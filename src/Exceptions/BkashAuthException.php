<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Exceptions;

class BkashAuthException extends BkashException
{
    public static function fromMessage(string $message, int $code = 0): self
    {
        return new self($message, $code);
    }
}
