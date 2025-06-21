<?php

namespace Modules\Base\Services;

use Modules\Base\Services\Date\DateFn;

class Functions
{
    public static function date(): DateFn
    {
        return new DateFn;
    }
}
