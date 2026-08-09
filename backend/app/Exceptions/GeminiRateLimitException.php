<?php

namespace App\Exceptions;

use RuntimeException;

class GeminiRateLimitException extends RuntimeException
{
    public function __construct(public readonly array $quota = [])
    {
        parent::__construct('AI is receiving too many requests right now. Please wait a moment and try again.');
    }
}
