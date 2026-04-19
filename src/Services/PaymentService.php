<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Services;

use Devrkb21\Bkash\Contracts\BkashClientContract;
use Devrkb21\Bkash\Contracts\AuthServiceContract;
use Devrkb21\Bkash\Contracts\PaymentServiceContract;
use Devrkb21\Bkash\DTO\PaymentResponse;
use Devrkb21\Bkash\Exceptions\BkashException;
use Devrkb21\Bkash\Exceptions\BkashPaymentException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentService implements PaymentServiceContract
{
    public function __construct(
        private readonly BkashClientContract $client,
        private readonly AuthServiceContract $authService
    ) {
    }

    public function createPayment(array $payload, bool $withAgreement = false): array
    {
        $agreementId = $this->extractAgreementId($payload);
        $withAgreement = $withAgreement || $agreementId !== null;

        $normalizedPayload = $payload;

        if ($withAgreement) {
            if ($agreementId === null) {
                throw BkashPaymentException::fromMessage('Missing required field: agreementId.');
            }

            $normalizedPayload['agreementId'] = $agreementId;

            $this->assertRequiredFields(
                $normalizedPayload,
                ['agreementId', 'callbackURL', 'amount', 'currency', 'intent', 'merchantInvoiceNumber'],
                'create payment with agreement'
            );

            $endpoint = 'payment-with-agreement/create';
        } else {
            $this->assertRequiredFields(
                $normalizedPayload,
                ['payerReference', 'callbackURL', 'amount', 'currency', 'intent', 'merchantInvoiceNumber'],
                'create payment'
            );

            $endpoint = 'payment/create';
        }

        $this->validateMerchantInvoiceNumber($normalizedPayload);

        Log::info('bKash payment: create payment request', [
            'agreement_mode' => $withAgreement,
            'merchant_invoice_number' => (string) ($normalizedPayload['merchantInvoiceNumber'] ?? ''),
        ]);

        $response = $this->requestWithAuth('POST', $endpoint, $normalizedPayload);
        $this->assertRequiredResponseFields($response, ['paymentID', 'bkashURL'], 'create payment');

        return $response;
    }

    public function executePayment(string $paymentId, ?string $agreementId = null): array
    {
        if (trim($paymentId) === '') {
            throw BkashPaymentException::fromMessage('Missing required field: paymentId.');
        }

        $withAgreement = $agreementId !== null && $agreementId !== '';
        $endpoint = $withAgreement ? 'payment-with-agreement/execute' : 'payment/execute';

        $payload = ['paymentId' => trim($paymentId)];
        if ($withAgreement) {
            $payload['agreementId'] = trim($agreementId);
        }

        Log::info('bKash payment: execute payment request', [
            'agreement_mode' => $withAgreement,
            'payment_id' => $payload['paymentId'],
        ]);

        $response = $this->requestWithAuth('POST', $endpoint, $payload);
        $this->assertRequiredResponseFields($response, ['trxID'], 'execute payment');

        return $response;
    }

    public function queryPayment(string $paymentId): array
    {
        if (trim($paymentId) === '') {
            throw BkashPaymentException::fromMessage('Missing required field: paymentId.');
        }

        return $this->requestWithAuth('POST', 'query/payment', [
            'paymentID' => trim($paymentId),
        ]);
    }

    public function capturePayment(string $paymentId, ?string $agreementId = null): array
    {
        if (trim($paymentId) === '') {
            throw BkashPaymentException::fromMessage('Missing required field: paymentId.');
        }

        $withAgreement = $agreementId !== null && $agreementId !== '';
        $endpoint = $withAgreement ? 'payment-with-agreement/capture' : 'payment/capture';

        $payload = ['paymentId' => trim($paymentId)];
        if ($withAgreement) {
            $payload['agreementId'] = trim($agreementId);
        }

        return $this->requestWithAuth('POST', $endpoint, $payload);
    }

    public function voidPayment(string $paymentId, ?string $agreementId = null): array
    {
        if (trim($paymentId) === '') {
            throw BkashPaymentException::fromMessage('Missing required field: paymentId.');
        }

        $withAgreement = $agreementId !== null && $agreementId !== '';
        $endpoint = $withAgreement ? 'payment-with-agreement/void' : 'payment/void';

        $payload = ['paymentId' => trim($paymentId)];
        if ($withAgreement) {
            $payload['agreementId'] = trim($agreementId);
        }

        return $this->requestWithAuth('POST', $endpoint, $payload);
    }

    public function handleCallback(array $request): array
    {
        $paymentId = $this->extractPaymentId($request);

        if ($paymentId === null) {
            throw BkashPaymentException::fromMessage('Missing required field: paymentId.');
        }

        return $this->executePayment($paymentId, $this->extractAgreementId($request));
    }

    public function initiatePayout(array $payload): array
    {
        return $this->requestWithAuth('POST', 'payout/initiate', $payload);
    }

    private function requestWithAuth(string $method, string $endpoint, array $payload = []): array
    {
        try {
            $response = $this->client->request(
                $method,
                $endpoint,
                $payload,
                $this->authService->getValidAccessToken()
            );
        } catch (BkashException $exception) {
            Log::error('bKash payment: request failed', [
                'endpoint' => $endpoint,
                'error' => 'request_failed',
                'code' => $exception->getCode(),
            ]);

            throw $exception;
        }

        return $this->normalizeResponse($response);
    }

    private function assertRequiredFields(array $payload, array $requiredFields, string $context): void
    {
        $missing = [];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $payload) || $this->isEmptyValue($payload[$field])) {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw BkashPaymentException::fromMessage(
                sprintf('Missing required field(s) for %s: %s', $context, implode(', ', $missing))
            );
        }
    }

    private function assertRequiredResponseFields(array $response, array $requiredFields, string $context): void
    {
        $missing = [];

        foreach ($requiredFields as $field) {
            if (!$this->hasValueByAliases($response, $this->fieldAliases($field))) {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw BkashPaymentException::fromMessage(
                sprintf('bKash %s response missing field(s): %s', $context, implode(', ', $missing))
            );
        }
    }

    private function normalizeResponse(array $response): array
    {
        return PaymentResponse::fromArray($response)->toArray();
    }

    private function validateMerchantInvoiceNumber(array &$payload): void
    {
        $merchantInvoiceNumber = $payload['merchantInvoiceNumber'] ?? null;

        if (!is_string($merchantInvoiceNumber) || trim($merchantInvoiceNumber) === '') {
            throw BkashPaymentException::fromMessage('Missing required field: merchantInvoiceNumber.');
        }

        $merchantInvoiceNumber = trim($merchantInvoiceNumber);

        // This field must be unique to prevent duplicate payments.
        $cacheKey = 'bkash.merchant_invoice.' . hash('sha256', $merchantInvoiceNumber);

        if (!Cache::add($cacheKey, true, 86400)) {
            throw BkashPaymentException::fromMessage('merchantInvoiceNumber must be a unique string.');
        }

        $payload['merchantInvoiceNumber'] = $merchantInvoiceNumber;
    }

    private function fieldAliases(string $field): array
    {
        return match ($field) {
            'paymentID', 'paymentId' => ['paymentID', 'paymentId', 'payment_id'],
            'agreementID', 'agreementId' => ['agreementID', 'agreementId', 'agreement_id'],
            'trxID', 'trxId' => ['trxID', 'trxId', 'trxid', 'trx_id'],
            default => [$field],
        };
    }

    private function hasValueByAliases(array $data, array $aliases): bool
    {
        foreach ($aliases as $alias) {
            if (!array_key_exists($alias, $data)) {
                continue;
            }

            if (!$this->isEmptyValue($data[$alias])) {
                return true;
            }
        }

        return false;
    }


    private function extractPaymentId(array $request): ?string
    {
        foreach (['paymentId', 'paymentID', 'payment_id'] as $key) {
            if (!array_key_exists($key, $request) || $this->isEmptyValue($request[$key])) {
                continue;
            }

            return trim((string) $request[$key]);
        }

        return null;
    }

    private function extractAgreementId(array $request): ?string
    {
        foreach (['agreementId', 'agreementID', 'agreement_id'] as $key) {
            if (!array_key_exists($key, $request) || $this->isEmptyValue($request[$key])) {
                continue;
            }

            return trim((string) $request[$key]);
        }

        return null;
    }

    private function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }
}
