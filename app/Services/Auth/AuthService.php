<?php

namespace App\Services\Auth;

use App\Http\Dto\JsonResponseDto;
use App\Http\Resources\DeviceResource;
use App\Http\Resources\UserResource;
use App\Libs\HttpStatusCode;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function register(array $data): JsonResponseDto
    {
        $user = new User($data);
        $user->role = 'user';
        $user->password = str()->random(8);

        try {
            $user->save();

            return JsonResponseDto::success(
                data: new UserResource($user),
                message: 'Registered successfully.',
                status: HttpStatusCode::CREATED,
            );
        } catch (\Exception $e) {
            Log::error('Registration Error: ' . $e->getMessage());
            return JsonResponseDto::error(
                message: 'Internal server error.',
                status: HttpStatusCode::INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function login(array $credentials, string $deviceIdentifier, ?string $deviceName = null, string $ipAddress): JsonResponseDto
    {
        $user = User::where('email', $credentials['email'])->first();
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return JsonResponseDto::error(
                message: 'Email or password given is incorrect.',
                status: HttpStatusCode::UNAUTHORIZED,
            );
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

            return JsonResponseDto::success(
                data: new UserResource($user->load('devices')),
                message: 'Device registration request received. Please wait for admin approval.',
                status: HttpStatusCode::CREATED,
            );
        }

        if (!$device->isApproved()) {
            $message = $this->getDeviceStatusMessage($device);

            return JsonResponseDto::error(
                message: $message,
                status: HttpStatusCode::FORBIDDEN,
            );
        }

        $device->last_login_ip = $ipAddress;
        $device->last_login_at = now();
        $device->save();

        $tokenName = "auth_token_user_{$user->id}_device_{$device->id}";
        $user->tokens()->where('name', $tokenName)->delete();

        $token = $user->createToken($tokenName)->plainTextToken;

        return JsonResponseDto::success(
            data: [
                'user' => new UserResource($user),
                'device' => new DeviceResource($device),
                'access_token' => $token
            ],
            message: 'Logged in successfully.',
        );
    }

    public function logout(User $user): JsonResponseDto
    {
        $user->tokens()->delete();

        return JsonResponseDto::success(message: 'Logged out successfully.');
    }

    public function getUserDetails(User $user): JsonResponseDto
    {
        return JsonResponseDto::success(
            data: new UserResource($user->load('devices')),
            message: 'User details retrieved successfully.',
        );
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
