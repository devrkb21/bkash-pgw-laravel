<?php

declare(strict_types=1);

namespace Devrkb21\Bkash;

use Devrkb21\Bkash\Contracts\AgreementServiceContract;
use Devrkb21\Bkash\Contracts\AuthServiceContract;
use Devrkb21\Bkash\Contracts\PaymentServiceContract;
use Devrkb21\Bkash\Contracts\RefundServiceContract;

class BkashManager
{
    public function __construct(
        private readonly PaymentServiceContract $paymentService,
        private readonly AuthServiceContract $authService,
        private readonly AgreementServiceContract $agreementService,
        private readonly RefundServiceContract $refundService
    ) {
    }

    public function payment(): PaymentServiceContract
    {
        return $this->paymentService;
    }

    public function auth(): AuthServiceContract
    {
        return $this->authService;
    }

    public function agreement(): AgreementServiceContract
    {
        return $this->agreementService;
    }

    public function refund(): RefundServiceContract
    {
        return $this->refundService;
    }
}