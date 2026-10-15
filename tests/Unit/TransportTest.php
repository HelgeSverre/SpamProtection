<?php

declare(strict_types=1);

namespace Helge\SpamProtection\Tests\Unit;

use Helge\SpamProtection\Exception\ApiCheckException;
use Helge\SpamProtection\SpamProtection;
use Helge\SpamProtection\Tests\Support\LocalHttpServer;
use Helge\SpamProtection\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

final class TransportTest extends TestCase
{
    #[DataProvider('transports')]
    public function testHttpTransport(bool $useCurl): void
    {
        $server = new LocalHttpServer();
        $sp = new class ($server->url, $useCurl) extends SpamProtection {
            public function __construct(string $url, bool $useCurl)
            {
                parent::__construct();
                $this->baseApiUrl = $url;
                $this->curlEnabled = $useCurl;
            }

            public function request(string $url): mixed
            {
                return $this->sendRequest($url);
            }
        };

        try {
            self::assertFalse($sp->checkIP('127.0.0.1'));
            try {
                $sp->request($server->url . '?unavailable=1');
                self::fail('Expected an HTTP error');
            } catch (RuntimeException $e) {
                self::assertSame('API returned HTTP 503', $e->getMessage());
                self::assertSame(503, $e->getCode());
            }
            $server->stop();
            $this->expectException(ApiCheckException::class);
            $sp->checkIP('127.0.0.1');
        } finally {
            $server->stop();
        }
    }

    public static function transports(): array
    {
        return ['cURL' => [true], 'streams' => [false]];
    }
}
