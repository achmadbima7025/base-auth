<?php

namespace App\Services\Auth;

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function register(array $data): array
    {
        $user = new User($data);
        $user->role = 'user';
        $user->password = str()->random(8);

        try {
            $user->save();

            return [
                'success' => true,
                'message' => 'Registered successfully.',
                'data' => $user,
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [
                'success' => false,
                'message' => 'Server error.',
            ];
        }
    }

    public function login(array $credentials, string $deviceIdentifier, ?string $deviceName, string $ipAddress): array
    {
        $user = User::where('email', $credentials['email'])->first();
        if (!$user || Hash::check($credentials['password'], $user->password)) {
            return [
                'success' => false,
                'message' => 'Incorrect email or password.',
            ];
        }

        $device = $user->devices()->firstOrNew(
            ['device_identifier' => $deviceIdentifier],
            [
                'name' => $deviceName ?: 'Unknown Device ('. now()->toDateTimeString() . ')',
                'status' => Device::STATUS_PENDING,
                'last_login_ip' => $ipAddress,
            ]
        );

        if (!$device->exists) {
            $device->save();

            return [
                'success' => false,
                'message' => 'Device registration request received. Please wait for admin approval.',
            ];
        }

        if (!$device->isApproved()) {
            $message = $this->getDeviceStatusMessage($device);

            return [
                'success' => false,
                'message' => $message,
            ];
        }

        $device->last_login_ip = $ipAddress;
        $device->last_login_at = now();
        $device->save();

        $tokenName = "auth_token_user_{$user->id}_device_{$device->id}";
        $user->tokens()->where('name', $tokenName)->delete();

        $token = $user->createToken($tokenName)->plainTextToken;

        return [
            'success' => true,
            'user' => $user->only(['id', 'name', 'email', 'role']),
            'device' => $device->only(['id', 'device_identifier', 'name', 'status']),
            'access_token' => $token,
        ];
    }

    private function getDeviceStatusMessage(Device $device): string
    {
        switch ($device->status) {
            case Device::STATUS_PENDING:
                return 'Your device is still pending admin approval.';
            case Device::STATUS_REJECTED:
                $message = 'Your device registration has been rejected.';
                if ($device->admin_notes) {
                    $message .= " Reason: {$device->admin_notes}";
                }
                return $message;
            case Device::STATUS_REVOKED:
                $message = 'Access for this device has been revoked.';
                if ($device->admin_notes) {
                    $message .= " Notes: {$device->admin_notes}";
                }
                return $message;
            default:
                return 'Device access denied.';
        }
    }

    public function logout(User $user): array
    {
        $user->tokens()->delete();

        return [
            'success' => true,
            'message' => 'Logged out successfully.',
        ];
    }

    public function getUserDetails(User $user): array
    {
        return [
            'success' => true,
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ];
    }
}
