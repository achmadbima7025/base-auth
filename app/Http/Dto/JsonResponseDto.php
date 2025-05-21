<?php

namespace App\Http\Dto;

use App\Libs\HttpStatusCode;
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
        $result = collect([
            'success' => $this->success,
        ]);

        // Store status separately but don't include it in the response
        $result->put('status', $this->status);

        if ($this->message !== null) {
            $result->put('message', $this->message);
        }

        if ($this->data !== null) {
            if (is_array($this->data) && !array_is_list($this->data)) {
                foreach ($this->data as $key => $value) {
                    $result->put($key, $value);
                }
            } else {
                $result->put('data', $this->data);
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
