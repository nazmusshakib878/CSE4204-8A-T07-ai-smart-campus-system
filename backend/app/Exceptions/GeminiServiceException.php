<?php

namespace App\Exceptions;

use RuntimeException;

class GeminiServiceException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('AI service is temporarily unavailable. Please try again.');
    }
}
