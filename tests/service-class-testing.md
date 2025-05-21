# Testing Service Classes in Laravel

This document provides guidance on how to test service classes in Laravel applications, with examples from the AuthService and DeviceService classes.

## Types of Tests for Service Classes

Service classes typically encapsulate business logic and interact with models, facades, and other services. Here are the types of tests that can be written for service classes:

1. **Unit Tests**: Test individual methods in isolation, mocking dependencies.
2. **Integration Tests**: Test how the service interacts with other components like models and facades.
3. **Feature Tests**: Test the service as part of a larger feature, often through HTTP requests.

## Test Approaches

### 1. Testing Return Values

Test that methods return the expected values:

```php
test('getUserDetails returns correct user data', function () {
    $authService = new AuthService();
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'user',
    ]);
    
    $result = $authService->getUserDetails($user);
    
    expect($result['success'])->toBeTrue();
    expect($result['user'])->toBeArray();
    expect($result['user']['id'])->toBe($user->id);
    expect($result['user']['name'])->toBe('Test User');
});
```

### 2. Testing Database Changes

Test that methods make the expected changes to the database:

```php
test('register creates a new user with correct data', function () {
    $authService = new AuthService();
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];
    
    $result = $authService->register($userData);
    
    $this->assertDatabaseHas('users', [
        'name' => $userData['name'],
        'email' => $userData['email'],
        'role' => 'user',
    ]);
});
```

### 3. Testing Different Scenarios

Test different scenarios and edge cases:

```php
test('login returns error for incorrect credentials', function () {
    $authService = new AuthService();
    $credentials = [
        'email' => 'nonexistent@example.com',
        'password' => 'password123',
    ];
    
    $result = $authService->login($credentials, 'device-123', 'Test Device', '127.0.0.1');
    
    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Incorrect email or password.');
});
```

### 4. Testing Relationships and Complex Logic

Test how the service handles relationships and complex business logic:

```php
test('approveDevice revokes previously approved device', function () {
    $deviceService = new DeviceService();
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    
    // Create a previously approved device
    $oldDevice = Device::factory()->create([
        'user_id' => $user->id,
        'status' => Device::STATUS_APPROVED,
    ]);
    
    // Create a new device to approve
    $newDevice = Device::factory()->create([
        'user_id' => $user->id,
        'status' => Device::STATUS_PENDING,
    ]);
    
    $result = $deviceService->approveDevice($newDevice, $admin, 'Approved new device');
    
    // Verify old device was revoked
    $this->assertDatabaseHas('devices', [
        'id' => $oldDevice->id,
        'status' => Device::STATUS_REVOKED,
    ]);
    
    // Verify new device was approved
    $this->assertDatabaseHas('devices', [
        'id' => $newDevice->id,
        'status' => Device::STATUS_APPROVED,
    ]);
});
```

### 5. Testing Error Handling

Test how the service handles errors and exceptions:

```php
test('register handles exceptions gracefully', function () {
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
    
    $result = $authService->register($userData);
    
    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Server error.');
});
```

## Best Practices

1. **Use Factories**: Use model factories to create test data.
2. **Test Edge Cases**: Test both happy paths and edge cases.
3. **Mock Dependencies**: Use mocks for external dependencies to isolate the service being tested.
4. **Use Database Transactions**: Wrap tests in database transactions to avoid test pollution.
5. **Test One Thing at a Time**: Each test should focus on testing one specific behavior.
6. **Use Descriptive Test Names**: Test names should clearly describe what is being tested.
7. **Follow AAA Pattern**: Arrange, Act, Assert - structure tests in this way for clarity.

## Example Test Files

For complete examples, see:
- [AuthServiceTest.php](./Unit/Services/Auth/AuthServiceTest.php)
- [DeviceServiceTest.php](./Unit/Services/Auth/DeviceServiceTest.php)

These files demonstrate how to test various aspects of service classes in Laravel.
