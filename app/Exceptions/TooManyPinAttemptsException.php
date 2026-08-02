<?php

namespace App\Exceptions;

use RuntimeException;

class TooManyPinAttemptsException extends RuntimeException
{
    public function __construct(public int $availableInSeconds)
    {
        parent::__construct("Too many incorrect PIN attempts. Try again in {$availableInSeconds} seconds.");
    }
}
