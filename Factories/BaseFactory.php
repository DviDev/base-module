<?php

namespace Modules\Base\Factories;

use Modules\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

abstract class BaseFactory extends Factory
{
    public function createFn(\Closure $fn)
    {
        /**@var BaseModel $model*/
        $model = new $this->model;
        $entity_class = $model->modelEntity();
        $entity = new $entity_class;
        return parent::create($fn($entity->props()));
    }
}
