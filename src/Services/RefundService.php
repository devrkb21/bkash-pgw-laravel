<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Services;

use Devrkb21\Bkash\Contracts\BkashClientContract;
use Devrkb21\Bkash\Contracts\AuthServiceContract;
use Devrkb21\Bkash\Contracts\RefundServiceContract;

class RefundService implements RefundServiceContract
{
    public function __construct(
        private readonly BkashClientContract $client,
        private readonly AuthServiceContract $authService
    ) {
    }

    public function searchTransaction(string $trxId): array
    {
        return $this->requestWithAuth('POST', 'general/search-transaction', [
            'trxId' => $trxId,
        ]);
    }

    public function refundTransaction(array $payload): array
    {
        return $this->requestWithAuth('POST', 'refund/payment/transaction', $payload);
    }

    public function refundStatus(string $paymentId, string $trxId): array
    {
        return $this->requestWithAuth('POST', 'refund/payment/status', [
            'paymentId' => $paymentId,
            'trxId' => $trxId,
        ]);
    }

    private function requestWithAuth(string $method, string $endpoint, array $payload = []): array
    {
        return $this->client->request(
            $method,
            $endpoint,
            $payload,
            $this->authService->getValidAccessToken()
        );
    }
}
