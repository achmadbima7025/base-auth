<?php

namespace App\Http\Controllers;

use App\Http\Dto\JsonResponseDto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

abstract class Controller
{
    /**
     * Return a JSON response
     *
     * @param JsonResponseDto $data
     * @return JsonResponse
     */
    protected function sendJsonResponse(JsonResponseDto $data)
    {
        return response()->json($data->toArray(), $data->status);
    }
}
