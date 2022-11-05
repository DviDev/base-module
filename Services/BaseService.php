<?php

namespace Modules\Base\Services;

use Illuminate\Contracts\Support\Arrayable;

class BaseService implements Arrayable
{
    public $attributes;

    public function toArray()
    {
        return $this->attributes;
    }

    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }
}
