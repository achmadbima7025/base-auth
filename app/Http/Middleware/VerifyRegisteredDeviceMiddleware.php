<?php

namespace App\Http\Middleware;

use App\Models\Device;
use App\Services\Auth\DeviceService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifyRegisteredDeviceMiddleware
{
    public function __construct(protected DeviceService $deviceService)
    {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
            $deviceIdentifier = $request->header('X-Device-ID');

            if (!$deviceIdentifier) {
                return response()->json(['message' => 'Device ID header (X-Device-ID) is missing.)'], 400);
            }

            $device = $this->deviceService->getDeviceForUserByIdentifier($user, $deviceIdentifier);

            if (!$device) {
                return response()->json(['message' => 'This device is not recognized for your account.'], 400);
            }

            if (!$device->isApproved()) {
                $message = 'Access from this device is not approved.';
                if ($device->status === Device::STATUS_PENDING) {
                    $message = 'This device is pending admin approval.';
                } elseif ($device->status === Device::STATUS_REJECTED) {
                    $message = 'Approval for this device has been rejected.';
                } elseif ($device->status === Device::STATUS_REVOKED) {
                    $message = 'Access for this device has been revoked.';
                }

                return response()->json(['message' => $message], 403);
            }

            $this->deviceService->updateDeviceLastUsed($device);
        }

        return $next($request);
    }
}
