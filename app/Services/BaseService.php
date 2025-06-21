<?php

namespace Modules\Base\Services;

use Illuminate\Contracts\Support\Arrayable;

class BaseService implements Arrayable
{
    public array $attributes;

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function __get(int|string$name)
    {
        return $this->attributes[$name] ?? null;
    }
}
