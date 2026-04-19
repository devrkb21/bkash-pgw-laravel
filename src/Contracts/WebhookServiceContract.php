<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Contracts;

interface WebhookServiceContract
{
    public function process(array $payload, array $headers = []): array;
}
