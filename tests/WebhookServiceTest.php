<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Tests;

use Devrkb21\Bkash\Services\WebhookService;
use Illuminate\Support\Facades\Http;

class WebhookServiceTest extends TestCase
{
    public function testNotificationEventIsParsedSuccessfully(): void
    {
        $service = $this->serviceWithVerificationResult(true);

        $response = $service->process(
            [
                'Type' => 'Notification',
                'MessageId' => 'msg-100',
                'Timestamp' => gmdate('c'),
                'Message' => json_encode([
                    'trxID' => 'trx_100',
                    'amount' => '100.00',
                    'transactionStatus' => 'Completed',
                ], JSON_THROW_ON_ERROR),
            ],
            [
                'X-AMZ-SNS-MESSAGE-TYPE' => 'Notification',
            ]
        );

        $this->assertTrue($response['success']);
        $this->assertSame('Notification', $response['type']);
        $this->assertSame('trx_100', $response['data']['trxID']);
        $this->assertSame('100.00', $response['data']['amount']);
        $this->assertSame('Completed', $response['data']['transactionStatus']);
    }

    public function testSubscriptionConfirmationCallsSubscribeUrl(): void
    {
        $service = $this->serviceWithVerificationResult(true);

        Http::fake([
            'https://sns.us-east-1.amazonaws.com/*' => Http::response('', 200),
        ]);

        $response = $service->process([
            'Type' => 'SubscriptionConfirmation',
            'MessageId' => 'msg-200',
            'Timestamp' => gmdate('c'),
            'SubscribeURL' => 'https://sns.us-east-1.amazonaws.com/?Action=ConfirmSubscription',
        ]);

        $this->assertTrue($response['success']);
        $this->assertSame('SubscriptionConfirmation', $response['type']);
        $this->assertTrue($response['data']['subscriptionConfirmed']);
    }

    public function testInvalidSignatureIsRejected(): void
    {
        $service = $this->serviceWithVerificationResult(false);

        $response = $service->process([
            'Type' => 'Notification',
            'MessageId' => 'msg-300',
            'Timestamp' => gmdate('c'),
            'Message' => '{"trxID":"trx_100","amount":"100.00","transactionStatus":"Completed"}',
        ]);

        $this->assertFalse($response['success']);
        $this->assertSame('Notification', $response['type']);
    }

    private function serviceWithVerificationResult(bool $verified): WebhookService
    {
        return new class ($verified) extends WebhookService {
            public function __construct(
                private readonly bool $verified
            ) {
            }

            public function verifySignature(array $payload): bool
            {
                return $this->verified;
            }
        };
    }
}
