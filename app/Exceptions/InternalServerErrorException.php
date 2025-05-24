<?php

namespace App\Exceptions;

use App\Libs\HttpStatusCode;
use Exception;
use Throwable;

class InternalServerErrorException extends Exception
{
    protected $message = 'Internal server error.';
    protected $code = HttpStatusCode::INTERNAL_SERVER_ERROR;

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        if (empty($message)) {
            $message = $this->message;
        }

        parent::__construct($message, $code, $previous);
    }
}
