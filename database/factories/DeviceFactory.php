<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_identifier' => 'device-' . Str::uuid(),
            'name' => fake()->randomElement(['iPhone', 'Android', 'iPad', 'MacBook', 'Windows PC', 'Linux Laptop']) . ' ' . fake()->randomElement(['Home', 'Work', 'Personal', 'Office']),
            'status' => Device::STATUS_PENDING,
            'admin_notes' => null,
            'last_login_ip' => fake()->ipv4(),
            'last_login_at' => fake()->optional(0.7)->dateTimeThisMonth(),
            'last_used_at' => fake()->optional(0.5)->dateTimeThisMonth(),
        ];
    }

    /**
     * Indicate that the device is approved.
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            $admin = User::factory()->create(['role' => 'admin']);

            return [
                'status' => Device::STATUS_APPROVED,
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'admin_notes' => 'Approved by admin ' . $admin->name,
            ];
        });
    }

    /**
     * Indicate that the device is rejected.
     */
    public function rejected(): static
    {
        return $this->state(function (array $attributes) {
            $admin = User::factory()->create(['role' => 'admin']);

            return [
                'status' => Device::STATUS_REJECTED,
                'rejected_by' => $admin->id,
                'rejected_at' => now(),
                'admin_notes' => fake()->sentence(),
            ];
        });
    }

    /**
     * Indicate that the device is revoked.
     */
    public function revoked(): static
    {
        return $this->state(function (array $attributes) {
            $admin = User::factory()->create(['role' => 'admin']);

            return [
                'status' => Device::STATUS_REVOKED,
                'admin_notes' => 'Device access revoked by admin ' . $admin->name,
            ];
        });
    }
}
