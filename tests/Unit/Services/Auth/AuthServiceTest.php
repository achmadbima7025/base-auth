<?php

use App\Models\User;
use App\Models\Device;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

test('register creates a new user with correct data', function () {
    // Arrange
    $authService = new AuthService();
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    // Act
    $result = $authService->register($userData);

    // Assert
    expect($result['success'])->toBeTrue();
    expect($result['message'])->toBe('Registered successfully.');
    expect($result['data'])->toBeInstanceOf(User::class);
    expect($result['data']->name)->toBe($userData['name']);
    expect($result['data']->email)->toBe($userData['email']);
    expect($result['data']->role)->toBe('user');

    // Verify the user was saved to the database
    $this->assertDatabaseHas('users', [
        'name' => $userData['name'],
        'email' => $userData['email'],
        'role' => 'user',
    ]);
});

test('register handles exceptions gracefully', function () {
    // Arrange
    $authService = new AuthService();
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    // Mock User to throw an exception when save is called
    $userMock = $this->createMock(User::class);
    $userMock->method('save')->willThrowException(new Exception('Database error'));

    // Use a partial mock of AuthService to return our mocked User
    $authServiceMock = $this->createPartialMock(AuthService::class, []);
    $this->app->instance(User::class, $userMock);

    // Mock Log facade to verify it's called
    Log::shouldReceive('error')->once()->with('Database error');

    // Act
    $result = $authService->register($userData);

    // Assert
    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Server error.');
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
    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Incorrect email or password.');
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
    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Device registration request received. Please wait for admin approval.');

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
    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Your device is still pending admin approval.');
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
    expect($result['success'])->toBeTrue();
    expect($result['user'])->toBeArray();
    expect($result['device'])->toBeArray();
    expect($result['access_token'])->toBeString();

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
    expect($result['success'])->toBeTrue();
    expect($result['message'])->toBe('Logged out successfully.');

    // Verify tokens were deleted
    expect($user->tokens()->count())->toBe(0);
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
    expect($result['success'])->toBeTrue();
    expect($result['user'])->toBeArray();
    expect($result['user']['id'])->toBe($user->id);
    expect($result['user']['name'])->toBe('Test User');
    expect($result['user']['email'])->toBe('test@example.com');
    expect($result['user']['role'])->toBe('user');
});
