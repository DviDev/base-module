<?php

namespace Modules\Base\Repository;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Base\Entities\BaseEntity;
use Modules\Base\Entities\BaseEntityModel;
use Modules\Base\Models\BaseModel;
use Modules\Base\Services\Errors\BaseTypeErrors;
use Modules\Base\Services\Errors\ExceptionBaseResponse;
use phpDocumentor\Reflection\Types\Callable_;

/**
 * @author     Davi Menezes
 * @copyright  Copyright (c) 2020. (davimenezes.dev@gmail.com)
 * @see https://github.com/DaviMenezes
 * @method  BaseModel findOrFail($id)
 * @method-red  static BaseModel find($id)
 */
abstract class BaseRepository
{
    /**@var BaseModel */
    public $model;
    /**@var BaseEntityModel */
    protected $entity;

    public function model()
    {
        $model = $this->modelClass();
        return $this->model = $this->model ?? new $model();
    }

    /**
     * @param BaseEntityModel $entity
     */
    public function setEntity(BaseEntityModel $entity): void
    {
        $this->entity = $entity;
    }

    /**@return BaseModel | string */
    abstract public function modelClass();

    public function save(BaseEntityModel &$entityModel = null)
    {
        $this->entity = $entityModel ?? $this->entity;
        if (!$this->entity) {
            ExceptionBaseResponse::throw(BaseTypeErrors::UNINFORMED_ENTITY);
        }
        $modelClass = $this->modelClass();
        if (!$modelClass) {
            ExceptionBaseResponse::throw(BaseTypeErrors::ENTITY_NOT_IMPLEMENTED);
        }
//        $model = new $modelClass();
        $entity_class = $this->model()->modelEntity();
        if (!$this->entity instanceof $entity_class) {
            ExceptionBaseResponse::throw(BaseTypeErrors::ENTITY_TYPE_ERROR);
        }

        try {
            foreach ($this->entity->getAttributes() as $attr => $value) {
//                if ($attr !== $model->getKeyName() && !$this->>$this->entity->isChanged($attr)) {
//                    continue;
//                }
                $this->model->setAttribute($attr, $value);
            }
            if (isset($this->model->id)) {
                $this->model->exists = true;
            }
            $this->model->save();
            $this->entity->id = $this->model->id;
            $this->entity->model = $this->model;
            return $this->model;
        } catch (\Exception $exception) {
            if (request()->route() && request()->route()->getPrefix() === 'api') {
                ExceptionBaseResponse::throw(BaseTypeErrors::ERROR_IN_RECORD_INFORMATION, null, $exception);
            }
            throw $exception;
        }
    }

    public static function obj()
    {
        $class = static::class;
        return new $class;
    }

    /**
     * @param array $data
     * @return bool|object
     */
    public function create(array $data)
    {
        return $this->modelClass()::query()->create($data);
    }

    public static function createFn(\Closure $fn): object|bool
    {
        $entity_class = (new static())->model()->modelEntity();
        return (new static())->create($fn((new $entity_class)->props()));
    }

    public function findOrNew($id): Model|Builder
    {
        $query = $this->modelClass()::query()->where('id', $id);
        return $this->firstOrNew($query);
    }

    public function remove($id)
    {
        return $this->modelClass()::query()
            ->where('id', '=', $id)->delete();
    }

    /**@return Builder|Model|BaseModel|object */
    public function first()
    {
        return $this->modelClass()::query()->first();
    }

    public function find($id)
    {
        $class = get_called_class();
        $obj = new $class();
        return $obj->modelClass()::query()->find($id);
    }

    public function exists($id): bool
    {
        return $this->modelClass()::query()->where('id', $id)->exists();
    }

    protected function db(Closure $fn, ...$classes)
    {
        $params = [];
        /**@var BaseModel $class */
        foreach ($classes as $class) {
            /**@var BaseEntityModel $modelEntity */
            $modelEntity = (new $class())->modelEntity();
            $params[] = $modelEntity::props($class::table());
        }
        return call_user_func_array($fn, $params);
    }

    /**
     * @param Builder|\Illuminate\Database\Query\Builder $query
     * @return Builder|Model|BaseModel|object
     */
    protected function firstOrNew($query)
    {
        $model = $query->first();
        if ($model) {
            return $model;
        }
        $class = $this->modelClass();
        return new $class;
    }

    public function __call($name, $arguments)
    {
        $query = $this->modelClass()::query();
        /**@var BaseRepository $obj */
        if (method_exists($query, $name)) {
            return $query->$name($arguments[0]);
        }
        return null;
    }

    public static function __callStatic($name, $arguments)
    {
        $class = get_called_class();
        /**@var BaseRepository $obj */
        $obj = new $class();
        if (is_callable($obj->$name($arguments))) {
            return $obj->$name($arguments);
        }
        return null;
    }

    public static function deleteFn(Closure $fn)
    {
        $model = (new static)->model();
        $props = $model->modelEntity()::props(null, true);
        $term = $fn($props);
        $model->newQuery()->where($term[0], $term[1], $term[2])->delete();
    }

}
