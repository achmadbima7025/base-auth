<?php

namespace App\Exceptions;

use App\Libs\HttpStatusCode;
use Exception;
use Throwable;

class DeviceNotFoundException extends Exception
{
    protected $message = 'Device not found.';
    protected $code = HttpStatusCode::NOT_FOUND;

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        if (empty($message)) {
            $message = $this->message;
        }

        parent::__construct($message, $code, $previous);
    }
}
