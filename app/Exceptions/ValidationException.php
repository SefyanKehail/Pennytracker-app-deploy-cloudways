<?php

namespace App\Exceptions;

use Throwable;

class ValidationException extends \RuntimeException
{


    public function __construct(
        public readonly array $errors,
        string        $message = "Validation Errors",
        int           $code = 422,
        ?Throwable    $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}