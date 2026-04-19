<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Exceptions;

use RuntimeException;

class BkashException extends RuntimeException
{
    public static function fromResponse(int $statusCode, string $body): self
    {
        return new self(
            sprintf('bKash API request failed with status %d: %s', $statusCode, $body),
            $statusCode
        );
    }
}
