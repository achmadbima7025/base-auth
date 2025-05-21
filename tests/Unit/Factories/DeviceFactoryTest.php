<?php

use App\Models\Device;
use App\Models\User;

test('device factory creates a device with default values', function () {
    $device = Device::factory()->create();

    expect($device)->toBeInstanceOf(Device::class)
        ->and($device->user_id)->toBeGreaterThan(0)
        ->and($device->device_identifier)->toStartWith('device-')
        ->and($device->name)->not->toBeEmpty()
        ->and($device->status)->toBe(Device::STATUS_PENDING);
});

test('device factory creates an approved device', function () {
    $device = Device::factory()->approved()->create();

    expect($device)->toBeInstanceOf(Device::class)
        ->and($device->status)->toBe(Device::STATUS_APPROVED)
        ->and($device->approved_by)->toBeGreaterThan(0)
        ->and($device->approved_at)->not->toBeNull();

    // Verify the admin user exists
    $admin = User::find($device->approved_by);
    expect($admin)->toBeInstanceOf(User::class)
        ->and($admin->role)->toBe('admin');
});

test('device factory creates a rejected device', function () {
    $device = Device::factory()->rejected()->create();

    expect($device)->toBeInstanceOf(Device::class)
        ->and($device->status)->toBe(Device::STATUS_REJECTED)
        ->and($device->rejected_by)->toBeGreaterThan(0)
        ->and($device->rejected_at)->not->toBeNull()
        ->and($device->admin_notes)->not->toBeEmpty();

    // Verify the admin user exists
    $admin = User::find($device->rejected_by);
    expect($admin)->toBeInstanceOf(User::class)
        ->and($admin->role)->toBe('admin');
});

test('device factory creates a revoked device', function () {
    $device = Device::factory()->revoked()->create();

    expect($device)->toBeInstanceOf(Device::class)
        ->and($device->status)->toBe(Device::STATUS_REVOKED)
        ->and($device->admin_notes)->toContain('revoked');
});

test('device factory can create multiple devices for the same user', function () {
    $user = User::factory()->create();

    $devices = Device::factory()
        ->count(3)
        ->for($user)
        ->create();

    expect($devices)->toHaveCount(3);

    foreach ($devices as $device) {
        expect($device->user_id)->toBe($user->id);
    }
});
