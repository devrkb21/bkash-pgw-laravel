<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Exceptions;

class BkashPaymentException extends BkashException
{
    public static function fromMessage(string $message, int $code = 0): self
    {
        return new self($message, $code);
    }
}
