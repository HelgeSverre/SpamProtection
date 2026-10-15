<?php

declare(strict_types=1);

namespace Helge\SpamProtection\Tests\Unit;

use Helge\SpamProtection\Exception\ApiKeyMissingException;
use Helge\SpamProtection\SpamProtection;
use Helge\SpamProtection\Tests\TestCase;

final class SpamProtectionTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $sp = new SpamProtection();
        self::assertSame(SpamProtection::THRESHOLD_STRICT, $sp->getFrequencyThreshold());
        self::assertFalse($sp->getAllowTorNodes());
        self::assertNull($sp->getApiKey());
    }

    public function testCustomConstructor(): void
    {
        $sp = new SpamProtection(SpamProtection::THRESHOLD_HIGH, SpamProtection::TOR_DISALLOW, 'test-api-key', SpamProtection::CONFIDENCE_MEDIUM);
        self::assertSame(SpamProtection::THRESHOLD_HIGH, $sp->getFrequencyThreshold());
        self::assertFalse($sp->getAllowTorNodes());
        self::assertSame('test-api-key', $sp->getApiKey());
    }

    public function testNullConstructorOptions(): void
    {
        $sp = new SpamProtection(null, null, null, null);
        self::assertSame(SpamProtection::THRESHOLD_STRICT, $sp->getFrequencyThreshold());
        self::assertFalse($sp->getAllowTorNodes());
        self::assertNull($sp->getApiKey());
    }

    public function testSettersAndGetters(): void
    {
        $sp = new SpamProtection();
        $sp->setFrequencyThreshold(SpamProtection::THRESHOLD_LOW);
        self::assertSame(SpamProtection::THRESHOLD_LOW, $sp->getFrequencyThreshold());
        $sp->setAllowTorNodes(true);
        self::assertTrue($sp->getAllowTorNodes());
        $sp->setAllowTorNodes(null);
        self::assertFalse($sp->getAllowTorNodes());
        $sp->setApiKey('new-api-key');
        self::assertSame('new-api-key', $sp->getApiKey());
        $sp->setApiKey(null);
        self::assertNull($sp->getApiKey());
    }

    public function testSubmissionWithoutKey(): void
    {
        $this->expectException(ApiKeyMissingException::class);
        $this->expectExceptionMessage('To submit a spam report you need an API Key');
        (new SpamProtection())->submitReport('spammer', '127.0.0.1', 'spam evidence', 'spam@example.com');
    }
}
