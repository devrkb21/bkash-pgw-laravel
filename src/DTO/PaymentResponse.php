<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\DTO;

class PaymentResponse
{
    public function __construct(
        public readonly ?string $paymentId,
        public readonly ?string $agreementId,
        public readonly ?string $bkashUrl,
        public readonly ?string $trxId,
        public readonly array $raw
    ) {
    }

    public static function fromArray(array $data): self
    {
        $paymentId = self::pickString($data, ['paymentID', 'paymentId', 'payment_id']);
        $agreementId = self::pickString($data, ['agreementID', 'agreementId', 'agreement_id']);
        $bkashUrl = self::pickString($data, ['bkashURL', 'bKashURL', 'bkashUrl']);
        $trxId = self::pickString($data, ['trxID', 'trxId', 'trxid', 'trx_id']);

        return new self($paymentId, $agreementId, $bkashUrl, $trxId, $data);
    }

    public function toArray(): array
    {
        $response = $this->raw;

        if ($this->paymentId !== null) {
            $response['paymentID'] = $this->paymentId;
            $response['paymentId'] = $this->paymentId;
        }

        if ($this->agreementId !== null) {
            $response['agreementID'] = $this->agreementId;
            $response['agreementId'] = $this->agreementId;
        }

        if ($this->bkashUrl !== null) {
            $response['bkashURL'] = $this->bkashUrl;
        }

        if ($this->trxId !== null) {
            $response['trxID'] = $this->trxId;
        }

        return $response;
    }

    private static function pickString(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = trim((string) $data[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
