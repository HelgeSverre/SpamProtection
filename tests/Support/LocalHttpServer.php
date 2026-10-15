<?php

declare(strict_types=1);

namespace Helge\SpamProtection\Tests\Support;

use RuntimeException;

final class LocalHttpServer
{
    public string $url;

    /** @var resource|null */
    private $process = null;

    public function __construct()
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        if ($socket === false) {
            throw new RuntimeException('Could not reserve a local test port');
        }
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        if ($address === false) {
            throw new RuntimeException('Could not get the local test address');
        }
        $this->url = 'http://' . $address;
        $process = proc_open(
            [PHP_BINARY, '-S', $address, __DIR__ . '/../Fixtures/router.php'],
            [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes
        );
        if (!is_resource($process)) {
            throw new RuntimeException('Could not start the local HTTP server');
        }

        $this->process = $process;

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $connection = @stream_socket_client('tcp://' . $address, timeout: 0.1);
            if ($connection !== false) {
                fclose($connection);

                return;
            }
            usleep(20000);
        }

        $this->stop();
        throw new RuntimeException('Local HTTP server did not start');
    }

    public function stop(): void
    {
        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
            $this->process = null;
        }
    }

    public function __destruct()
    {
        $this->stop();
    }
}
