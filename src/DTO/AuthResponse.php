<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\DTO;

class AuthResponse
{
    public function __construct(
        public readonly string $idToken,
        public readonly string $refreshToken,
        public readonly int $expiresAt,
        public readonly int $expiresIn
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['id_token'] ?? ''),
            (string) ($data['refresh_token'] ?? ''),
            (int) ($data['expires_at'] ?? 0),
            (int) ($data['expires_in'] ?? 0)
        );
    }

    public function toArray(): array
    {
        return [
            'id_token' => $this->idToken,
            'refresh_token' => $this->refreshToken,
            'expires_at' => $this->expiresAt,
            'expires_in' => $this->expiresIn,
        ];
    }
}
