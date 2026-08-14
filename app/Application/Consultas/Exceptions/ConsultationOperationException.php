<?php

namespace App\Application\Consultas\Exceptions;

use RuntimeException;

final class ConsultationOperationException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
