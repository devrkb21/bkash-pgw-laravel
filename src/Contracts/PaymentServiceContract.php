<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Contracts;

interface PaymentServiceContract
{
    public function createPayment(array $payload, bool $withAgreement = false): array;

    public function executePayment(string $paymentId, ?string $agreementId = null): array;

    public function queryPayment(string $paymentId): array;

    public function capturePayment(string $paymentId, ?string $agreementId = null): array;

    public function voidPayment(string $paymentId, ?string $agreementId = null): array;

    public function initiatePayout(array $payload): array;
}
