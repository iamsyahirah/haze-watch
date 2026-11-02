<?php

declare(strict_types=1);

namespace HazeWatch\Providers;

use RuntimeException;

final class ProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $providerCode = null,
        public readonly ?string $requestId = null,
        public readonly ?int $retryAfterSeconds = null,
        public readonly ?int $httpStatus = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
