<?php

use App\Models\User;
use App\Models\Device;
use App\Services\Auth\DeviceService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

test('getDeviceForUserByIdentifier returns correct device', function () {
    // Arrange
    $deviceService = new DeviceService();
    $user = User::factory()->create();
    $device = Device::factory()->create([
        'user_id' => $user->id,
        'device_identifier' => 'test-device-123',
    ]);

    // Act
    $result = $deviceService->getDeviceForUserByIdentifier($user, 'test-device-123');

    // Assert
    expect($result)->toBeInstanceOf(Device::class);
    expect($result->id)->toBe($device->id);
    expect($result->device_identifier)->toBe('test-device-123');
});

test('getDeviceForUserByIdentifier returns null for non-existent device', function () {
    // Arrange
    $deviceService = new DeviceService();
    $user = User::factory()->create();

    // Act
    $result = $deviceService->getDeviceForUserByIdentifier($user, 'non-existent-device');

    // Assert
    expect($result)->toBeNull();
});

test('listUserDevices returns collection of user devices', function () {
    // Arrange
    $deviceService = new DeviceService();
    $user = User::factory()->create();
    $devices = Device::factory()->count(3)->create([
        'user_id' => $user->id,
    ]);

    // Create a device for another user to ensure it's not included
    Device::factory()->create();

    // Act
    $result = $deviceService->listUserDevices($user);

    // Assert
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(3);
    expect($result->pluck('id')->toArray())->toEqual($devices->pluck('id')->toArray());
});

test('listAllDevicesFiltered returns paginated devices with status filter', function () {
    // Arrange
    $deviceService = new DeviceService();

    // Create devices with different statuses
    Device::factory()->count(3)->create(['status' => Device::STATUS_APPROVED]);
    Device::factory()->count(2)->create(['status' => Device::STATUS_PENDING]);

    // Act
    $result = $deviceService->listAllDevicesFiltered(Device::STATUS_APPROVED, 10);

    // Assert
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->total())->toBe(3);
    expect($result->first()->status)->toBe(Device::STATUS_APPROVED);
});

test('getDeviceDetails returns correct device data', function () {
    // Arrange
    $deviceService = new DeviceService();
    $user = User::factory()->create();
    $device = Device::factory()->create([
        'user_id' => $user->id,
    ]);

    // Act
    $result = $deviceService->getDeviceDetails($device);

    // Assert
    expect($result['success'])->toBeTrue();
    expect($result['data'])->toBeInstanceOf(Device::class);
    expect($result['data']->id)->toBe($device->id);
    expect($result['data']->user)->toBeInstanceOf(User::class);
    expect($result['data']->user->id)->toBe($user->id);
});

test('approveDevice changes device status to approved', function () {
    // Arrange
    $deviceService = new DeviceService();
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $device = Device::factory()->create([
        'user_id' => $user->id,
        'status' => Device::STATUS_PENDING,
    ]);

    // Act
    $result = $deviceService->approveDevice($device, $admin, 'Approved for testing');

    // Assert
    expect($result['success'])->toBeTrue();
    expect($result['message'])->toBe('Device approved successfully.');
    expect($result['data']->status)->toBe(Device::STATUS_APPROVED);
    expect($result['data']->approved_by)->toBe($admin->id);
    expect($result['data']->admin_notes)->toBe('Approved for testing');

    // Verify database was updated
    $this->assertDatabaseHas('devices', [
        'id' => $device->id,
        'status' => Device::STATUS_APPROVED,
        'approved_by' => $admin->id,
    ]);
});

test('approveDevice revokes previously approved device', function () {
    // Arrange
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

    // Act
    $result = $deviceService->approveDevice($newDevice, $admin, 'Approved new device');

    // Assert
    expect($result['success'])->toBeTrue();

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

test('rejectDevice changes device status to rejected', function () {
    // Arrange
    $deviceService = new DeviceService();
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $device = Device::factory()->create([
        'user_id' => $user->id,
        'status' => Device::STATUS_PENDING,
    ]);

    // Act
    $result = $deviceService->rejectDevice($device, $admin, 'Suspicious activity');

    // Assert
    expect($result['success'])->toBeTrue();
    expect($result['message'])->toBe('Device rejected successfully.');
    expect($result['data']->status)->toBe(Device::STATUS_REJECTED);
    expect($result['data']->rejected_by)->toBe($admin->id);
    expect($result['data']->admin_notes)->toBe('Suspicious activity');

    // Verify database was updated
    $this->assertDatabaseHas('devices', [
        'id' => $device->id,
        'status' => Device::STATUS_REJECTED,
        'rejected_by' => $admin->id,
        'admin_notes' => 'Suspicious activity',
    ]);
});

test('revokeDevice changes device status to revoked', function () {
    // Arrange
    $deviceService = new DeviceService();
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $device = Device::factory()->create([
        'user_id' => $user->id,
        'status' => Device::STATUS_APPROVED,
    ]);

    // Create a token for the device
    $tokenName = "auth_token_user_{$user->id}_device_{$device->id}";
    $user->createToken($tokenName);

    // Act
    $result = $deviceService->revokeDevice($device, $admin, 'Device reported lost');

    // Assert
    expect($result['success'])->toBeTrue();
    expect($result['message'])->toBe('Device revoked successfully.');
    expect($result['data']->status)->toBe(Device::STATUS_REVOKED);
    expect($result['data']->admin_notes)->toBe('Device reported lost');

    // Verify database was updated
    $this->assertDatabaseHas('devices', [
        'id' => $device->id,
        'status' => Device::STATUS_REVOKED,
        'admin_notes' => 'Device reported lost',
    ]);

    // Verify token was deleted
    expect($user->tokens()->where('name', $tokenName)->count())->toBe(0);
});

test('registerDeviceForUserByAdmin creates approved device', function () {
    // Arrange
    $deviceService = new DeviceService();
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $deviceIdentifier = 'admin-registered-device-123';
    $deviceName = 'Office Laptop';

    // Act
    $result = $deviceService->registerDeviceForUserByAdmin(
        $user,
        $deviceIdentifier,
        $deviceName,
        $admin,
        'Registered for new employee'
    );

    // Assert
    expect($result['success'])->toBeTrue();
    expect($result['message'])->toBe('Device registration successful.');
    expect($result['data']->device_identifier)->toBe($deviceIdentifier);
    expect($result['data']->name)->toBe($deviceName);
    expect($result['data']->status)->toBe(Device::STATUS_APPROVED);
    expect($result['data']->approved_by)->toBe($admin->id);
    expect($result['data']->admin_notes)->toBe('Registered for new employee');

    // Verify database was updated
    $this->assertDatabaseHas('devices', [
        'user_id' => $user->id,
        'device_identifier' => $deviceIdentifier,
        'name' => $deviceName,
        'status' => Device::STATUS_APPROVED,
        'approved_by' => $admin->id,
        'admin_notes' => 'Registered for new employee',
    ]);
});

test('updateDeviceLastUsed updates last_used_at timestamp', function () {
    // Arrange
    $deviceService = new DeviceService();
    $device = Device::factory()->create([
        'last_used_at' => null,
    ]);

    // Act
    $deviceService->updateDeviceLastUsed($device);

    // Assert
    $device->refresh();
    expect($device->last_used_at)->not->toBeNull();
    expect($device->last_used_at)->toBeInstanceOf(\Carbon\Carbon::class);

    // Verify the timestamp is recent (within the last minute)
    expect($device->last_used_at->diffInMinutes(now()))->toBeLessThan(1);
});
