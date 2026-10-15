<?php

declare(strict_types=1);

namespace Helge\SpamProtection\Tests\Unit;

use Helge\SpamProtection\Tests\TestCase;
use Helge\SpamProtection\Types;

final class TypesTest extends TestCase
{
    public function testEnumValues(): void
    {
        self::assertSame('email', Types::EMAIL->value);
        self::assertSame('username', Types::USERNAME->value);
        self::assertSame('ip', Types::IP->value);
        self::assertCount(3, Types::cases());
    }
}
