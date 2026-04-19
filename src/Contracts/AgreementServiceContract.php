<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Contracts;

interface AgreementServiceContract
{
    public function createAgreement(array $payload): array;

    public function executeAgreement(string $agreementId): array;

    public function queryAgreement(string $agreementId): array;

    public function cancelAgreement(string $agreementId): array;
}
