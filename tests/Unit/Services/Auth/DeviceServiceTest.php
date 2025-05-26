<?php

use App\Exceptions\DeviceNotFoundException;
use App\Models\Device;
use App\Models\User;
use App\Services\Auth\DeviceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    createRoles();
    $this->deviceService = new DeviceService();

    // Create test users
    $this->user = User::factory()->create();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    // Create test devices
    $this->pendingDevice = Device::factory()->create([
        'user_id' => $this->user->id,
        'device_identifier' => 'pending-device-123',
        'status' => Device::STATUS_PENDING,
    ]);

    $this->approvedDevice = Device::factory()->create([
        'user_id' => $this->user->id,
        'device_identifier' => 'approved-device-123',
        'status' => Device::STATUS_APPROVED,
    ]);
});

test('getDeviceForUserByIdentifier returns device when it exists', function () {
    // Act
    $device = $this->deviceService->getDeviceForUserByIdentifier(
        $this->user->id,
        'approved-device-123'
    );

    // Assert
    expect($device)->toBeInstanceOf(Device::class)
        ->and($device->id)->toBe($this->approvedDevice->id)
        ->and($device->user_id)->toBe($this->user->id)
        ->and($device->device_identifier)->toBe('approved-device-123');
});

test('getDeviceForUserByIdentifier returns null when device does not exist', function () {
    // Act
    $device = $this->deviceService->getDeviceForUserByIdentifier(
        $this->user->id,
        'non-existent-device'
    );

    // Assert
    expect($device)->toBeNull();
});

test('listUserDevices returns all devices for a user', function () {
    // Act
    $devices = $this->deviceService->listUserDevices($this->user->id);

    // Assert
    expect($devices)->toBeCollection()
        ->and($devices)->toHaveCount(2)
        ->and($devices->pluck('id')->toArray())->toContain($this->pendingDevice->id, $this->approvedDevice->id);
});

test('listAllDevicesFiltered returns paginated devices with optional status filter', function () {
    // Create additional devices with different statuses
    Device::factory()->create([
        'user_id' => $this->user->id,
        'status' => Device::STATUS_REJECTED,
    ]);

    Device::factory()->create([
        'user_id' => $this->user->id,
        'status' => Device::STATUS_REVOKED,
    ]);

    // Act - Get all devices
    $allDevices = $this->deviceService->listAllDevicesFiltered(null, 10);

    // Assert
    expect($allDevices)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class)
        ->and($allDevices->total())->toBe(4);

    // Act - Get only pending devices
    $pendingDevices = $this->deviceService->listAllDevicesFiltered(Device::STATUS_PENDING, 10);

    // Assert
    expect($pendingDevices)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class)
        ->and($pendingDevices->total())->toBe(1)
        ->and($pendingDevices->items()[0]->id)->toBe($this->pendingDevice->id);
});

test('getDeviceDetails returns device when it exists', function () {
    // Act
    $device = $this->deviceService->getDeviceDetails($this->approvedDevice->id);

    // Assert
    expect($device)->toBeInstanceOf(Device::class)
        ->and($device->id)->toBe($this->approvedDevice->id);
});

test('getDeviceDetails throws exception when device does not exist', function () {
    // Act & Assert
    expect(fn() => $this->deviceService->getDeviceDetails(999))
        ->toThrow(DeviceNotFoundException::class);
});

test('approveDevice changes device status to approved', function () {
    // Act
    $device = $this->deviceService->approveDevice(
        $this->pendingDevice->id,
        $this->admin,
        'Approved for testing'
    );

    // Assert
    expect($device)->toBeInstanceOf(Device::class)
        ->and($device->status)->toBe(Device::STATUS_APPROVED)
        ->and($device->approved_by)->toBe($this->admin->id)
        ->and($device->approved_at)->not->toBeNull()
        ->and($device->admin_notes)->toBe('Approved for testing');

    $this->assertDatabaseHas('devices', [
        'id' => $this->pendingDevice->id,
        'status' => Device::STATUS_APPROVED,
        'approved_by' => $this->admin->id,
    ]);
});

test('rejectDevice changes device status to rejected', function () {
    // Act
    $device = $this->deviceService->rejectDevice(
        $this->pendingDevice->id,
        $this->admin,
        'Rejected for testing'
    );

    // Assert
    expect($device)->toBeInstanceOf(Device::class)
        ->and($device->status)->toBe(Device::STATUS_REJECTED)
        ->and($device->rejected_by)->toBe($this->admin->id)
        ->and($device->rejected_at)->not->toBeNull()
        ->and($device->admin_notes)->toBe('Rejected for testing');

    $this->assertDatabaseHas('devices', [
        'id' => $this->pendingDevice->id,
        'status' => Device::STATUS_REJECTED,
        'rejected_by' => $this->admin->id,
    ]);
});

test('revokeDevice changes device status to revoked', function () {
    // Act
    $device = $this->deviceService->revokeDevice(
        $this->approvedDevice->id,
        $this->admin,
        'Revoked for testing'
    );

    // Assert
    expect($device)->toBeInstanceOf(Device::class)
        ->and($device->status)->toBe(Device::STATUS_REVOKED)
        ->and($device->admin_notes)->toBe('Revoked for testing');

    $this->assertDatabaseHas('devices', [
        'id' => $this->approvedDevice->id,
        'status' => Device::STATUS_REVOKED,
    ]);
});

test('registerDeviceForUserByAdmin creates a new approved device', function () {
    // Arrange
    $deviceIdentifier = 'admin-registered-device-123';
    $deviceName = 'Admin Registered Device';

    // Act
    $device = $this->deviceService->registerDeviceForUserByAdmin(
        $this->user->id,
        $deviceIdentifier,
        $deviceName,
        $this->admin,
        'Registered by admin for testing'
    );

    // Assert
    expect($device)->toBeInstanceOf(Device::class)
        ->and($device->user_id)->toBe($this->user->id)
        ->and($device->device_identifier)->toBe($deviceIdentifier)
        ->and($device->name)->toBe($deviceName)
        ->and($device->status)->toBe(Device::STATUS_APPROVED)
        ->and($device->approved_by)->toBe($this->admin->id)
        ->and($device->approved_at)->not->toBeNull()
        ->and($device->admin_notes)->toBe('Registered by admin for testing');

    $this->assertDatabaseHas('devices', [
        'user_id' => $this->user->id,
        'device_identifier' => $deviceIdentifier,
        'name' => $deviceName,
        'status' => Device::STATUS_APPROVED,
    ]);
});

test('updateDeviceLastUsed updates the last_used_at timestamp', function () {
    // Arrange
    $originalTimestamp = $this->approvedDevice->last_used_at;

    // Act
    $this->deviceService->updateDeviceLastUsed($this->approvedDevice);
    $this->approvedDevice->refresh();

    // Assert
    expect($this->approvedDevice->last_used_at)->not->toBe($originalTimestamp);

    $this->assertDatabaseHas('devices', [
        'id' => $this->approvedDevice->id,
        'last_used_at' => $this->approvedDevice->last_used_at,
    ]);
});
