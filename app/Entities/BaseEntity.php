<?php

namespace Modules\Base\Entities;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Modules\Base\Contracts\EntityInterface;

abstract class BaseEntity implements Arrayable, EntityInterface, JsonSerializable
{
    use Props;

    public ?string $table_alias = null;

    protected array $attributes_ = [];

    protected array $changed = [];

    use EntityImplementation;

    public function __construct(...$attributes)
    {
        $this->attributes_ = $attributes[0] ?? [];
    }

    /**
     * alias to toArray method
     */
    public function getAttributes(): array
    {
        return $this->attributes_;
    }

    public function isChanged(string $attribute): bool
    {
        return in_array($attribute, $this->changed);
    }

    public function __get(string $name)
    {
        return $this->attributes_[$name] ?? null;
    }

    public function __set(string $name, mixed $value)
    {
        $this->set($name, $value);
    }

    public function __call(string $method, array $parameters): BaseEntity
    {
        if (str_starts_with($method, 'set')) {
            $prop = strtolower(substr($method, 3));

            return $this->set($prop, $parameters[0]);
        }
        if (is_a($this, BaseEntityModel::class) && $method == 'save') {
            $this->repository()->save();
        }

        return $this;
    }

    public function __isset(int|string $name)
    {
        return array_key_exists($name, $this->attributes_);
    }

    public function __toString()
    {
        return json_encode($this->attributes_, JSON_UNESCAPED_UNICODE);
    }

    public function set(string $prop, mixed $value, bool $model_to_entity = false): BaseEntity
    {
        $changing = ! isset($this->attributes_[$prop]) ||
            (isset($this->attributes_[$prop]) && $this->attributes_[$prop] !== $value);
        if ($changing) {
            $this->attributes_[$prop] = $value;
        }
        if (! $model_to_entity && $changing) {
            $this->changed[] = $prop;
        }

        return $this;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return $this->attributes_;
    }

    public function arrayValues(): array
    {
        return array_values($this->attributes_);
    }

    public function changed(): array
    {
        return $this->changed;
    }
}
