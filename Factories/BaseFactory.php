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

    /**
     * @return string
     */
    protected function createName(): string
    {
        return $this->removeAbreviations($this->faker->unique()->name)->trim()->value();
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getEmail(string $name): string
    {
        return str(iconv('UTF-8', 'ASCII//TRANSLIT', $this->removeAbreviations($name)))
                ->lower()->explode(' ')->shift(3)->join('_') . '@gmail.com';
    }

    /**
     * @return \Illuminate\Support\Stringable
     */
    protected function removeAbreviations(string $str)
    {
        return str($str)
            ->replace(['Dr.', 'Dra.', 'Sr.', 'Sra.', 'Srta.', 'Jr.', ' da', ' de'], '')->trim();
    }
}
