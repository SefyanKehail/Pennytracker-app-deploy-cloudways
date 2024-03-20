<?php

namespace App\Exceptions;

class RateLmitException extends \RuntimeException
{
    public function __construct(
    string        $message = "Too Many Requests",
    int           $code = 429,
    ?\Throwable    $previous = null
) {
    parent::__construct($message, $code, $previous);
}
}