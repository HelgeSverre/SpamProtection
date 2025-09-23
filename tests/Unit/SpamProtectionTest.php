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

test('check IP integration test - clean IP')
    ->group('integration')
    ->tap(function () {
        // Test with localhost which should never be spam
        $result = $this->spamProtection->checkIP('127.0.0.1');
        expect($result)->toBeFalse();
    });

test('check email integration test - clean email')
    ->group('integration')
    ->tap(function () {
        // Test with a unique email that should not be spam
        $result = $this->spamProtection->checkEmail('clean.user.12345@gmail.com');
        expect($result)->toBeFalse();
    });

test('check username integration test - test username')
    ->group('integration')
    ->tap(function () {
        // Test with a test username that should not be spam
        $result = $this->spamProtection->checkUsername('testuser123456');
        expect($result)->toBeFalse();
    });

test('check with various threshold levels')
    ->group('integration')
    ->tap(function () {
        // Test different threshold configurations
        $strictProtector = new SpamProtection(SpamProtection::THRESHOLD_STRICT);
        $result = $strictProtector->checkIP('8.8.8.8'); // Google DNS, should be clean
        expect($result)->toBeFalse();

        $lowProtector = new SpamProtection(SpamProtection::THRESHOLD_LOW);
        $result = $lowProtector->checkIP('8.8.8.8');
        expect($result)->toBeFalse();
    });

test('API connectivity and response format')
    ->group('integration')
    ->tap(function () {
        // This test mainly verifies API connectivity and proper response handling
        $result = $this->spamProtection->checkIP('127.0.0.1');
        // If we get here, the API is working and returned a valid response
        expect($result)->toBeBool();
        expect($result)->toBeFalse(); // localhost should not be spam
    });

test('test with known spam data')
    ->group('integration')
    ->tap(function () {
        // Let's test with test@example.com which we know is flagged
        $result = $this->spamProtection->checkEmail('test@example.com');
        expect($result)->toBeTrue(); // This should be flagged as spam
    });

test('comprehensive integration test with real data')
    ->group('integration')
    ->tap(function () {
        // Test multiple scenarios with actual data

        // Clean data that should not be flagged
        expect($this->spamProtection->checkIP('127.0.0.1'))->toBeFalse();
        expect($this->spamProtection->checkIP('8.8.8.8'))->toBeFalse(); // Google DNS
        expect($this->spamProtection->checkEmail('clean.user.12345@gmail.com'))->toBeFalse();
        expect($this->spamProtection->checkUsername('cleanuser12345'))->toBeFalse();

        // Known spam data
        expect($this->spamProtection->checkEmail('test@example.com'))->toBeTrue();

        // Test with your provided examples (they appear to be clean now)
        expect($this->spamProtection->checkIP('91.186.18.61'))->toBeFalse();
        expect($this->spamProtection->checkEmail('g2fsehis5e@mail.ru'))->toBeFalse();
        expect($this->spamProtection->checkUsername('MariFoogwoogy'))->toBeFalse();

        // Test threshold behavior with known spam
        $strictProtector = new SpamProtection(SpamProtection::THRESHOLD_STRICT);
        $highProtector = new SpamProtection(SpamProtection::THRESHOLD_HIGH);

        // test@example.com has frequency 6, so both should flag it
        expect($strictProtector->checkEmail('test@example.com'))->toBeTrue();
        expect($highProtector->checkEmail('test@example.com'))->toBeTrue();
    });
