<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DeviceNotFoundException;
use App\Exceptions\InternalServerErrorException;
use App\Http\Controllers\Controller;
use App\Http\Dto\JsonResponseDto;
use App\Http\Requests\Device\ApproveDeviceRequest;
use App\Http\Requests\Device\ManualDeviceRegistrationRequest;
use App\Http\Requests\Device\RejectDeviceRequest;
use App\Http\Requests\Device\RevokeDeviceRequest;
use App\Http\Resources\DeviceCollection;
use App\Http\Resources\DeviceResource;
use App\Libs\HttpStatusCode;
use App\Models\Device;
use App\Services\Auth\DeviceService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * DeviceController handles device management operations
 */
class DeviceController extends Controller
{
    public function __construct(protected DeviceService $service)
    {
    }

    /**
     * Get device by identifier for a user
     * Retrieves a specific device for a user by its identifier
     */
    public function getDeviceForUserByIdentifier(int $userId, string $identifier)
    {
        if (!is_numeric($userId)) {
            return $this->sendResponse(JsonResponseDto::error(message: 'User ID is invalid.', status: HttpStatusCode::NOT_FOUND));
        }

        try {
            $result = $this->service->getDeviceForUserByIdentifier($userId, $identifier);

            if ($result === null) {
                // When device is not found, return 404
                $response = JsonResponseDto::error('Device not found.', status: HttpStatusCode::NOT_FOUND);
                return $this->sendResponse($response);
            }

            return response()->json([
                'success' => true,
                'data' => new DeviceResource($result),
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->sendResponse(JsonResponseDto::error($e->getMessage(), HttpStatusCode::NOT_FOUND));
        } catch (Exception $e) {
            return $this->sendResponse(JsonResponseDto::error($e->getMessage(), HttpStatusCode::INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * List devices for a user
     * Lists all devices belonging to a specific user
     */
    public function listUserDevices(int $userId)
    {
        if (!is_numeric($userId)) {
            return $this->sendResponse(JsonResponseDto::error(message: 'User ID is invalid.'));
        }

        try {
            $result = $this->service->listUserDevices($userId);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->sendResponse(JsonResponseDto::error($e->getMessage(), HttpStatusCode::NOT_FOUND));
        } catch (Exception $e) {
            return $this->sendResponse(JsonResponseDto::error($e->getMessage(), HttpStatusCode::INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * List all devices
     * Lists all devices with optional status filtering and pagination
     */
    public function listAllDevice(Request $request)
    {
        try {
            $status = $request->get('status');
            $result = $this->service->listAllDevicesFiltered($status, $request->get('perPage'));

            return response()->json([
                'success' => true,
                'data' => new DeviceCollection($result),
            ]);
        } catch (Exception $e) {
            return $this->sendResponse(JsonResponseDto::error($e->getMessage(), HttpStatusCode::INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Get device details
     * Retrieves details for a specific device
     */
    public function getDetailDevice(int $deviceId)
    {
        if (!is_numeric($deviceId)) {
            return $this->sendResponse(JsonResponseDto::error(message: 'Device ID is invalid.'));
        }

        try {
            $result = $this->service->getDeviceDetails($deviceId);
            return response()->json([
                'success' => true,
                'data' => new DeviceResource($result),
            ]);
        } catch (DeviceNotFoundException $e) {
            return $this->sendResponse(JsonResponseDto::error(message: $e->getMessage(), status: HttpStatusCode::NOT_FOUND));
        }
    }

    /**
     * Approve a device
     * Approves a device for use, automatically revoking any previously approved device for the same user. Requires admin privileges.
     */
    public function approveDevice(ApproveDeviceRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $admin = Auth::user();
            $deviceId = $request->route('deviceId');

            $result = $this->service->approveDevice(
                deviceId: $deviceId,
                admin: $admin,
                notes: $validatedData['notes']
            );

            return response()->json([
                'success' => true,
                'message' => 'Device approved successfully.',
                'data' => new DeviceResource($result),
            ]);
        } catch (DeviceNotFoundException $e) {
            Log::error('DeviceNotFoundException: ' . $e->getMessage());
            return $this->sendResponse(JsonResponseDto::error(message: $e->getMessage(), status: HttpStatusCode::NOT_FOUND));
        } catch (ModelNotFoundException $e) {
            Log::error('ModelNotFoundException: ' . $e->getMessage());
            return $this->sendResponse(JsonResponseDto::error(message: 'Device not found.', status: HttpStatusCode::NOT_FOUND));
        } catch (InternalServerErrorException $e) {
            Log::error('InternalServerErrorException: ' . $e->getMessage());
            return $this->sendResponse(JsonResponseDto::error(message: $e->getMessage(), status: HttpStatusCode::INTERNAL_SERVER_ERROR));
        } catch (Throwable $e) {
            Log::error('Unexpected error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->sendResponse(JsonResponseDto::error(message: 'An unexpected error occurred.', status: HttpStatusCode::INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Reject a device
     * Rejects a device, preventing it from being used. Requires admin privileges.
     */
    public function rejectDevice(RejectDeviceRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $admin = Auth::user();
            $deviceId = $request->route('deviceId');

            $result = $this->service->rejectDevice(
                deviceId: $deviceId,
                admin: $admin,
                notes: $validatedData['notes'],
            );

            return response()->json([
                'success' => true,
                'message' => 'Device rejected successfully.',
                'data' => new DeviceResource($result),
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->sendResponse(JsonResponseDto::error(message: 'Device not found.', status: HttpStatusCode::NOT_FOUND));
        } catch (InternalServerErrorException $e) {
            return $this->sendResponse(JsonResponseDto::error(message: $e->getMessage(), status: HttpStatusCode::INTERNAL_SERVER_ERROR));
        } catch (Exception $e) {
            return $this->sendResponse(JsonResponseDto::error(message: 'An unexpected error occurred.', status: HttpStatusCode::INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Revoke a device
     * Revokes a previously approved device. Requires admin privileges.
     */
    public function revokeDevice(RevokeDeviceRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $admin = Auth::user();
            $deviceId = $request->route('deviceId');
            $notes = $validatedData['notes'];

            $result = $this->service->revokeDevice(
                deviceId: $deviceId,
                admin: $admin,
                notes: $notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Device revoked successfully.',
                'data' => new DeviceResource($result),
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->sendResponse(JsonResponseDto::error(message: 'Device not found.', status: HttpStatusCode::NOT_FOUND));
        } catch (InternalServerErrorException $e) {
            return $this->sendResponse(JsonResponseDto::error(message: $e->getMessage(), status: HttpStatusCode::INTERNAL_SERVER_ERROR));
        } catch (Exception $e) {
            return $this->sendResponse(JsonResponseDto::error(message: 'An unexpected error occurred.', status: HttpStatusCode::INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Register a device for a user by admin
     * Registers a new device for a specific user by an administrator. Requires admin privileges.
     */
    public function registerDeviceForUserByAdmin(ManualDeviceRegistrationRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $result = $this->service->registerDeviceForUserByAdmin(
                userId: $validatedData['user_id'],
                deviceIdentifier:  $validatedData['device_identifier'],
                deviceName: $validatedData['device_name'],
                admin: $request->user(),
                notes: $validatedData['notes'],
            );

            return response()->json([
                'success' => true,
                'message' => 'Device registered successfully.',
                'data' => new DeviceResource($result),
            ], HttpStatusCode::CREATED);
        } catch(ModelNotFoundException $e) {
            return $this->sendResponse(JsonResponseDto::error(message: $e->getMessage()));
        } catch (Throwable $e) {
            return $this->sendResponse(JsonResponseDto::error(message: $e->getMessage()));
        }
    }
}
