<?php

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles
    createRoles();

    // Create admin user
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    // Create approved device for admin
    $this->adminApprovedDevice = Device::factory()->create([
        'user_id' => $this->admin->id,
        'device_identifier' => 'approved-device-123',
        'name' => 'Admin Approved Device',
        'status' => Device::STATUS_APPROVED,
        'approved_by' => $this->admin->id,
        'approved_at' => now(),
    ]);

    // Create regular user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create another user
    $this->anotherUser = User::factory()->create();
    $this->anotherUser->assignRole('user');

    // Create devices for the user
    $this->pendingDevice = Device::factory()->create([
        'user_id' => $this->user->id,
        'device_identifier' => 'pending-device-123',
        'name' => 'Pending Device',
        'status' => Device::STATUS_PENDING,
    ]);

    $this->approvedDevice = Device::factory()->create([
        'user_id' => $this->user->id,
        'device_identifier' => 'approved-device-123',
        'name' => 'Approved Device',
        'status' => Device::STATUS_APPROVED,
        'approved_by' => $this->admin->id,
        'approved_at' => now(),
    ]);

    $this->rejectedDevice = Device::factory()->create([
        'user_id' => $this->user->id,
        'device_identifier' => 'rejected-device-123',
        'name' => 'Rejected Device',
        'status' => Device::STATUS_REJECTED,
        'rejected_by' => $this->admin->id,
        'rejected_at' => now(),
    ]);
});

test('getDeviceForUserByIdentifier returns device when it exists', function () {
    // Arrange
    $this->actingAs($this->admin);

    // Act
    $response = getJson("/api/users/{$this->user->id}/devices/approved-device-123", [
        'X-Device-ID' => 'approved-device-123',
    ]);

    // Assert
    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $this->approvedDevice->id)
        ->assertJsonPath('data.device_identifier', 'approved-device-123');
});

test('getDeviceForUserByIdentifier returns 404 when device does not exist', function () {
    // Arrange
    $this->actingAs($this->admin);

    // Act
    $response = getJson("/api/users/{$this->user->id}/devices/non-existent-device", [
        'X-Device-ID' => 'approved-device-123',
    ]);

    // Assert
    $response->assertStatus(404)
        ->assertJsonPath('success', false);
});

test('listUserDevices returns all devices for a user', function () {
    // Arrange
    $this->actingAs($this->admin);

    // Act
    $response = getJson("/api/users/{$this->user->id}/devices", [
        'X-Device-ID' => 'approved-device-123',
    ]);

    // Assert
    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.user_id', $this->user->id);
});

test('listAllDevice returns all devices with optional filtering', function () {
    // Arrange
    $this->actingAs($this->admin);

    // Act - Get all devices
    $response = getJson("/api/devices", [
        'X-Device-ID' => 'approved-device-123',
    ]);

    // Assert
    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data'); // data.data because of pagination

    // Act - Get only pending devices
    $response = getJson("/api/devices?status=pending", [
        'X-Device-ID' => 'approved-device-123',
    ]);

    // Assert
    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.status', Device::STATUS_PENDING);
});

test('getDetailDevice returns device details', function () {
    // Arrange
    $this->actingAs($this->admin);

    // Act
    $response = getJson("/api/devices/{$this->approvedDevice->id}", [
        'X-Device-ID' => 'approved-device-123',
    ]);

    // Assert
    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $this->approvedDevice->id)
        ->assertJsonPath('data.device_identifier', 'approved-device-123')
        ->assertJsonPath('data.status', Device::STATUS_APPROVED);
});

test('getDetailDevice returns 404 for non-existent device', function () {
    // Arrange
    $this->actingAs($this->admin);

    // Act
    $response = getJson("/api/devices/999", [
        'X-Device-ID' => 'approved-device-123',
    ]);

    // Assert
    $response->assertStatus(404)
        ->assertJsonPath('success', false);
});

test('approveDevice changes device status to approved', function () {
    // Arrange
    $this->actingAs($this->admin);
    $data = [
        'notes' => 'Approved for testing',
    ];

    // Act
    $response = postJson("/api/devices/{$this->pendingDevice->id}/approve", $data, [
        'X-Device-ID' => 'approved-device-123',
    ]);

    // Assert
    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $this->pendingDevice->id)
        ->assertJsonPath('data.status', Device::STATUS_APPROVED)
        ->assertJsonPath('data.admin_notes', 'Approved for testing');

    $this->assertDatabaseHas('devices', [
        'id' => $this->pendingDevice->id,
        'status' => Device::STATUS_APPROVED,
        'approved_by' => $this->admin->id,
    ]);
});

test('rejectDevice changes device status to rejected', function () {
    // Arrange
    $this->actingAs($this->admin);
    $data = [
        'notes' => 'Rejected for testing',
    ];

    // Act
    $response = postJson("/api/devices/{$this->pendingDevice->id}/reject", $data, [
        'X-Device-ID' => 'approved-device-123',
    ]);

    // Assert
    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $this->pendingDevice->id)
        ->assertJsonPath('data.status', Device::STATUS_REJECTED)
        ->assertJsonPath('data.admin_notes', 'Rejected for testing');

    $this->assertDatabaseHas('devices', [
        'id' => $this->pendingDevice->id,
        'status' => Device::STATUS_REJECTED,
        'rejected_by' => $this->admin->id,
    ]);
});

test('revokeDevice changes device status to revoked', function () {
    // Arrange
    $this->actingAs($this->admin);
    $data = [
        'notes' => 'Revoked for testing',
    ];

    // Act
    $response = postJson("/api/devices/{$this->approvedDevice->id}/revoke", $data, [
        'X-Device-ID' => 'approved-device-123',
    ]);

    // Assert
    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $this->approvedDevice->id)
        ->assertJsonPath('data.status', Device::STATUS_REVOKED)
        ->assertJsonPath('data.admin_notes', 'Revoked for testing');

    $this->assertDatabaseHas('devices', [
        'id' => $this->approvedDevice->id,
        'status' => Device::STATUS_REVOKED,
    ]);
});

test('registerDeviceForUserByAdmin creates a new approved device', function () {
    // Arrange
    $this->actingAs($this->admin);
    $data = [
        'user_id' => $this->anotherUser->id,
        'device_identifier' => 'admin-registered-device-123',
        'device_name' => 'Admin Registered Device',
        'notes' => 'Registered by admin for testing',
    ];

    // Act
    $response = postJson("/api/devices/register", $data, [
        'X-Device-ID' => 'approved-device-123',
    ]);

    // Assert
    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user_id', $this->anotherUser->id)
        ->assertJsonPath('data.device_identifier', 'admin-registered-device-123')
        ->assertJsonPath('data.name', 'Admin Registered Device')
        ->assertJsonPath('data.status', Device::STATUS_APPROVED);

    $this->assertDatabaseHas('devices', [
        'user_id' => $this->anotherUser->id,
        'device_identifier' => 'admin-registered-device-123',
        'name' => 'Admin Registered Device',
        'status' => Device::STATUS_APPROVED,
        'approved_by' => $this->admin->id,
    ]);
});

test('device management endpoints require admin privileges', function () {
    // Arrange
    $this->actingAs($this->user);

    // Act & Assert - List all devices
    getJson("/api/devices", [
        'X-Device-ID' => 'approved-device-123',
    ])->assertStatus(403);

    // Act & Assert - Get device details
    getJson("/api/devices/{$this->approvedDevice->id}", [
        'X-Device-ID' => 'approved-device-123',
    ])->assertStatus(403);

    // Act & Assert - Approve device
    postJson("/api/devices/{$this->pendingDevice->id}/approve", ['notes' => 'Test'], [
        'X-Device-ID' => 'approved-device-123',
    ])->assertStatus(403);

    // Act & Assert - Reject device
    postJson("/api/devices/{$this->pendingDevice->id}/reject", ['notes' => 'Test'], [
        'X-Device-ID' => 'approved-device-123',
    ])->assertStatus(403);

    // Act & Assert - Revoke device
    postJson("/api/devices/{$this->approvedDevice->id}/revoke", ['notes' => 'Test'], [
        'X-Device-ID' => 'approved-device-123',
    ])->assertStatus(403);

    // Act & Assert - Register device
    postJson("/api/devices/register", [
        'user_id' => $this->anotherUser->id,
        'device_identifier' => 'test-device',
        'device_name' => 'Test Device',
    ], [
        'X-Device-ID' => 'approved-device-123',
    ])->assertStatus(403);
});
