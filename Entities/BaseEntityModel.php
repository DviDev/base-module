<?php

namespace Modules\Base\Entities;

use Modules\Base\Contracts\EntityModelInterface;
use Modules\Base\Models\BaseModel;
use Modules\Base\Repository\BaseRepository;
use Modules\Base\Services\Errors\BaseTypeErrors;
use Modules\Base\Services\Errors\ExceptionBaseResponse;

/**
 * @method save()
 * @method static self new()
*/
abstract class BaseEntityModel extends BaseEntity implements EntityModelInterface
{
    /**@var self|BaseModel */
    public $model;

    protected static function setTable($table, $alias = null): string
    {
        return $table . ($alias ? ' as ' . $alias : '');
    }

    abstract protected function repositoryClass(): string;

    public function repository()
    {
        $class = $this->repositoryClass();
        /**@var BaseRepository $repository */
        $repository = new $class();
        $repository->setEntity($this);
        if (!is_a($repository, BaseRepository::class)) {
            ExceptionBaseResponse::throw(BaseTypeErrors::ENTITY_TYPE_ERROR, 'A classe deve ser do tipo ' . BaseRepository::class);
        }
        return $repository;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->attributes_)) {
            return $this->attributes_[$name];
        }
        return $this->model->$name ?? null;
    }
}
