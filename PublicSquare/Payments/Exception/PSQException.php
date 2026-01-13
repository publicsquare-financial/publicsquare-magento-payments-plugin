<?php

namespace PublicSquare\Payments\Exception;

use \RuntimeException;

class PSQException extends RuntimeException
{
    private int $propagateHttpResponseCode;

    public function __construct(int $propagateHttpResponseCode, string $message = "", int $exceptionCode = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $exceptionCode, $previous);
        $this->propagateHttpResponseCode = $propagateHttpResponseCode;
    }

    /**
     * @return int
     */
    public function getPropagateHttpResponseCode(): int
    {
        return $this->propagateHttpResponseCode;
    }
}