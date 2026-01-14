<?php

namespace PublicSquare\Payments\Exception;

class BadRequestException extends PSQException
{
    public function __construct(
        string $message = "",
        int $propagateHttpResponseCode = 400,
        int $exceptionCode = 0,
        ?\Throwable $previous = null)
    {
        parent::__construct($propagateHttpResponseCode, $message, $exceptionCode, $previous);
    }

}