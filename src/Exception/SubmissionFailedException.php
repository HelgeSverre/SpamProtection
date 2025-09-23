<?php

declare(strict_types=1);

namespace Helge\SpamProtection\Exception;

use Exception;
use Throwable;

/**
 * Thrown when a spam report submission fails.
 */
class SubmissionFailedException extends Exception
{
    public function __construct(int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('Submission failed.', $code, $previous);
    }
}
