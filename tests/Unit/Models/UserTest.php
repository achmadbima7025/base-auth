<?php

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles
    createRoles();

    // Create test user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create admin user
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    // Create devices for the user
    $this->pendingDevice = Device::factory()->create([
        'user_id' => $this->user->id,
        'status' => Device::STATUS_PENDING,
    ]);

    $this->approvedDevice = Device::factory()->create([
        'user_id' => $this->user->id,
        'status' => Device::STATUS_APPROVED,
    ]);

    // Create a device approved by the admin
    $this->adminApprovedDevice = Device::factory()->create([
        'user_id' => $this->user->id,
        'status' => Device::STATUS_APPROVED,
        'approved_by' => $this->admin->id,
    ]);
});

test('user has many devices', function () {
    // Assert
    expect($this->user->devices)->toBeCollection()
        ->and($this->user->devices)->toHaveCount(3)
        ->and($this->user->devices->pluck('id')->toArray())->toContain(
            $this->pendingDevice->id,
            $this->approvedDevice->id,
            $this->adminApprovedDevice->id
        );
});

test('user has many approved devices', function () {
    // Act
    $approvedDevice = $this->user->approvedDevice();

    // Assert
    expect($approvedDevice)->toBeInstanceOf(Device::class)
        ->and($approvedDevice->status)->toBe(Device::STATUS_APPROVED);
});

test('admin has approved devices relationship', function () {
    // Assert
    expect($this->admin->approver)->toBeCollection()
        ->and($this->admin->approver)->toHaveCount(1)
        ->and($this->admin->approver->first()->id)->toBe($this->adminApprovedDevice->id);
});

test('isAdmin returns true for admin user', function () {
    // Assert
    expect($this->admin->isAdmin())->toBeTrue();
});

test('isAdmin returns false for non-admin user', function () {
    // Assert
    expect($this->user->isAdmin())->toBeFalse();
});

test('user has fillable attributes', function () {
    // Arrange
    $fillable = ['name', 'email', 'password'];

    // Assert
    expect($this->user->getFillable())->toBe($fillable);
});

test('user has hidden attributes', function () {
    // Arrange
    $hidden = ['password', 'remember_token'];

    // Assert
    expect($this->user->getHidden())->toBe($hidden);
});

test('user has correct casts', function () {
    // Arrange
    $expectedCasts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Assert
    expect($this->user->getCasts())->toMatchArray($expectedCasts);
});
