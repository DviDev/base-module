<?php

namespace Modules\Base\Contracts;

use Illuminate\Contracts\Support\Arrayable;

abstract class BaseDTO implements Arrayable
{
    abstract public static function fromArray(array $array): BaseDTO;

    public function toArray(): array
    {
        return get_object_vars( $this);
    }
}
