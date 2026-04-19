<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Contracts;

interface AuthServiceContract
{
    public function grantToken(bool $forceRefresh = false): array;

    public function refreshToken(?string $refreshToken = null): array;

    public function getValidAccessToken(): string;

    public function getCachedTokenData(): ?array;
}
