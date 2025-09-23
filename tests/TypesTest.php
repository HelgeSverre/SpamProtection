<?php

declare(strict_types=1);

namespace Helge\SpamProtection\Tests;

use Helge\SpamProtection\Types;
use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{
    public function testEmailEnumValue(): void
    {
        $this->assertEquals('email', Types::EMAIL->value);
    }

    public function testUsernameEnumValue(): void
    {
        $this->assertEquals('username', Types::USERNAME->value);
    }

    public function testIPEnumValue(): void
    {
        $this->assertEquals('ip', Types::IP->value);
    }

    public function testEnumCases(): void
    {
        $cases = Types::cases();
        $this->assertCount(3, $cases);
        $this->assertContains(Types::EMAIL, $cases);
        $this->assertContains(Types::USERNAME, $cases);
        $this->assertContains(Types::IP, $cases);
    }
}
