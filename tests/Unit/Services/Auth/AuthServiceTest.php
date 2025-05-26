<?php

use App\Exceptions\UnauthorizedDeviceException;
use App\Models\Device;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    createRoles();
    $this->authService = new AuthService();
});

test('register creates a new user with correct data', function () {
    // Arrange
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'user',
    ];

    // Act
    $user = $this->authService->register($userData);

    // Assert
    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe($userData['name'])
        ->and($user->email)->toBe($userData['email'])
        ->and($user->hasRole($userData['role']))->toBeTrue();

    $this->assertDatabaseHas('users', [
        'name' => $userData['name'],
        'email' => $userData['email'],
    ]);
});

test('login throws authentication exception for invalid credentials', function () {
    // Arrange
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $credentials = [
        'email' => 'test@example.com',
        'password' => 'wrong_password',
    ];

    // Act & Assert
    expect(fn() => $this->authService->login($credentials, 'device-123', 'Test Device', '127.0.0.1'))
        ->toThrow(AuthenticationException::class, 'Invalid credentials.');
});

test('login throws unauthorized device exception for new device', function () {
    // Arrange
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $credentials = [
        'email' => 'test@example.com',
        'password' => 'password123',
    ];

    // Act & Assert
    expect(fn() => $this->authService->login($credentials, 'new-device-123', 'New Test Device', '127.0.0.1'))
        ->toThrow(UnauthorizedDeviceException::class, 'User has no registered devices, new device has been registered and is waiting for admin approval.');

    $this->assertDatabaseHas('devices', [
        'user_id' => $user->id,
        'device_identifier' => 'new-device-123',
        'name' => 'New Test Device',
        'status' => Device::STATUS_PENDING,
    ]);
});

test('login throws unauthorized device exception for pending device', function () {
    // Arrange
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $device = Device::factory()->create([
        'user_id' => $user->id,
        'device_identifier' => 'existing-device-123',
        'status' => Device::STATUS_PENDING,
    ]);

    $credentials = [
        'email' => 'test@example.com',
        'password' => 'password123',
    ];

    // Act & Assert
    expect(fn() => $this->authService->login($credentials, 'existing-device-123', 'Existing Test Device', '127.0.0.1'))
        ->toThrow(UnauthorizedDeviceException::class, 'Your device is still pending admin approval.');
});

test('login returns token for approved device', function () {
    // Arrange
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $device = Device::factory()->create([
        'user_id' => $user->id,
        'device_identifier' => 'approved-device-123',
        'status' => Device::STATUS_APPROVED,
    ]);

    $credentials = [
        'email' => 'test@example.com',
        'password' => 'password123',
    ];

    // Act
    $result = $this->authService->login($credentials, 'approved-device-123', 'Approved Test Device', '127.0.0.1');

    // Assert
    expect($result)->toBeArray()
        ->and($result['user']->id)->toBe($user->id)
        ->and($result['device']->id)->toBe($device->id)
        ->and($result['access_token'])->toBeString();

    // Check that the device was updated
    $this->assertDatabaseHas('devices', [
        'id' => $device->id,
        'last_login_ip' => '127.0.0.1',
    ]);
});

test('logout deletes all tokens for user', function () {
    // Arrange
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // Act
    $this->authService->logout($user);

    // Assert
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id,
    ]);
});

test('getUserDetails returns user with devices and roles', function () {
    // Arrange
    $user = User::factory()->create();
    $user->assignRole('user');
    $device = Device::factory()->create([
        'user_id' => $user->id,
    ]);

    // Act
    $result = $this->authService->getUserDetails($user);

    // Assert
    expect($result)->toBeInstanceOf(User::class)
        ->and($result->devices)->toHaveCount(1)
        ->and($result->devices->first()->id)->toBe($device->id)
        ->and($result->roles)->toHaveCount(1)
        ->and($result->roles->first()->name)->toBe('user');
});
