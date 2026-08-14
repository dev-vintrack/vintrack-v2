<?php

namespace App\Application\NotificationCases\Exceptions;

use RuntimeException;

final class NonRetryableDeliveryException extends RuntimeException {}
