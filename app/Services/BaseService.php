<?php

declare(strict_types=1);

namespace Modules\Base\Services;

use Illuminate\Contracts\Support\Arrayable;

final class BaseService implements Arrayable
{
    public array $attributes;

    public function __get(int|string $name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
