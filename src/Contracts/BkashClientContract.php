<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Contracts;

interface BkashClientContract
{
    public function request(
        string $method,
        string $endpoint,
        array $payload = [],
        ?string $accessToken = null,
        array $headers = []
    ): array;
}