<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Facades;

use Illuminate\Support\Facades\Facade;

class Bkash extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'bkash';
    }
}
