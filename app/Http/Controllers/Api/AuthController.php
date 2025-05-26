<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\UnauthorizedDeviceException;
use App\Http\Controllers\Controller;
use App\Http\Dto\JsonResponseDto;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\DeviceResource;
use App\Http\Resources\UserResource;
use App\Libs\HttpStatusCode;
use App\Services\Auth\AuthService;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AuthController handles user authentication operations
 */
class AuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    /**
     * Register a new user
     * Registers a new user with the provided data. Requires admin privileges.
     */
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $result = $this->authService->register($data);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully.',
            'data' => new UserResource($result),
        ], HttpStatusCode::CREATED);
    }

    /**
     * Authenticate user and get token
     * Logs in a user with the provided credentials and returns an access token
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        $deviceIdentifier = $request->header('X-Device-ID');
        $ipAddress = $request->ip();

        try {
            $result = $this->authService->login(
                credentials: $credentials,
                deviceIdentifier: $deviceIdentifier,
                deviceName: $request->get('device_name'),
                ipAddress: $ipAddress,
            );

            $data = [
                'user' => new UserResource($result['user']),
                'device' => new DeviceResource($result['device']),
                'access_token' => $result['access_token'],
            ];

            return response()->json([
                'success' => true,
                'message' => 'User logged in successfully.',
                'data' => $data,
            ]);
        } catch (AuthenticationException|UnauthorizedDeviceException $e) {
            $responseData = JsonResponseDto::error(
                message: $e->getMessage(),
                status: HttpStatusCode::UNAUTHORIZED,
            );
            return $this->sendResponse($responseData);
        }
    }

    /**
     * Logout user
     * Logs out the user by invalidating their access token
     */
    public function logout(Request $request)
    {
        $this->authService->logout($request->user());
        return response()->json([
            'success' => true,
            'message' => 'User logged out successfully.',
        ]);
    }

    /**
     * Get user details
     * Retrieves details for the specified user
     */
    public function getUserDetails(Request $request)
    {
        $result = $this->authService->getUserDetails($request->user());

        return response()->json([
            'success' => true,
            'data' => new UserResource($result),
        ]);
    }
}
