<?php

declare(strict_types=1);

namespace HazeWatch\Providers;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;
use RuntimeException;

/** Read-only client for the documented Haze Monitor endpoints. */
final class HazeMonitorClient
{
    private const BASE_URL = 'https://hazemonitor.com';

    public function __construct(
        private readonly string $cacheDirectory,
        private readonly int $connectTimeoutSeconds = 5,
        private readonly int $timeoutSeconds = 15,
        private readonly int $maxResponseBytes = 5_000_000,
    ) {
    }

    /** @return array<string, mixed> */
    public function fetchCurrent(): array
    {
        return $this->getJson('/api/air-quality');
    }

    /** @return array<string, mixed> */
    public function fetchRecentHistory(): array
    {
        return $this->getJson('/api/air-quality/history');
    }

    /** @return array<string, mixed> */
    public function fetchStationHistory(string $stationId, string $range): array
    {
        if (!preg_match('/^[a-zA-Z0-9_-]{1,40}$/', $stationId)) {
            throw new \InvalidArgumentException('Station identifier contains unsupported characters.');
        }
        if (!in_array($range, ['month', 'year'], true)) {
            throw new \InvalidArgumentException('History range must be month or year.');
        }

        $query = '?' . http_build_query(['station' => $stationId, 'range' => $range]);

        return $this->getJson('/api/air-quality/history' . $query);
    }

    /** @return array<string, mixed> */
    private function getJson(string $path): array
    {
        $url = self::BASE_URL . $path;
        $cachePath = rtrim($this->cacheDirectory, '/') . '/' . hash('sha256', $url) . '.json';
        $cached = $this->readFreshCache($cachePath);
        if ($cached !== null) {
            return $cached;
        }

        $response = $this->request($url);
        if ($response->status < 200 || $response->status >= 300) {
            throw $this->providerError($response);
        }

        try {
            $decoded = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ProviderException('Provider returned invalid JSON.', httpStatus: $response->status, previous: $exception);
        }
        if (!is_array($decoded)) {
            throw new ProviderException('Provider response must be a JSON object.', httpStatus: $response->status);
        }

        $lifetime = $this->cacheLifetimeSeconds($response);
        if ($lifetime === null || $lifetime === 0 || $this->hasDirective($response->header('cache-control'), 'no-store')) {
            throw new ProviderException('Successful response did not publish a reusable cache lifetime; refusing to ingest until cache guidance is available.', httpStatus: $response->status);
        }
        if ($lifetime > 0) {
            $this->writeCache($cachePath, $decoded, $lifetime);
        }

        return $decoded;
    }

    private function request(string $url): HttpResponse
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('The PHP cURL extension is required for provider requests.');
        }

        $headers = [];
        $body = '';
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Could not initialize provider HTTP request.');
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeoutSeconds,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$headers): int {
                $length = strlen($line);
                if (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $headers[strtolower(trim($name))][] = trim($value);
                }

                return $length;
            },
            CURLOPT_WRITEFUNCTION => function ($curl, string $chunk) use (&$body): int {
                $body ??= '';
                if (strlen($body) + strlen($chunk) > $this->maxResponseBytes) {
                    return 0;
                }
                $body .= $chunk;

                return strlen($chunk);
            },
        ]);

        $success = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);

        if ($success === false) {
            throw new ProviderException('Provider request failed: ' . ($error ?: 'response could not be read.'));
        }

        return new HttpResponse($status, $body, $headers);
    }

    /** @return array<string, mixed>|null */
    private function readFreshCache(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        try {
            $entry = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($entry) || !isset($entry['expiresAt'], $entry['body']) || $entry['expiresAt'] <= time() || !is_array($entry['body'])) {
            return null;
        }

        return $entry['body'];
    }

    /**
     * Use an explicit freshness lifetime from Cache-Control or Expires.
     * No polling interval is guessed when the server publishes no lifetime.
     */
    private function cacheLifetimeSeconds(HttpResponse $response): ?int
    {
        $cacheControl = $response->header('cache-control');
        if ($this->hasDirective($cacheControl, 'no-cache') || $this->hasDirective($cacheControl, 'no-store')) {
            return null;
        }
        if ($cacheControl !== null && preg_match('/(?:^|,)\s*(?:s-maxage|max-age)\s*=\s*"?(\d+)/i', $cacheControl, $match)) {
            $age = max(0, (int) ($response->header('age') ?? 0));

            return max(0, (int) $match[1] - $age);
        }

        $expires = $response->header('expires');
        if ($expires !== null) {
            try {
                $expiresAt = new DateTimeImmutable($expires, new DateTimeZone('UTC'));

                return max(0, $expiresAt->getTimestamp() - time());
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }

    private function hasDirective(?string $header, string $directive): bool
    {
        return $header !== null && preg_match('/(?:^|,)\s*' . preg_quote($directive, '/') . '(?:\s|,|$)/i', $header) === 1;
    }

    private function writeCache(string $path, array $body, int $lifetime): void
    {
        if (!is_dir($this->cacheDirectory) && !mkdir($this->cacheDirectory, 0770, true) && !is_dir($this->cacheDirectory)) {
            throw new RuntimeException('Could not create provider cache directory.');
        }

        $entry = json_encode([
            'expiresAt' => time() + $lifetime,
            'body' => $body,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $temporaryPath = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (file_put_contents($temporaryPath, $entry, LOCK_EX) === false || !rename($temporaryPath, $path)) {
            @unlink($temporaryPath);
            throw new RuntimeException('Could not write provider cache entry.');
        }
    }

    private function providerError(HttpResponse $response): ProviderException
    {
        $payload = json_decode($response->body, true);

        return new ProviderException(
            is_array($payload) && is_string($payload['message'] ?? null) ? $payload['message'] : 'Provider returned an unsuccessful HTTP status.',
            is_array($payload) && is_string($payload['code'] ?? null) ? $payload['code'] : null,
            is_array($payload) && is_string($payload['requestId'] ?? null) ? $payload['requestId'] : null,
            $this->parseRetryAfter($response->header('retry-after')),
            $response->status,
        );
    }

    private function parseRetryAfter(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }
        if (ctype_digit($value)) {
            return (int) $value;
        }

        try {
            return max(0, (new DateTimeImmutable($value))->getTimestamp() - time());
        } catch (\Exception) {
            return null;
        }
    }
}
