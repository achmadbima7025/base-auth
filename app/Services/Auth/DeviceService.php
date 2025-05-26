<?php

namespace App\Services\Auth;

use App\Exceptions\DeviceNotFoundException;
use App\Exceptions\InternalServerErrorException;
use App\Http\Dto\JsonResponseDto;
use App\Models\Device;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeviceService
{
    /**
     * @throws ModelNotFoundException
     * @throws InternalServerErrorException
     */
    public function getDeviceForUserByIdentifier(int $userId, string $deviceIdentifier): ?Device
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                throw new ModelNotFoundException('User not found.');
            }

            return $user->devices()
                ->where('device_identifier', $deviceIdentifier)
                ->first();
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error("Error getting device for user: {$e->getMessage()}");
            throw new InternalServerErrorException('Failed to retrieve device information.');
        }
    }

    /**
     * @throws ModelNotFoundException
     * @throws InternalServerErrorException
     */
    public function listUserDevices(int $userId): Collection
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                throw new ModelNotFoundException('User not found.');
            }

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
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error("Error listing user devices: {$e->getMessage()}");
            throw new InternalServerErrorException('Failed to retrieve user devices.');
        }
    }

    public function listAllDevicesFiltered(?string $status = null, ?int $perPage = 10): LengthAwarePaginator
    {
        $query = Device::with(['user:id,name,email', 'approver:id,name']);

        if (isset($status)) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * @throws DeviceNotFoundException
     */
    public function getDeviceDetails($deviceId): Device
    {
        $device = Device::find($deviceId);

        if (!$device) {
            throw new DeviceNotFoundException();
        }

        return $device->load(['user:id,name,email', 'approver:id,name']);
    }

    /**
     * @throws InternalServerErrorException
     * @throws DeviceNotFoundException
     * @throws Throwable
     */
    public function approveDevice(int $deviceId, User $admin, ?string $notes): Device
    {
        $device = Device::find($deviceId);

        if (!$device) {
            throw new DeviceNotFoundException('Device not found.');
        }

        DB::beginTransaction();
        try {
            $targetUser = $device->user;
            if (!$targetUser) {
                $device->load('user');
                $targetUser = $device->user;

                if (!$targetUser) {
                    Log::critical("DeviceManagementService: User relationship is null for UserDevice ID: {$device->id}. Cannot proceed with approval.");
                    throw new DeviceNotFoundException('Device does not belong to any user.');
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
            return $device->fresh();
        } catch (DeviceNotFoundException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            throw new InternalServerErrorException();
        }
    }

    /**
     * @throws InternalServerErrorException
     * @throws DeviceNotFoundException
     */
    public function rejectDevice(int $deviceId, User $admin, string $notes): Device
    {
        $device = Device::find($deviceId);
        if (!$device) {
            throw new DeviceNotFoundException('Device not found.');
        }

        try {
            $device->status = Device::STATUS_REJECTED;
            $device->rejected_by = $admin->id;
            $device->rejected_at = now();
            $device->admin_notes = $notes;
            $device->save();

            return $device->fresh(['user']);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new InternalServerErrorException();
        }
    }

    /**
     * @throws InternalServerErrorException
     * @throws DeviceNotFoundException
     */
    public function revokeDevice(int $deviceId, User $admin, ?string $notes): Device
    {
        $device = Device::find($deviceId);

        if (!$device) {
            throw new DeviceNotFoundException('Device not found.');
        }

        try {
            $device->status = Device::STATUS_REVOKED;
            $device->admin_notes = $notes ?: "Device revoked by admin {$admin->name}";
            $device->save();

            $this->revokeTokenForDevice($device);

            return $device->fresh(['user']);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new InternalServerErrorException();
        }
    }

    /**
     * @throws InternalServerErrorException|Throwable
     */
    public function registerDeviceForUserByAdmin(
        int $userId,
        string $deviceIdentifier,
        string $deviceName,
        User $admin,
        ?string $notes
    ): Device
    {
        DB::beginTransaction();
        try {
            $user = User::find($userId);

            if (!$user) {
                throw new ModelNotFoundException('User not found.');
            }

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
            return $newDevice->fresh(['user']);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            throw new Exception('Failed to register device.');
        }
    }

    /**
     * @throws InternalServerErrorException
     */
    public function updateDeviceLastUsed(Device $device): void
    {
        try {
            $device->update([
                'last_used_at' => now()
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new InternalServerErrorException('Failed to update device last used timestamp.');
        }
    }

    /**
     * @throws InternalServerErrorException
     */
    private function revokeOldDeviceInternal(Device $device, User $targetUser, User $admin, string $reason): void
    {
        try {
            $device->status = Device::STATUS_REVOKED;
            $device->admin_notes = ($device->admin_notes ? "{$device->admin_notes}\n" : '')
                . $reason . ' by ' . $admin->name . now()->toDateTimeString();

            $device->save();
            $this->revokeTokenForDevice($device, $targetUser);
        } catch (Exception $e) {
            Log::error("Failed to revoke old device: {$e->getMessage()}");
            throw new InternalServerErrorException('Failed to revoke old device.');
        }
    }

    /**
     * @throws InternalServerErrorException
     */
    private function revokeTokenForDevice(Device $device, ?User $user = null): void
    {
        try {
            $targetUser = $user ?? $device->user;
            if ($targetUser) {
                $tokenName = "auth_token_user_{$targetUser->id}_device_{$device->id}";
                $targetUser->tokens()->where('name', $tokenName)->delete();
            }
        } catch (Exception $e) {
            Log::error("Failed to revoke token for device: {$e->getMessage()}");
            throw new InternalServerErrorException('Failed to revoke authentication token.');
        }
    }
}
