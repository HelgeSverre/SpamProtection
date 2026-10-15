<?php

declare(strict_types=1);

namespace Helge\SpamProtection\Tests\Unit;

use Helge\SpamProtection\Exception\ApiCheckException;
use Helge\SpamProtection\Exception\SubmissionFailedException;
use Helge\SpamProtection\Tests\Support\FakeSpamProtection;
use Helge\SpamProtection\Tests\TestCase;
use Helge\SpamProtection\Types;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

final class ApiResponseTest extends TestCase
{
    #[DataProvider('types')]
    public function testCheckTypesAndUrlEncoding(Types $type): void
    {
        $sp = new FakeSpamProtection();
        $sp->response = json_encode(['success' => 1, $type->value => ['appears' => 1, 'frequency' => 10]], JSON_THROW_ON_ERROR);
        self::assertTrue($sp->check($type, 'value +&?@example.com'));
        parse_str((string) parse_url((string) $sp->lastUrl, PHP_URL_QUERY), $query);
        self::assertSame('value +&?@example.com', $query[$type->value]);
        self::assertSame('json', $query['f']);
        self::assertArrayHasKey('notorexit', $query);
    }

    public static function types(): array
    {
        return [[Types::EMAIL], [Types::IP], [Types::USERNAME]];
    }

    #[DataProvider('wrappers')]
    public function testWrapperMethods(string $method, string $type): void
    {
        $sp = new FakeSpamProtection();
        $sp->response = json_encode(['success' => 1, $type => ['appears' => 0]], JSON_THROW_ON_ERROR);
        self::assertFalse($sp->$method('test'));
        self::assertStringContainsString('?' . $type . '=test', (string) $sp->lastUrl);
    }

    public static function wrappers(): array
    {
        return [['checkIP', 'ip'], ['checkEmail', 'email'], ['checkUsername', 'username']];
    }

    public function testAllowingTorNodes(): void
    {
        $sp = new FakeSpamProtection(allowTorNodes: true);
        $sp->response = '{"success":1,"ip":{"appears":0}}';
        self::assertFalse($sp->checkIP('127.0.0.1'));
        self::assertStringNotContainsString('notorexit', (string) $sp->lastUrl);
    }

    #[DataProvider('frequencies')]
    public function testFrequencyThreshold(int $frequency, bool $expected): void
    {
        $sp = new FakeSpamProtection(frequencyThreshold: 5);
        $sp->response = json_encode(['success' => 1, 'email' => ['appears' => 1, 'frequency' => $frequency]], JSON_THROW_ON_ERROR);
        self::assertSame($expected, $sp->checkEmail('test@example.com'));
    }

    public static function frequencies(): array
    {
        return [[4, false], [5, true], [6, true]];
    }

    #[DataProvider('confidences')]
    public function testConfidenceThreshold(float $confidence, bool $expected): void
    {
        $sp = new FakeSpamProtection(confidenceThreshold: 80);
        $sp->response = json_encode(['success' => 1, 'ip' => [
            'appears' => 1, 'frequency' => 10, 'confidence' => $confidence,
        ]], JSON_THROW_ON_ERROR);
        self::assertSame($expected, $sp->checkIP('127.0.0.1'));
    }

    public static function confidences(): array
    {
        return [[79.9, false], [80.0, true], [80.1, true]];
    }

    public function testNumericStringScores(): void
    {
        $sp = new FakeSpamProtection(confidenceThreshold: 80);
        $sp->response = '{"success":"1","email":{"appears":"1","frequency":"5","confidence":"80.5"}}';
        self::assertTrue($sp->checkEmail('test@example.com'));
    }

    public function testCleanResultsDoNotRequireScores(): void
    {
        $sp = new FakeSpamProtection(confidenceThreshold: 80);
        $sp->response = '{"success":true,"email":{"appears":false}}';
        self::assertFalse($sp->checkEmail('test@example.com'));
    }

    #[DataProvider('invalidChecks')]
    public function testInvalidChecksThrow(mixed $response, string $detail): void
    {
        $sp = new FakeSpamProtection(confidenceThreshold: 80);
        $sp->response = $response;
        $this->expectException(ApiCheckException::class);
        $this->expectExceptionMessage($detail);
        $sp->checkEmail('test@example.com');
    }

    public static function invalidChecks(): array
    {
        return [
            'transport returned false' => [false, 'Empty API response'],
            'empty body' => ['', 'Empty API response'],
            'malformed JSON' => ['{', 'Invalid JSON response'],
            'JSON null' => ['null', 'Missing API success status'],
            'missing success' => ['{}', 'Missing API success status'],
            'API rejection' => ['{"success":0,"error":"rate limit exceeded"}', 'rate limit exceeded'],
            'rejection without message' => ['{"success":0}', 'API rejected the request'],
            'invalid success' => ['{"success":"1junk"}', 'API rejected the request'],
            'missing type' => ['{"success":1}', 'Missing or invalid email result'],
            'invalid type' => ['{"success":1,"email":false}', 'Missing or invalid email result'],
            'missing appears' => ['{"success":1,"email":{}}', 'Missing or invalid email result'],
            'invalid appears' => ['{"success":1,"email":{"appears":2}}', 'Missing or invalid email result'],
            'missing frequency' => ['{"success":1,"email":{"appears":1}}', 'Missing or invalid report frequency'],
            'invalid frequency' => ['{"success":1,"email":{"appears":1,"frequency":"oops"}}', 'Missing or invalid report frequency'],
            'negative frequency' => ['{"success":1,"email":{"appears":1,"frequency":-1}}', 'Missing or invalid report frequency'],
            'missing confidence' => ['{"success":1,"email":{"appears":1,"frequency":5}}', 'Missing or invalid confidence score'],
            'invalid confidence' => ['{"success":1,"email":{"appears":1,"frequency":5,"confidence":101}}', 'Missing or invalid confidence score'],
        ];
    }

    public function testJsonFailureRetainsCause(): void
    {
        $sp = new FakeSpamProtection();
        $sp->response = '{';
        try {
            $sp->checkEmail('test@example.com');
            self::fail('Expected an API exception');
        } catch (ApiCheckException $e) {
            self::assertInstanceOf(JsonException::class, $e->getPrevious());
        }
    }

    public function testTransportFailuresRetainCauseAndCode(): void
    {
        $sp = new FakeSpamProtection(apiKey: 'test-key');
        $sp->failure = new RuntimeException('API returned HTTP 429', 429);
        foreach (['checkEmail' => ApiCheckException::class, 'submitReport' => SubmissionFailedException::class] as $method => $exception) {
            try {
                if ($method === 'checkEmail') {
                    $sp->checkEmail('test@example.com');
                } else {
                    $sp->submitReport('user', '127.0.0.1', 'evidence', 'test@example.com');
                }
                self::fail('Expected a transport exception');
            } catch (ApiCheckException|SubmissionFailedException $e) {
                self::assertInstanceOf($exception, $e);
                self::assertSame(429, $e->getCode());
                self::assertSame($sp->failure, $e->getPrevious());
                self::assertStringContainsString('HTTP 429', $e->getMessage());
            }
        }
    }

    #[DataProvider('successfulSubmissions')]
    public function testSuccessfulSubmission(string $response): void
    {
        $sp = new FakeSpamProtection(apiKey: 'test-key');
        $sp->response = $response;
        self::assertTrue($sp->submitReport('a & b', '127.0.0.1', 'some + evidence', 'test@example.com'));
        parse_str((string) parse_url((string) $sp->lastUrl, PHP_URL_QUERY), $query);
        self::assertStringStartsWith('https://www.stopforumspam.com/add.php?', (string) $sp->lastUrl);
        self::assertSame([
            'username' => 'a & b', 'ip_addr' => '127.0.0.1', 'evidence' => 'some + evidence',
            'email' => 'test@example.com', 'api_key' => 'test-key', 'f' => 'json',
        ], $query);
    }

    public static function successfulSubmissions(): array
    {
        return [['{"success":1}'], ['{"success":"1"}'], ['{"success":true}'], ['<p>data submitted successfully</p>']];
    }

    #[DataProvider('failedSubmissions')]
    public function testFailedSubmission(mixed $response, string $detail): void
    {
        $sp = new FakeSpamProtection(apiKey: 'test-key');
        $sp->response = $response;
        $this->expectException(SubmissionFailedException::class);
        $this->expectExceptionMessage($detail);
        $sp->submitReport('user', '127.0.0.1', 'evidence', 'test@example.com');
    }

    public static function failedSubmissions(): array
    {
        return [
            [false, 'Empty API response'], ['', 'Empty API response'],
            ['{"success":0,"error":"could not validate api key"}', 'could not validate api key'],
            ['{"success":0,"error":[]}', 'API rejected the request'],
            ['{"success":"1junk"}', 'API rejected the request'],
            ['<html>server error</html>', 'Unexpected API response'],
            ['not data submitted successfully', 'Unexpected API response'],
        ];
    }

    public function testApiErrorsRedactKeysAndRemoveMarkup(): void
    {
        $sp = new FakeSpamProtection(apiKey: 'secret + key');
        $sp->response = json_encode(['success' => 0, 'error' => "<p>Invalid secret + key or secret+%2B+key or secret%20%2B%20key\n</p>"], JSON_THROW_ON_ERROR);
        $this->expectException(SubmissionFailedException::class);
        $this->expectExceptionMessage('Submission failed. Invalid [redacted] or [redacted] or [redacted]');
        $sp->submitReport('user', '127.0.0.1', 'evidence', 'test@example.com');
    }
}
