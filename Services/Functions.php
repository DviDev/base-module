<?php

namespace Modules\Base\Services;

use Modules\Base\Services\Date\DateFn;

class Functions
{
    static public function date()
    {
        return new DateFn();
    }

}
