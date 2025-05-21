<?php

namespace App\Services\Auth;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeviceService
{
    public function getDeviceForUserByIdentifier(User $user, string $deviceIdentifier): ?Device
    {
        return $user->devices()
            ->where('device_identifier', $deviceIdentifier)
            ->first();
    }

    public function listUserDevices(User $user): Collection
    {
        return $user->devices()
            ->select([
                'id',
                'user_id',
                'name',
                'device_identifier',
                'status',
                'last_login_at',
                'admin_notes'
            ])
            ->orderBy('id')
            ->get();
    }

    public function listAllDevicesFiltered(?string $status, int $perPage = 10): LengthAwarePaginator
    {
        return Device::with(['user:id,name,email', 'approver:id,name'])
                        ->when(!is_null($status), function ($query) use ($status) {
                            $query->where('status', $status);
                        })
                        ->orderBy('created_at', 'desc')
                        ->paginate($perPage);
    }

    public function getDeviceDetails(Device $device): array
    {
        return [
            'success' => true,
            'data' => $device->load(['user:id,name,email', 'approver:id,name'])
        ];
    }

    public function approveDevice(Device $device, User $admin, ?string $notes): array
    {
        DB::beginTransaction();
        try {
            $targetUser = $device->user;
            if (!$targetUser) {
                $device->load('user');
                $targetUser = $device->user;

                if (!$targetUser) {
                    Log::critical("DeviceManagementService: User relationship is null for UserDevice ID: {$device->id}. Cannot proceed with approval.");

                    return [
                        'success' => false,
                        'message' => "Associated user not found for the device being approved (ID: {$device->id})."
                    ];
                }
            }

            $oldApprovedDevice = $targetUser->devices()
                ->where('status', Device::STATUS_APPROVED)
                ->where('id', '!=', $device->id)
                ->first();

            if ($oldApprovedDevice) {
                $this->revokeOldDeviceInternal(
                    $oldApprovedDevice,
                    $targetUser,
                    $admin,
                    'Automatically revoked due to approval of new device: ' . $device->name
                );
            }

            $device->status = Device::STATUS_APPROVED;
            $device->approved_by = $admin->id;
            $device->approved_at = now();
            $device->admin_notes = $notes ?? "Device approved by admin {$admin->name}";

            $device->save();
            DB::commit();

            return [
                'success' => true,
                'message' => "Device approved successfully.",
                'data' => $device->fresh(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return [
                'success' => false,
                'message' => 'Internal server error',
            ];
        }
    }

    public function rejectDevice(Device $device, User $admin, string $notes): array
    {
        try {
            $device->status = Device::STATUS_REJECTED;
            $device->rejected_by = $admin->id;
            $device->rejected_at = now();
            $device->admin_notes = $notes;
            $device->save();

            return [
                'success' => true,
                'message' => "Device rejected successfully.",
                'data' => $device->fresh(),
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [
                'success' => false,
                'message' => 'Internal server error',
            ];
        }
    }

    public function revokeDevice(Device $device, User $admin, ?string $notes): array
    {
        try {
            $device->status = Device::STATUS_REVOKED;
            $device->admin_notes = $notes ?: "Device revoked by admin {$admin->name}";
            $device->save();

            $this->revokeTokenForDevice($device);

            return [
                'success' => true,
                'message' => 'Device revoked successfully.',
                'data' => $device->fresh(),
            ];
        } catch (\Exception $e) {
            LOg::error($e->getMessage());
            return [
                'success' => false,
                'message' => 'Internal server error.',
            ];
        }
    }

    public function registerDeviceForUserByAdmin(
        User $user,
        string $deviceIdentifier,
        string $deviceName,
        User $admin,
        ?string $notes
    ): array
    {
        DB::beginTransaction();
        try {
            $oldApprovedDevice = $user->devices()
                ->where('status', Device::STATUS_APPROVED)
                ->where('device_identifier', '!=', $deviceIdentifier)
                ->first();

            if ($oldApprovedDevice) {
                $this->revokeOldDeviceInternal(
                    $oldApprovedDevice,
                    $user,
                    $admin,
                    'Automatically revoked due to registration of new device: ' . $deviceName
                );
            }

            $newDevice = $user->devices()->updateOrCreate(
                ['device_identifier' => $deviceIdentifier],
                [
                    'name' => $deviceName,
                    'status' => Device::STATUS_APPROVED,
                    'approved_by' => $admin->id,
                    'approved_at' => now(),
                    'admin_notes' => $notes,
                    'user_id' => $user->id,
                ]
            );

            DB::commit();
            return [
                'success' => true,
                'message' => 'Device registration successful.',
                'data' => $newDevice->fresh(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return [
                'success' => false,
                'message' => 'Internal server error.',
            ];
        }
    }

    public function updateDeviceLastUsed(Device $device): void
    {
        $device->update([
            'last_used_at' => now()
        ]);
    }

    private function revokeOldDeviceInternal(Device $device, User $targetUser, User $admin, string $reason): void
    {
        $device->status = Device::STATUS_REVOKED;
        $device->admin_notes = ($device->admin_notes ? "{$device->admin_notes}\n" : '')
            . $reason . ' by ' . $admin->name . now()->toDateTimeString();

        $device->save();
        $this->revokeTokenForDevice($device, $targetUser);
    }

    private function revokeTokenForDevice(Device $device, ?User $user = null): void
    {
        $targetUser = $user ?? $device->user;
        if ($targetUser) {
            $tokenName = "auth_token_user_{$targetUser->id}_device_{$device->id}";
            $targetUser->tokens()->where('name', $tokenName)->delete();
        }
    }
}
