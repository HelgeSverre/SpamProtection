<?php

declare(strict_types=1);

namespace Helge\SpamProtection\Exception;

use Exception;
use Throwable;

/**
 * Thrown when the API check fails or returns an unexpected response.
 */
class ApiCheckException extends Exception
{
    public function __construct(int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('API Check Unsuccessful', $code, $previous);
    }
}
