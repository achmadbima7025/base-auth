<?php

namespace App\Services\Auth;

use App\Exceptions\UnauthorizedDeviceException;
use App\Models\Device;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make(Str::random(8)),
        ]);
        $user->assignRole($data['role']);

        return $user->load('roles');
    }

    /**
     * @throws AuthenticationException
     * @throws UnauthorizedDeviceException
     */
    public function login(array $credentials, ?string $deviceIdentifier = null, ?string $deviceName = null, ?string $ipAddress = null): array
    {
        $user = User::where('email', $credentials['email'])->first();
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        $device = $user->devices()->firstOrNew(
            ['device_identifier' => $deviceIdentifier],
            [
                'name' => $deviceName ?: 'Unknown Device ('. now()->toDateTimeString() . ')',
                'status' => Device::STATUS_PENDING,
                'last_login_ip' => $ipAddress ?: '127.0.0.1',
            ]
        );

        if (!$device->exists) {
            $device->save();
            throw new UnauthorizedDeviceException('User has no registered devices, new device has been registered and is waiting for admin approval.');
        }

        if (!$device->isApproved()) {
            $message = $this->getDeviceStatusMessage($device);
            throw new UnauthorizedDeviceException($message);
        }

        $device->last_login_ip = $ipAddress ?: '127.0.0.1';
        $device->last_login_at = now();
        $device->save();

        $tokenName = "auth_token_user_{$user->id}_device_{$device->id}";
        $user->tokens()->where('name', $tokenName)->delete();

        $token = $user->createToken($tokenName)->plainTextToken;

        return [
            'user' => $user,
            'device' => $device,
            'access_token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }

    public function getUserDetails(User $user): User
    {
        return $user->load('devices', 'roles');
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
}
