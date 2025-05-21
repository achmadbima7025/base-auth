<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $result = $this->authService->register($data);

        return $this->sendJsonResponse($result);
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        $deviceIdentifier = $request->header('X-Device-ID');
        $ipAddress = $request->ip();

        $result = $this->authService->login(
            credentials: $credentials,
            deviceIdentifier: $deviceIdentifier,
            ipAddress:  $ipAddress
        );

        return $this->sendJsonResponse($result);
    }

    public function logout(Request $request)
    {
        return $this->sendJsonResponse($this->authService->logout($request->user()));
    }

    public function getUserDetails(Request $request)
    {
        $result = $this->authService->getUserDetails($request->user());

        return $this->sendJsonResponse($result);
    }
}
