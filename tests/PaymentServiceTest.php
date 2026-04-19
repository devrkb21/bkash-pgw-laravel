<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Tests;

use Devrkb21\Bkash\Contracts\AuthServiceContract;
use Devrkb21\Bkash\Contracts\BkashClientContract;
use Devrkb21\Bkash\Exceptions\BkashPaymentException;
use Devrkb21\Bkash\Services\PaymentService;

class PaymentServiceTest extends TestCase
{
    public function testCreatePaymentValidatesRequiredFields(): void
    {
        $service = new PaymentService(
            $this->createMock(BkashClientContract::class),
            $this->createMock(AuthServiceContract::class)
        );

        $this->expectException(BkashPaymentException::class);

        $service->createPayment([
            'amount' => '100',
            'currency' => 'BDT',
        ]);
    }

    public function testCreatePaymentAutoSwitchesToAgreementEndpoint(): void
    {
        $authService = $this->createMock(AuthServiceContract::class);
        $authService->expects($this->once())
            ->method('getValidAccessToken')
            ->willReturn('token_1');

        $client = $this->createMock(BkashClientContract::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'payment-with-agreement/create',
                $this->callback(function (array $payload): bool {
                    return ($payload['agreementId'] ?? '') === 'agreement_1'
                        && ($payload['merchantInvoiceNumber'] ?? '') === 'INV-1';
                }),
                'token_1'
            )
            ->willReturn([
                'paymentId' => 'payment_1',
                'bkashUrl' => 'https://tokenized.sandbox.bka.sh/pay',
            ]);

        $service = new PaymentService($client, $authService);

        $response = $service->createPayment([
            'agreementId' => 'agreement_1',
            'callbackURL' => 'https://merchant.example.com/callback',
            'amount' => '100',
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => 'INV-1',
        ]);

        $this->assertSame('payment_1', $response['paymentID']);
        $this->assertSame('payment_1', $response['paymentId']);
        $this->assertSame('https://tokenized.sandbox.bka.sh/pay', $response['bkashURL']);
    }

    public function testHandleCallbackExecutesPayment(): void
    {
        $authService = $this->createMock(AuthServiceContract::class);
        $authService->expects($this->once())
            ->method('getValidAccessToken')
            ->willReturn('token_2');

        $client = $this->createMock(BkashClientContract::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'payment/execute',
                ['paymentId' => 'payment_2'],
                'token_2'
            )
            ->willReturn([
                'trxId' => 'trx_1',
            ]);

        $service = new PaymentService($client, $authService);

        $response = $service->handleCallback([
            'paymentID' => 'payment_2',
        ]);

        $this->assertSame('trx_1', $response['trxID']);
    }
}
