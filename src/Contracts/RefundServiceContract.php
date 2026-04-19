<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Contracts;

interface RefundServiceContract
{
    public function searchTransaction(string $trxId): array;

    public function refundTransaction(array $payload): array;

    public function refundStatus(string $paymentId, string $trxId): array;
}
