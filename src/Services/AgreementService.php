<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Services;

use Devrkb21\Bkash\Contracts\BkashClientContract;
use Devrkb21\Bkash\Contracts\AgreementServiceContract;
use Devrkb21\Bkash\Contracts\AuthServiceContract;

class AgreementService implements AgreementServiceContract
{
    public function __construct(
        private readonly BkashClientContract $client,
        private readonly AuthServiceContract $authService
    ) {
    }

    public function createAgreement(array $payload): array
    {
        return $this->requestWithAuth('POST', 'agreement/create', $payload);
    }

    public function executeAgreement(string $agreementId): array
    {
        return $this->requestWithAuth('POST', 'agreement/execute', [
            'agreementId' => $agreementId,
        ]);
    }

    public function queryAgreement(string $agreementId): array
    {
        return $this->requestWithAuth('POST', 'query/agreement', [
            'agreementId' => $agreementId,
        ]);
    }

    public function cancelAgreement(string $agreementId): array
    {
        return $this->requestWithAuth('POST', 'agreement/cancel', [
            'agreementId' => $agreementId,
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
