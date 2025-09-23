<?php

declare(strict_types=1);

use Helge\SpamProtection\Exception\ApiKeyMissingException;
use Helge\SpamProtection\SpamProtection;

beforeEach(function () {
    $this->spamProtection = new SpamProtection();
});

test('constructor with default values')
    ->tap(function () {
        $sp = new SpamProtection();
        expect($sp->getFrequencyThreshold())->toBe(SpamProtection::THRESHOLD_STRICT);
        expect($sp->getAllowTorNodes())->toBeFalse();
        expect($sp->getApiKey())->toBeNull();
    });

test('constructor with custom values')
    ->tap(function () {
        $sp = new SpamProtection(
            SpamProtection::THRESHOLD_HIGH,
            SpamProtection::TOR_DISALLOW,
            'test-api-key',
            SpamProtection::CONFIDENCE_MEDIUM
        );

        expect($sp->getFrequencyThreshold())->toBe(SpamProtection::THRESHOLD_HIGH);
        expect($sp->getAllowTorNodes())->toBeFalse();
        expect($sp->getApiKey())->toBe('test-api-key');
    });

test('setters and getters')
    ->tap(function () {
        $this->spamProtection->setFrequencyThreshold(SpamProtection::THRESHOLD_LOW);
        expect($this->spamProtection->getFrequencyThreshold())->toBe(SpamProtection::THRESHOLD_LOW);

        $this->spamProtection->setAllowTorNodes(true);
        expect($this->spamProtection->getAllowTorNodes())->toBeTrue();

        $this->spamProtection->setApiKey('new-api-key');
        expect($this->spamProtection->getApiKey())->toBe('new-api-key');
    });

test('threshold constants')
    ->tap(function () {
        expect(SpamProtection::THRESHOLD_STRICT)->toBe(1);
        expect(SpamProtection::THRESHOLD_HIGH)->toBe(3);
        expect(SpamProtection::THRESHOLD_MEDIUM)->toBe(5);
        expect(SpamProtection::THRESHOLD_LOW)->toBe(10);
    });

test('confidence constants')
    ->tap(function () {
        expect(SpamProtection::CONFIDENCE_STRICT)->toBe(99);
        expect(SpamProtection::CONFIDENCE_HIGH)->toBe(80);
        expect(SpamProtection::CONFIDENCE_MEDIUM)->toBe(40);
        expect(SpamProtection::CONFIDENCE_LOW)->toBe(10);
    });

test('tor constants')
    ->tap(function () {
        expect(SpamProtection::TOR_ALLOW)->toBeTrue();
        expect(SpamProtection::TOR_DISALLOW)->toBeFalse();
    });

test('submit report without api key throws exception')
    ->throws(ApiKeyMissingException::class, 'To submit a spam report you need an API Key')
    ->tap(function () {
        $this->spamProtection->submitReport('spammer', '127.0.0.1', 'spam evidence', 'spam@example.com');
    });

test('check IP with valid IP')
    ->group('integration')
    ->skip('Integration test - requires network access to StopForumSpam API');

test('check email with valid email')
    ->group('integration')
    ->skip('Integration test - requires network access to StopForumSpam API');

test('check username with valid username')
    ->group('integration')
    ->skip('Integration test - requires network access to StopForumSpam API');
