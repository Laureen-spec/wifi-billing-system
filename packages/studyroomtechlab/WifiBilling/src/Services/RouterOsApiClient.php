<?php

namespace StudyRoomTechLab\WifiBilling\Services;

use RuntimeException;

class RouterOsApiClient
{
    private $socket = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port = 8728,
        private readonly string $username = 'admin',
        private readonly string $password = '',
        private readonly int $timeout = 8,
    ) {
    }

    public function connect(): void
    {
        if ($this->socket) {
            return;
        }

        $target = sprintf('tcp://%s:%d', $this->host, $this->port);
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client($target, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT);

        if (! $socket) {
            throw new RuntimeException("RouterOS API connection failed: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, $this->timeout);
        $this->socket = $socket;
        $this->login();
    }

    public function close(): void
    {
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function query(string $command, array $attributes = [], array $queries = []): array
    {
        $this->connect();

        $words = [$command];
        foreach ($attributes as $key => $value) {
            if ($value === null) {
                continue;
            }
            if ($value === true) {
                $words[] = '=' . $key . '=';
            } else {
                $words[] = '=' . $key . '=' . (string) $value;
            }
        }
        foreach ($queries as $query) {
            $words[] = $query;
        }

        $this->writeSentence($words);
        return $this->readRows();
    }

    public function first(string $command, array $attributes = [], array $queries = []): ?array
    {
        $rows = $this->query($command, $attributes, $queries);
        return $rows[0] ?? null;
    }

    public function findByName(string $path, string $name): ?array
    {
        return $this->first($path . '/print', [], ['?name=' . $name]);
    }

    public function upsertByName(string $path, string $name, array $attributes): void
    {
        $existing = $this->findByName($path, $name);
        $payload = array_merge(['name' => $name], $attributes);

        if ($existing && isset($existing['.id'])) {
            $payload = array_merge(['.id' => $existing['.id']], $payload);
            $this->query($path . '/set', $payload);
            return;
        }

        $this->query($path . '/add', $payload);
    }

    private function login(): void
    {
        try {
            $this->writeSentence(['/login', '=name=' . $this->username, '=password=' . $this->password]);
            $this->readRows();
            return;
        } catch (RuntimeException $e) {
            // Some very old RouterOS builds use challenge login. Try the legacy flow.
        }

        $this->writeSentence(['/login']);
        $rows = $this->readRawSentences();
        $ret = null;
        foreach ($rows as $sentence) {
            foreach ($sentence as $word) {
                if (str_starts_with($word, '=ret=')) {
                    $ret = substr($word, 5);
                    break 2;
                }
            }
        }

        if (! $ret) {
            throw new RuntimeException('RouterOS API login failed: challenge was not returned.');
        }

        $response = '00' . md5(chr(0) . $this->password . pack('H*', $ret));
        $this->writeSentence(['/login', '=name=' . $this->username, '=response=' . $response]);
        $this->readRows();
    }

    private function readRows(): array
    {
        $sentences = $this->readRawSentences();
        $rows = [];
        $trapMessages = [];

        foreach ($sentences as $sentence) {
            $reply = $sentence[0] ?? '';
            $row = [];

            foreach (array_slice($sentence, 1) as $word) {
                if (! str_starts_with($word, '=')) {
                    continue;
                }
                $parts = explode('=', substr($word, 1), 2);
                $row[$parts[0]] = $parts[1] ?? '';
            }

            if ($reply === '!re') {
                $rows[] = $row;
            }
            if ($reply === '!trap') {
                $trapMessages[] = $row['message'] ?? 'Unknown RouterOS API error';
            }
            if ($reply === '!done' && isset($row['ret']) && empty($rows)) {
                $rows[] = ['ret' => $row['ret']];
            }
        }

        if ($trapMessages) {
            throw new RuntimeException(implode('; ', $trapMessages));
        }

        return $rows;
    }

    private function readRawSentences(): array
    {
        $sentences = [];

        while (true) {
            $sentence = $this->readSentence();
            if ($sentence === []) {
                continue;
            }
            $sentences[] = $sentence;
            if (($sentence[0] ?? null) === '!done') {
                break;
            }
        }

        return $sentences;
    }

    private function writeSentence(array $words): void
    {
        foreach ($words as $word) {
            $this->writeWord((string) $word);
        }
        $this->writeWord('');
    }

    private function readSentence(): array
    {
        $sentence = [];
        while (true) {
            $word = $this->readWord();
            if ($word === '') {
                return $sentence;
            }
            $sentence[] = $word;
        }
    }

    private function writeWord(string $word): void
    {
        $this->writeLength(strlen($word));
        if ($word !== '') {
            $written = @fwrite($this->socket, $word);
            if ($written === false) {
                throw new RuntimeException('RouterOS API write failed.');
            }
        }
    }

    private function readWord(): string
    {
        $length = $this->readLength();
        if ($length === 0) {
            return '';
        }

        $data = '';
        while (strlen($data) < $length) {
            $chunk = @fread($this->socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('RouterOS API read failed or timed out.');
            }
            $data .= $chunk;
        }

        return $data;
    }

    private function writeLength(int $length): void
    {
        if ($length < 0x80) {
            $data = chr($length);
        } elseif ($length < 0x4000) {
            $data = chr(($length >> 8) | 0x80) . chr($length & 0xFF);
        } elseif ($length < 0x200000) {
            $data = chr(($length >> 16) | 0xC0) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x10000000) {
            $data = chr(($length >> 24) | 0xE0) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } else {
            $data = chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }

        if (@fwrite($this->socket, $data) === false) {
            throw new RuntimeException('RouterOS API length write failed.');
        }
    }

    private function readLength(): int
    {
        $byte = @fread($this->socket, 1);
        if ($byte === false || $byte === '') {
            throw new RuntimeException('RouterOS API length read failed.');
        }

        $c = ord($byte);
        if (($c & 0x80) === 0x00) {
            return $c;
        }
        if (($c & 0xC0) === 0x80) {
            return (($c & ~0xC0) << 8) + ord($this->readBytes(1));
        }
        if (($c & 0xE0) === 0xC0) {
            return (($c & ~0xE0) << 16) + (ord($this->readBytes(1)) << 8) + ord($this->readBytes(1));
        }
        if (($c & 0xF0) === 0xE0) {
            return (($c & ~0xF0) << 24) + (ord($this->readBytes(1)) << 16) + (ord($this->readBytes(1)) << 8) + ord($this->readBytes(1));
        }

        return (ord($this->readBytes(1)) << 24) + (ord($this->readBytes(1)) << 16) + (ord($this->readBytes(1)) << 8) + ord($this->readBytes(1));
    }

    private function readBytes(int $length): string
    {
        $data = @fread($this->socket, $length);
        if ($data === false || strlen($data) !== $length) {
            throw new RuntimeException('RouterOS API byte read failed.');
        }
        return $data;
    }
}
