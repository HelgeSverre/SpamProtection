<?php

declare(strict_types=1);

namespace Helge\SpamProtection\Tests\Integration;

use Helge\SpamProtection\SpamProtection;
use Helge\SpamProtection\Tests\TestCase;
use Helge\SpamProtection\Types;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
final class ApiTest extends TestCase
{
    #[DataProvider('checks')]
    public function testLiveApiRespondsSuccessfully(Types $type, string $value): void
    {
        // Database contents change; exact spam decisions belong in fixture tests.
        (new SpamProtection())->check($type, $value);
        $this->addToAssertionCount(1); // A valid API response completes without an exception.
    }

    public static function checks(): array
    {
        return [
            'IP' => [Types::IP, '127.0.0.1'],
            'email' => [Types::EMAIL, 'test@example.com'],
            'username' => [Types::USERNAME, 'testuser123456'],
        ];
    }
}
