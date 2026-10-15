<?php

declare(strict_types=1);

namespace Helge\SpamProtection\Tests\Support;

use Helge\SpamProtection\SpamProtection;
use RuntimeException;

final class FakeSpamProtection extends SpamProtection
{
    public mixed $response = false;
    public ?RuntimeException $failure = null;
    public ?string $lastUrl = null;

    protected function sendRequest(string $url): mixed
    {
        $this->lastUrl = $url;
        if ($this->failure !== null) {
            throw $this->failure;
        }

        return $this->response;
    }
}
