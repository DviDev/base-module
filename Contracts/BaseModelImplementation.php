<?php

namespace Modules\Base\Contracts;

use Modules\Base\Entities\BaseEntityModel;
use Modules\Base\Models\BaseModel;

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

    /** @template T
     * @param class-string<T>|null $entity_class
     * @return T
     * @throws \ReflectionException
     */
    public function toEntity(T $entity_class = null)
    {
        $entity_class = $this->modelEntity();

        /**@var BaseEntityModel $entity */
        $entity = $entity_class::props();
        $entity->model = $this;

        foreach ($entity->toArray() as $prop => $attribute) {
            $entity->set($prop, $this->$prop, true);
        }

        /**@var $entity T */
        return $entity;
    }

    protected static function dbTable($table, $alias = null): string
    {
        return $table . ($alias ? ' as ' . $alias : '');
    }

    public function repository()
    {
        $entity = $this->modelEntity();
        return (new $entity)->repository();
    }

    public function props(): object
    {
        return $this->modelEntity()::props();
    }

    public static function createFn(\Closure $fn)
    {
        $entity_class = (new static())->modelEntity();
        $entity = new $entity_class();
        return $fn($entity->props());
    }
}
