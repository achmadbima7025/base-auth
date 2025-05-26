<?php

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    createRoles();
    // Create test users
    $this->user = User::factory()->create();
    $this->admin = User::factory()->create();

    // Create test device
    $this->device = Device::factory()->create([
        'user_id' => $this->user->id,
        'device_identifier' => 'test-device-123',
        'name' => 'Test Device',
        'status' => Device::STATUS_PENDING,
    ]);

    // Create approved device
    $this->approvedDevice = Device::factory()->create([
        'user_id' => $this->user->id,
        'device_identifier' => 'approved-device-123',
        'name' => 'Approved Device',
        'status' => Device::STATUS_APPROVED,
        'approved_by' => $this->admin->id,
        'approved_at' => now(),
    ]);

    // Create rejected device
    $this->rejectedDevice = Device::factory()->create([
        'user_id' => $this->user->id,
        'device_identifier' => 'rejected-device-123',
        'name' => 'Rejected Device',
        'status' => Device::STATUS_REJECTED,
        'rejected_by' => $this->admin->id,
        'rejected_at' => now(),
    ]);
});

test('device belongs to user', function () {
    // Assert
    expect($this->device->user)->toBeInstanceOf(User::class)
        ->and($this->device->user->id)->toBe($this->user->id);
});

test('device belongs to approver', function () {
    // Assert
    expect($this->approvedDevice->approver)->toBeInstanceOf(User::class)
        ->and($this->approvedDevice->approver->id)->toBe($this->admin->id);
});

test('device belongs to rejector', function () {
    // Assert
    expect($this->rejectedDevice->rejector)->toBeInstanceOf(User::class)
        ->and($this->rejectedDevice->rejector->id)->toBe($this->admin->id);
});

test('isApproved returns true for approved device', function () {
    // Assert
    expect($this->approvedDevice->isApproved())->toBeTrue();
});

test('isApproved returns false for non-approved device', function () {
    // Assert
    expect($this->device->isApproved())->toBeFalse()
        ->and($this->rejectedDevice->isApproved())->toBeFalse();
});

test('device has fillable attributes', function () {
    // Arrange
    $fillable = [
        'user_id',
        'device_identifier',
        'name',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'admin_notes',
        'last_login_ip',
        'last_login_at',
        'last_used_at',
    ];

    // Assert
    expect($this->device->getFillable())->toBe($fillable);
});

test('device has correct casts', function () {
    // Arrange
    $expectedCasts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'last_login_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    // Assert
    expect($this->device->getCasts())->toMatchArray($expectedCasts);
});

test('device has correct status constants', function () {
    // Assert
    expect(Device::STATUS_PENDING)->toBe('pending')
        ->and(Device::STATUS_APPROVED)->toBe('approved')
        ->and(Device::STATUS_REJECTED)->toBe('rejected')
        ->and(Device::STATUS_REVOKED)->toBe('revoked');
});
