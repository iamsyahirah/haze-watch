<?php

declare(strict_types=1);

namespace HazeWatch\Providers;

final class HttpResponse
{
    /** @param array<string, list<string>> $headers */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers,
    ) {
    }

    public function header(string $name): ?string
    {
        $values = $this->headers[strtolower($name)] ?? [];

        return $values === [] ? null : end($values);
    }
}
