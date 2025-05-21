<?php

namespace App\Http\Middleware;

use App\Http\Dto\JsonResponseDto;
use App\Libs\HttpStatusCode;
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
                return response()->json(
                    JsonResponseDto::error(
                        message: 'Device ID header (X-Device-ID) is missing.)',
                        status: HttpStatusCode::BAD_REQUEST
                    )->toArray(),
                    HttpStatusCode::BAD_REQUEST
                );
            }

            $device = $this->deviceService->getDeviceForUserByIdentifier($user, $deviceIdentifier);

            if (!$device) {
                $responseData = JsonResponseDto::error(
                    message: 'This device is not recognized for your account.',
                    status: HttpStatusCode::BAD_REQUEST
                );

                return response()->json($responseData->toArray(), $responseData->status);
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

                $responseData = JsonResponseDto::error(
                    message: $message,
                    status: HttpStatusCode::FORBIDDEN,
                );

                return response()->json($responseData->toArray(), $responseData->status);
            }

            $this->deviceService->updateDeviceLastUsed($device);
        }

        return $next($request);
    }
}
