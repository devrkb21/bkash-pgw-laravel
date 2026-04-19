<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Services;

use Devrkb21\Bkash\Contracts\WebhookServiceContract;
use Devrkb21\Bkash\Exceptions\BkashException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenSSLAsymmetricKey;
use Throwable;

class WebhookService implements WebhookServiceContract
{
    public function process(array $payload, array $headers = []): array
    {
        $normalizedHeaders = $this->normalizeHeaders($headers);
        $headerType = trim((string) ($normalizedHeaders['x-amz-sns-message-type'] ?? ''));
        $payloadType = trim((string) ($payload['Type'] ?? ''));
        $type = $headerType !== '' ? $headerType : $payloadType;

        Log::info('bKash webhook: incoming webhook', [
            'type' => $type,
            'message_id' => (string) ($payload['MessageId'] ?? ''),
        ]);

        if ($type === '') {
            Log::warning('bKash webhook: missing message type');

            return $this->failureResponse('unknown', 'Missing SNS message type.');
        }

        $verified = $this->verifySignature($payload);

        Log::info('bKash webhook: signature verification result', [
            'type' => $type,
            'verified' => $verified,
        ]);

        if (!$verified) {
            Log::warning('bKash webhook: invalid signature', [
                'type' => $type,
            ]);

            return $this->failureResponse($type, 'Invalid webhook signature.');
        }

        $replayValidation = $this->validateReplayProtection($payload);
        if ($replayValidation !== null) {
            Log::warning('bKash webhook: replay protection rejected message', [
                'type' => $type,
                'reason' => $replayValidation,
            ]);

            return $this->failureResponse($type, $replayValidation);
        }

        if ($type === 'SubscriptionConfirmation') {
            return $this->handleSubscriptionConfirmation($payload, $type);
        }

        if ($type === 'Notification') {
            return $this->handleNotification($payload, $type);
        }

        Log::warning('bKash webhook: unsupported message type', [
            'type' => $type,
        ]);

        return $this->failureResponse($type, 'Unsupported SNS message type.');
    }

    public function verifySignature(array $payload): bool
    {
        $requiredFields = ['Type', 'MessageId', 'TopicArn', 'Timestamp', 'Signature', 'SignatureVersion', 'SigningCertURL', 'Message'];
        foreach ($requiredFields as $field) {
            if (!$this->hasNonEmptyScalar($payload, $field)) {
                return false;
            }
        }

        $signingCertUrl = trim((string) $payload['SigningCertURL']);
        $this->validateSigningCertUrlOrFail($signingCertUrl);

        $stringToSign = $this->buildStringToSign($payload);
        if ($stringToSign === null) {
            return false;
        }

        $signature = base64_decode((string) $payload['Signature'], true);
        if ($signature === false || $signature === '') {
            return false;
        }

        $algorithm = $this->resolveAlgorithm((string) $payload['SignatureVersion']);
        if ($algorithm === null) {
            return false;
        }

        $certificateResponse = Http::timeout(10)->get($signingCertUrl);
        if (!$certificateResponse->successful()) {
            return false;
        }

        $certificateBody = (string) $certificateResponse->body();
        if ($certificateBody === '') {
            return false;
        }

        $publicKey = openssl_pkey_get_public($certificateBody);
        if ($publicKey === false) {
            return false;
        }

        try {
            $verificationResult = openssl_verify($stringToSign, $signature, $publicKey, $algorithm);
        } finally {
            if ($publicKey instanceof OpenSSLAsymmetricKey) {
                openssl_free_key($publicKey);
            }
        }

        return $verificationResult === 1;
    }

    private function handleSubscriptionConfirmation(array $payload, string $type): array
    {
        if (!$this->hasNonEmptyScalar($payload, 'SubscribeURL')) {
            Log::warning('bKash webhook: SubscriptionConfirmation missing SubscribeURL');

            return $this->failureResponse($type, 'Missing SubscribeURL field.');
        }

        $subscribeUrl = trim((string) $payload['SubscribeURL']);
        if (!filter_var($subscribeUrl, FILTER_VALIDATE_URL)) {
            Log::warning('bKash webhook: SubscriptionConfirmation has invalid SubscribeURL', [
                'subscribe_url' => $subscribeUrl,
            ]);

            return $this->failureResponse($type, 'Invalid SubscribeURL field.');
        }

        try {
            $response = Http::timeout(5)->get($subscribeUrl);
        } catch (Throwable $exception) {
            Log::warning('bKash webhook: subscription confirmation request exception', [
                'error' => $exception->getMessage(),
            ]);

            return $this->failureResponse($type, 'Failed to confirm subscription.');
        }

        if (!$response->successful()) {
            Log::warning('bKash webhook: subscription confirmation request failed', [
                'status' => $response->status(),
            ]);

            return $this->failureResponse($type, 'Failed to confirm subscription.');
        }

        return $this->successResponse($type, [
            'subscriptionConfirmed' => true,
            'subscribeURL' => $subscribeUrl,
        ]);
    }

    private function handleNotification(array $payload, string $type): array
    {
        if (!$this->hasNonEmptyScalar($payload, 'Message')) {
            Log::warning('bKash webhook: Notification missing Message field');

            return $this->failureResponse($type, 'Missing Message field.');
        }

        $messageRaw = (string) $payload['Message'];
        $message = json_decode($messageRaw, true);

        if (!is_array($message)) {
            Log::warning('bKash webhook: Notification message is not valid JSON');

            return $this->failureResponse($type, 'Invalid Message JSON payload.');
        }

        $trxId = $this->pickString($message, ['trxID', 'trxId', 'trxid', 'trx_id']);
        $amount = $this->pickString($message, ['amount']);
        $transactionStatus = $this->pickString($message, ['transactionStatus', 'transaction_status']);

        if ($trxId === null || $amount === null || $transactionStatus === null) {
            Log::warning('bKash webhook: Notification message missing required fields');

            return $this->failureResponse($type, 'Missing Notification field(s): trxID, amount, transactionStatus.');
        }

        $parsedData = [
            'trxID' => $trxId,
            'trxId' => $trxId,
            'amount' => $amount,
            'transactionStatus' => $transactionStatus,
        ];

        Log::info('bKash webhook: parsed notification message', $parsedData);

        return $this->successResponse($type, $parsedData);
    }

    private function successResponse(string $type, array $data): array
    {
        return [
            'success' => true,
            'type' => $type,
            'data' => $data,
        ];
    }

    private function failureResponse(string $type, string $reason): array
    {
        return [
            'success' => false,
            'type' => $type,
            'data' => [
                'error' => $reason,
            ],
        ];
    }

    private function buildStringToSign(array $payload): ?string
    {
        $type = (string) ($payload['Type'] ?? '');

        if ($type === 'Notification') {
            if (!$this->hasNonEmptyScalar($payload, 'Message')
                || !$this->hasNonEmptyScalar($payload, 'MessageId')
                || !$this->hasNonEmptyScalar($payload, 'Timestamp')
                || !$this->hasNonEmptyScalar($payload, 'TopicArn')
            ) {
                return null;
            }

            $stringToSign = '';
            $stringToSign .= $this->line('Message', (string) $payload['Message']);
            $stringToSign .= $this->line('MessageId', (string) $payload['MessageId']);

            if ($this->hasNonEmptyScalar($payload, 'Subject')) {
                $stringToSign .= $this->line('Subject', (string) $payload['Subject']);
            }

            $stringToSign .= $this->line('Timestamp', (string) $payload['Timestamp']);
            $stringToSign .= $this->line('TopicArn', (string) $payload['TopicArn']);
            $stringToSign .= $this->line('Type', (string) $payload['Type']);

            return $stringToSign;
        }

        if ($type === 'SubscriptionConfirmation' || $type === 'UnsubscribeConfirmation') {
            if (!$this->hasNonEmptyScalar($payload, 'Message')
                || !$this->hasNonEmptyScalar($payload, 'MessageId')
                || !$this->hasNonEmptyScalar($payload, 'SubscribeURL')
                || !$this->hasNonEmptyScalar($payload, 'Timestamp')
                || !$this->hasNonEmptyScalar($payload, 'Token')
                || !$this->hasNonEmptyScalar($payload, 'TopicArn')
            ) {
                return null;
            }

            $stringToSign = '';
            $stringToSign .= $this->line('Message', (string) $payload['Message']);
            $stringToSign .= $this->line('MessageId', (string) $payload['MessageId']);
            $stringToSign .= $this->line('SubscribeURL', (string) $payload['SubscribeURL']);
            $stringToSign .= $this->line('Timestamp', (string) $payload['Timestamp']);
            $stringToSign .= $this->line('Token', (string) $payload['Token']);
            $stringToSign .= $this->line('TopicArn', (string) $payload['TopicArn']);
            $stringToSign .= $this->line('Type', (string) $payload['Type']);

            return $stringToSign;
        }

        return null;
    }

    private function resolveAlgorithm(string $signatureVersion): ?int
    {
        if ($signatureVersion === '1') {
            return OPENSSL_ALGO_SHA1;
        }

        if ($signatureVersion === '2') {
            return OPENSSL_ALGO_SHA256;
        }

        return null;
    }

    private function validateSigningCertUrlOrFail(string $url): void
    {
        if (!str_starts_with(strtolower($url), 'https://sns.')) {
            throw new BkashException('Invalid SigningCertURL.');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new BkashException('Invalid SigningCertURL.');
        }

        $parts = parse_url($url);

        if (!is_array($parts)) {
            throw new BkashException('Invalid SigningCertURL.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        $query = (string) ($parts['query'] ?? '');
        $fragment = (string) ($parts['fragment'] ?? '');

        if ($scheme !== 'https') {
            throw new BkashException('Invalid SigningCertURL.');
        }

        if (!preg_match('/^sns\.[a-z0-9-]+\.amazonaws\.com$/', $host)) {
            throw new BkashException('Invalid SigningCertURL.');
        }

        if (!str_ends_with(strtolower($path), '.pem')) {
            throw new BkashException('Invalid SigningCertURL.');
        }

        if ($query !== '' || $fragment !== '') {
            throw new BkashException('Invalid SigningCertURL.');
        }
    }

    private function validateReplayProtection(array $payload): ?string
    {
        if (!$this->hasNonEmptyScalar($payload, 'Timestamp')) {
            return 'Missing Timestamp field.';
        }

        $timestamp = strtotime((string) $payload['Timestamp']);
        if ($timestamp === false) {
            return 'Invalid Timestamp field.';
        }

        if ((time() - $timestamp) > 300) {
            return 'Webhook timestamp is too old.';
        }

        if (!$this->hasNonEmptyScalar($payload, 'MessageId')) {
            return 'Missing MessageId field.';
        }

        $messageId = trim((string) $payload['MessageId']);
        $cacheKey = 'bkash_webhook_' . $messageId;

        if (!Cache::add($cacheKey, true, 600)) {
            return 'Duplicate webhook message detected.';
        }

        return null;
    }

    private function pickString(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!$this->hasNonEmptyScalar($data, $key)) {
                continue;
            }

            return trim((string) $data[$key]);
        }

        return null;
    }

    private function hasNonEmptyScalar(array $data, string $key): bool
    {
        if (!array_key_exists($key, $data)) {
            return false;
        }

        $value = $data[$key];

        if (!is_scalar($value) && $value !== null) {
            return false;
        }

        return trim((string) $value) !== '';
    }

    private function line(string $key, string $value): string
    {
        return $key . "\n" . $value . "\n";
    }

    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            $normalizedName = strtolower((string) $name);

            if (is_array($value)) {
                $firstValue = reset($value);
                $value = $firstValue === false ? '' : $firstValue;
            }

            if (is_scalar($value) || $value === null) {
                $normalized[$normalizedName] = trim((string) $value);
                continue;
            }

            $normalized[$normalizedName] = '';
        }

        return $normalized;
    }
}
