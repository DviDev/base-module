<?php

declare(strict_types=1);

namespace Modules\Base\Services;

use Modules\Base\Services\Date\DateFn;

final class Functions
{
    public static function date(): DateFn
    {
        return new DateFn;
    }
}
