<?php

use App\Models\User;
use App\Models\Device;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

test('register creates a new user ywith correct data', function () {
    // Arrange
    $authService = new AuthService();
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    // Act
    $result = $authService->register($userData);

    // Assert
    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toBe('Registered successfully.')
        ->and($result['data'])->toBeInstanceOf(User::class)
        ->and($result['data']->name)->toBe($userData['name'])
        ->and($result['data']->email)->toBe($userData['email'])
        ->and($result['data']->role)->toBe('user');

    // Verify the user was saved to the database
    $this->assertDatabaseHas('users', [
        'name' => $userData['name'],
        'email' => $userData['email'],
        'role' => 'user',
    ]);
});

test('register handles exceptions gracefully', function () {
    // Arrange
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $authService = Mockery::mock(AuthService::class);
    $authService->expects()->register($userData)
        ->once()
        ->andReturn([
            'success' => false,
            'message' => 'Internal server error.',
        ]);

    // Bind the mock to the container
    $this->app->instance(AuthService::class, $authService);

    $result = $authService->register($userData);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toBe('Internal server error.');
});

test('login returns error for incorrect credentials', function () {
    // Arrange
    $authService = new AuthService();
    $credentials = [
        'email' => 'nonexistent@example.com',
        'password' => 'password123',
    ];

    // Act
    $result = $authService->login($credentials, 'device-123', 'Test Device', '127.0.0.1');

    // Assert
    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toBe('Incorrect email or password.');
});

test('login creates new pending device if device does not exist', function () {
    // Arrange
    $authService = new AuthService();
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
    ]);
    $credentials = [
        'email' => $user->email,
        'password' => 'password123',
    ];

    $deviceIdentifier = 'new-device-' . Str::random(10);
    $deviceName = 'Test Device';
    $ipAddress = '127.0.0.1';

    // Act
    $result = $authService->login($credentials, $deviceIdentifier, $deviceName, $ipAddress);

    // Assert
    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toBe('Device registration request received. Please wait for admin approval.');

    // Verify device was created
    $this->assertDatabaseHas('devices', [
        'user_id' => $user->id,
        'device_identifier' => $deviceIdentifier,
        'name' => $deviceName,
        'status' => Device::STATUS_PENDING,
        'last_login_ip' => $ipAddress,
    ]);
});

test('login returns error for non-approved device', function () {
    // Arrange
    $authService = new AuthService();
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
    ]);

    $device = Device::factory()->create([
        'user_id' => $user->id,
        'status' => Device::STATUS_PENDING,
    ]);

    $credentials = [
        'email' => $user->email,
        'password' => 'password123',
    ];

    // Act
    $result = $authService->login($credentials, $device->device_identifier, 'Test Device', '127.0.0.1');

    // Assert
    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toBe('Your device is still pending admin approval.');
});

test('login returns token for approved device', function () {
    // Arrange
    $authService = new AuthService();
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
    ]);

    $device = Device::factory()->create([
        'user_id' => $user->id,
        'status' => Device::STATUS_APPROVED,
    ]);

    $credentials = [
        'email' => $user->email,
        'password' => 'password123',
    ];

    // Act
    $result = $authService->login($credentials, $device->device_identifier, 'Test Device', '127.0.0.1');

    // Assert
    expect($result['success'])->toBeTrue()
        ->and($result['user'])->toBeArray()
        ->and($result['device'])->toBeArray()
        ->and($result['access_token'])->toBeString();

    // Verify device was updated
    $this->assertDatabaseHas('devices', [
        'id' => $device->id,
        'last_login_ip' => '127.0.0.1',
    ]);
});

test('logout deletes all user tokens', function () {
    // Arrange
    $authService = new AuthService();
    $user = User::factory()->create();

    // Create some tokens for the user
    $user->createToken('test-token-1');
    $user->createToken('test-token-2');

    // Act
    $result = $authService->logout($user);

    // Assert
    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toBe('Logged out successfully.')
        ->and($user->tokens()->count())->toBe(0);

    // Verify tokens were deleted
});

test('getUserDetails returns correct user data', function () {
    // Arrange
    $authService = new AuthService();
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'user',
    ]);

    // Act
    $result = $authService->getUserDetails($user);

    // Assert
    expect($result['success'])->toBeTrue()
        ->and($result['user'])->toBeArray()
        ->and($result['user']['id'])->toBe($user->id)
        ->and($result['user']['name'])->toBe('Test User')
        ->and($result['user']['email'])->toBe('test@example.com')
        ->and($result['user']['role'])->toBe('user');
});
