<?php

declare(strict_types=1);

namespace Helge\SpamProtection\Tests;

use Helge\SpamProtection\Exception\ApiKeyMissingException;
use Helge\SpamProtection\SpamProtection;
use PHPUnit\Framework\TestCase;

class SpamProtectionTest extends TestCase
{
    private SpamProtection $spamProtection;

    protected function setUp(): void
    {
        $this->spamProtection = new SpamProtection();
    }

    public function testConstructorWithDefaultValues(): void
    {
        $sp = new SpamProtection();
        $this->assertEquals(SpamProtection::THRESHOLD_STRICT, $sp->getFrequencyThreshold());
        $this->assertFalse($sp->getAllowTorNodes());
        $this->assertNull($sp->getApiKey());
    }

    public function testConstructorWithCustomValues(): void
    {
        $sp = new SpamProtection(
            SpamProtection::THRESHOLD_HIGH,
            SpamProtection::TOR_DISALLOW,
            'test-api-key',
            SpamProtection::CONFIDENCE_MEDIUM
        );

        $this->assertEquals(SpamProtection::THRESHOLD_HIGH, $sp->getFrequencyThreshold());
        $this->assertFalse($sp->getAllowTorNodes());
        $this->assertEquals('test-api-key', $sp->getApiKey());
    }

    public function testSettersAndGetters(): void
    {
        $this->spamProtection->setFrequencyThreshold(SpamProtection::THRESHOLD_LOW);
        $this->assertEquals(SpamProtection::THRESHOLD_LOW, $this->spamProtection->getFrequencyThreshold());

        $this->spamProtection->setAllowTorNodes(true);
        $this->assertTrue($this->spamProtection->getAllowTorNodes());

        $this->spamProtection->setApiKey('new-api-key');
        $this->assertEquals('new-api-key', $this->spamProtection->getApiKey());
    }

    public function testThresholdConstants(): void
    {
        $this->assertEquals(1, SpamProtection::THRESHOLD_STRICT);
        $this->assertEquals(3, SpamProtection::THRESHOLD_HIGH);
        $this->assertEquals(5, SpamProtection::THRESHOLD_MEDIUM);
        $this->assertEquals(10, SpamProtection::THRESHOLD_LOW);
    }

    public function testConfidenceConstants(): void
    {
        $this->assertEquals(99, SpamProtection::CONFIDENCE_STRICT);
        $this->assertEquals(80, SpamProtection::CONFIDENCE_HIGH);
        $this->assertEquals(40, SpamProtection::CONFIDENCE_MEDIUM);
        $this->assertEquals(10, SpamProtection::CONFIDENCE_LOW);
    }

    public function testTorConstants(): void
    {
        $this->assertTrue(SpamProtection::TOR_ALLOW);
        $this->assertFalse(SpamProtection::TOR_DISALLOW);
    }

    public function testSubmitReportWithoutApiKeyThrowsException(): void
    {
        $this->expectException(ApiKeyMissingException::class);
        $this->expectExceptionMessage('To submit a spam report you need an API Key');

        $this->spamProtection->submitReport('spammer', '127.0.0.1', 'spam evidence', 'spam@example.com');
    }

    /**
     * @group integration
     */
    public function testCheckIPWithValidIP(): void
    {
        $this->markTestSkipped('Integration test - requires network access to StopForumSpam API');
    }

    /**
     * @group integration
     */
    public function testCheckEmailWithValidEmail(): void
    {
        $this->markTestSkipped('Integration test - requires network access to StopForumSpam API');
    }

    /**
     * @group integration
     */
    public function testCheckUsernameWithValidUsername(): void
    {
        $this->markTestSkipped('Integration test - requires network access to StopForumSpam API');
    }
}
