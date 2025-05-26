<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Dto\JsonResponseDto;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\DeviceCollection;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Services\Auth\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct(protected UserService $service)
    {

    }

    public function listAllUsers(Request $request)
    {
        $name = $request->query('name');
        $email = $request->query('email');
        $perPage = $request->query('per_page');
        $result = $this->service->getAllUsers(
            name: $name,
            email: $email,
            perPage: $perPage,
        );
        $responseData = new UserCollection($result)->toArray(request());
        return response()->json([
            'success' => true,
            'data' => $responseData['data'],
            'meta' => $responseData['meta'],
            'links' => $responseData['links'],
        ]);
    }

    public function updateUser(UpdateUserRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $result = $this->service->updateUser($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'data' => new UserResource($result),
            ]);
        } catch (\Exception $e) {
            Log::warning($e->getMessage());
            return $this->sendResponse(JsonResponseDto::error(message: $e->getMessage()));
        }
    }
}
