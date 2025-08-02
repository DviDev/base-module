<?php

namespace Modules\Base\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Base\Entities\BaseEntityModel;

/**
 * @extends BaseModel
 */
trait BaseModelImplementation
{
    public function __construct(array $attributes = [])
    {
        $this->table = static::table();
        $this->timestamps = false;
        parent::__construct($attributes);
    }

    /**@return self */
    public static function createFn(\Closure $fn)
    {
        $entity_class = (new static)->modelEntity();
        $attributes = $fn($entity_class::props());

        return self::query()->create($attributes);
    }

    public static function whereFn(\Closure $fn): Builder
    {
        $entity_class = (new static)->modelEntity();
        $arrays = $fn($entity_class::props());
        $builder = self::query();

        foreach ($arrays as $array) {
            $item1 = $array[0];
            $item2 = ! isset($array[2]) ? '=' : $array[1];
            $item3 = $array[2] ?? $array[1];
            $builder->where($item1, $item2, $item3);
        }

        return $builder;
    }

    protected static function booted()
    {
        static::saving(function (Model $model) {
            $props = $model->props()->toArray();
            if (in_array('created_at', $props) && ! isset($model->created_at)) {
                $model->created_at = now();
            }
            if (in_array('updated_at', $props) && ! isset($model->updated_at)) {
                $model->updated_at = now();
            }
        });
        static::creating(function (Model $model) {
            if (in_array('created_at', $model->props()->toArray()) && ! isset($model->created_at)) {
                $model->created_at = now();
            }
        });

        static::updating(function (Model $model) {
            if (in_array('updated_at', $model->props()->toArray()) && $model->isDirty()) {
                $model->updated_at = now();
            }
        });

        parent::booted();
    }

    public function props($alias = null, $refresh = false): object
    {
        return $this->modelEntity()::props($alias, $refresh);
    }

    protected static function dbTable($table, $alias = null): string
    {
        return $table.($alias ? ' as '.$alias : '');
    }

    public function toEntity(): BaseEntityModel
    {
        $entity_class = $this->modelEntity();

        /** @var BaseEntityModel $entity */
        $entity = $entity_class::props();
        $entity->model = $this;

        foreach ($entity->toArray() as $prop => $attribute) {
            $entity->set($prop, $this->$prop, true);
        }

        return $entity;
    }

    public function repository()
    {
        $entity = $this->modelEntity();

        return (new $entity)->repository($this);
    }
}
