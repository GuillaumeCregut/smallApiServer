<?php

namespace App\Kernel\Security;

readonly class CsrfToken
{
    public function __construct(
        public string $value,
        public int $expiresAt
    ) {}

    public function isExpired(): bool
    {
        return time() > $this->expiresAt;
    }
}
