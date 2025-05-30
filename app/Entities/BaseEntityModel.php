<?php

namespace Modules\Base\Entities;

use Modules\Base\Contracts\EntityModelInterface;
use Modules\Base\Models\BaseModel;
use Modules\Base\Repository\BaseRepository;
use Modules\Base\Services\Errors\BaseTypeErrors;
use Modules\Base\Services\Errors\ExceptionBaseResponse;

/**
 * @method $this save()
 * @method static self new()
 */
abstract class BaseEntityModel extends BaseEntity implements EntityModelInterface
{
    /**@var self|BaseModel */
    public $model;

    public string $table;

    public function __construct(...$attributes)
    {
        parent::__construct($attributes[0] ?? []);
        $this->table = $this->table();
    }

    protected static function setTable($table, $alias = null): string
    {
        return $table . ($alias ? ' as ' . $alias : '');
    }

    protected function repositoryClass(): ?string
    {
        return null;
    }

    public function repository(BaseModel $model = null): BaseRepository
    {
        if (!$class = $this->repositoryClass()) {
            throw new \Exception(BaseTypeErrors::errorMessages()[BaseTypeErrors::REPOSITORY_CLASS_UNINFORMED]. '('.get_called_class().')');
        }
        /**@var BaseRepository $repository */
        $repository = new $class($model);
        $repository->setEntity($this);
        if (!is_a($repository, BaseRepository::class)) {
            ExceptionBaseResponse::throw(BaseTypeErrors::ENTITY_TYPE_ERROR, 'A classe '.$class.' deve ser do tipo ' .
                BaseRepository::class);
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

    public function table(): string
    {
        if ($this->repositoryClass()) {
            $repository = $this->repository();
            $table = $repository->modelClass()::table();
            return !$this->table_alias ? $table : $table.' as ' .$this->table_alias;
        }
        $vars = str(get_called_class())->explode('\\');
        $entity = $vars[3];
        $vars[2] = 'Models';
        $vars[3] = $entity . 'Model';
        unset($vars[4]);
        /**@var BaseModel $model_class*/
        $model_class = $vars->join('\\');
        $table = $model_class::table();
        return !$this->table_alias ? $table : $table.' as ' .$this->table_alias;
    }
}
