<?php

declare(strict_types=1);

namespace Modules\Base\Contracts;

use Illuminate\Contracts\Support\Arrayable;

abstract class BaseDTO implements Arrayable
{
    abstract public static function fromArray(array $array): self;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
