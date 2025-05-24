<?php

namespace App\Exceptions;

use App\Libs\HttpStatusCode;
use Exception;
use Throwable;

class UnauthorizedDeviceException extends Exception
{
    protected $message = 'Unauthorized device.';
    protected $code = HttpStatusCode::FORBIDDEN;

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        if (empty($message)) {
            $message = $this->message;
        }

        parent::__construct($message, $code, $previous);
    }
}
