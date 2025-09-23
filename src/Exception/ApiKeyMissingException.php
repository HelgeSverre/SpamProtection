<?php

declare(strict_types=1);

namespace Helge\SpamProtection\Exception;

use Exception;
use Throwable;

/**
 * Thrown when an API key is required but not provided.
 */
class ApiKeyMissingException extends Exception
{
    public function __construct(int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(
            'To submit a spam report you need an API Key',
            $code,
            $previous
        );
    }
}