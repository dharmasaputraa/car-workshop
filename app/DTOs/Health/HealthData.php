<?php

namespace App\DTOs\Health;

final readonly class HealthData
{
    private function __construct(
        public bool $healthy,
        public string $message,
        public array $details,
        public int $statusCode,
    ) {}

    public static function make(
        bool $healthy,
        string $message,
        array $details,
        int $statusCode,
    ): self {
        return new self($healthy, $message, $details, $statusCode);
    }
}
