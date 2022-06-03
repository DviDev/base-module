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

/**
 * @author     Davi Menezes
 * @copyright  Copyright (c) 2020. (davimenezes.dev@gmail.com)
 * @see https://github.com/DaviMenezes
 * @method  BaseEntityModel findOrFail($id)
 * @method-red  static BaseEntity find($id)
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
            ExceptionBaseResponse::throw(BaseTypeErrors::ERROR_IN_RECORD_INFORMATION, null, $exception);
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

    /**@return BaseEntityModel */
    public function findOrNew($id)
    {
        $query = $this->modelClass()::query()->where('id', $id);
        return $this->firstOrNew($query)->toEntity();
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

    /**@return BaseEntity */
    public function find($id)
    {
        $class = get_called_class();
        $obj = new $class();
        /**@var BaseRepository $obj */
        $model = $obj->modelClass()::query()->find($id);
        /**@var BaseModel $model */
        return $model ? $model->toEntity() : null;
    }

    public function exists($id): bool
    {
        return $this->modelClass()::query()->where('id', $id)->exists();
    }

    protected function db(Closure $fn, ...$classes)
    {
        $params = [];
        foreach ($classes as $class) {
            /**@var BaseEntityModel $class */
            $params[] = $class::props($class::dbTable());
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
            $result = $query->$name($arguments[0]);
            if (is_a($result, BaseModel::class)) {
                return $result->toEntity();
            }
            return $result;
        }
        return null;
    }

    public static function __callStatic($name, $arguments)
    {
        $class = get_called_class();
        /**@var BaseRepository $obj */
        $obj = new $class();
        if (is_callable($obj->$name($arguments))) {
            $result = $obj->$name($arguments);
            if (is_a($result, BaseModel::class)) {
                return $result->toEntity();
            }
            return $result;
        }
        return null;
    }
}
