<?php

namespace App\Http\Controllers;

use App\Http\Dto\JsonResponseDto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

abstract class Controller
{
    protected function sendResponse(JsonResponseDto $response)
    {
        return response()->json($response->toArray(), $response->status);
    }
}
