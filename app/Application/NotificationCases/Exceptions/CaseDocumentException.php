<?php

namespace App\Application\NotificationCases\Exceptions;

use RuntimeException;

final class CaseDocumentException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message, public readonly int $httpStatus = 422)
    {
        parent::__construct($message);
    }
}
