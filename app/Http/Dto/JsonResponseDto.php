<?php

namespace App\Http\Dto;

use App\Libs\HttpStatusCode;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

readonly class JsonResponseDto
{
    /**
     * @param bool $success Whether the operation was successful
     * @param string|null $message Human-readable message
     * @param mixed $data The data to be returned
     * @param int $status HTTP status code
     */
    public function __construct(
        public bool    $success,
        public ?string $message = null,
        public mixed   $data = null,
        public int     $status = HttpStatusCode::OK
    ) {
    }

    /**
     * Create a success response
     *
     * @param mixed $data The data to be returned
     * @param string|null $message Human-readable message
     * @param int $status HTTP status code
     * @return self
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = HttpStatusCode::OK
    ): self {
        return new self(
            success: true,
            message: $message,
            data: $data,
            status: $status
        );
    }

    /**
     * Create an error response
     *
     * @param string $message Human-readable error message
     * @param mixed $data Additional error data
     * @param int $status HTTP status code
     * @return self
     */
    public static function error(
        string $message,
        mixed $data = null,
        int $status = HttpStatusCode::BAD_REQUEST
    ): self {
        return new self(
            success: false,
            message: $message,
            data: $data,
            status: $status
        );
    }

    /**
     * Convert the DTO to a Collection
     *
     * @return Collection
     */
    public function toCollection(): Collection
    {
        $result = collect(['success' => $this->success]);

        if ($this->message !== null) {
            $result->put('message', $this->message);
        }

        if ($this->data !== null) {
            if ($this->data instanceof AnonymousResourceCollection) {
                $result->put('data', $this->data);
            } else if ($this->data instanceof ResourceCollection) {
                $resourceCollection = $this->data->toArray(request());
                $result->put('data', $resourceCollection['data']);
                $result->put('meta', $resourceCollection['meta']);
                $result->put('links', $resourceCollection['links']);
            }
        }

        return $result;
    }

    /**
     * Convert the DTO to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->toCollection()->toArray();
    }
}
