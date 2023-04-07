<?php

namespace Modules\Base\Services\Tests;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Modules\Base\Entities\BaseEntityModel;
use Modules\Base\Models\BaseModel;
use Tests\TestCase;

abstract class BaseTest extends TestCase
{
    abstract public function getEntityClass(): string|BaseEntityModel;

    abstract public function getModelClass(): string|BaseModel;

    public function testTableMustExist()
    {
        $this->assertTrue(
            Schema::hasTable($this->getModelClass()::table())
        );
    }

    public function testTableHasExpectedColumns()
    {
        $this->assertTrue(
            Schema::hasColumns($this->getModelClass()::table(),
                $this->getEntityClass()::propsArray()
            )
        );
    }

    public function testCanCreateInstanceOfEntity()
    {
        $entity_class = $this->getEntityClass();
        $entity = new $entity_class();
        $this->assertInstanceOf($entity_class, $entity);
    }

    public function testCanCreateInstanceOfModel()
    {
        $model_class = $this->getModelClass();
        $model = new $model_class();
        $this->assertInstanceOf($model_class, $model);
    }

    public function testShouldSave($attributes = null)
    {
        if (!$attributes) {
            $modelClass = $this->getModelClass();
            $model = $modelClass::factory()->create();
            $attributes = $model->getAttributes();
        }
        if (isset($attributes['created_at'])) {
            $attributes['created_at'] = is_a($attributes['created_at'], Carbon::class)
                ? $attributes['created_at']->format('Y-m-d H:i:s')
//                : $attributes['created_at'];
                : (new Carbon($attributes['created_at']))->format('Y-m-d H:i:s');
        }
        if (isset($attributes['updated_at'])) {
            $attributes['updated_at'] = (new Carbon($attributes['updated_at']))->format('Y-m-d H:i:s');
        }

        $this->assertDatabaseHas($this->getModelClass()::table(), $attributes);
    }

    public function testShouldUpdate($attributes = null)
    {
        if (!$attributes) {
            $model = $this->create();
            $make = $this->getModelClass()::factory()->make();
            $model->update($make->attributesToArray());
            $attributes = $model->getAttributes();
        }
        if (isset($attributes['created_at'])) {
            $attributes['created_at'] = is_a($attributes['created_at'], Carbon::class)
                ? $attributes['created_at']->format('Y-m-d H:i:s')
                : (new Carbon($attributes['created_at']))->format('Y-m-d H:i:s');
        }
        if (isset($attributes['updated_at'])) {
            $attributes['updated_at'] = (new Carbon($attributes['updated_at']))->format('Y-m-d H:i:s');
        }
        $this->assertDatabaseHas($this->getModelClass()::table(), $attributes);
    }

    public function testShouldDelete()
    {
        $model = $this->create();
        $model->delete();
        $this->assertDatabaseMissing($this->getModelClass()::table(), $model->attributesToArray());
    }

    protected function create():BaseModel
    {
        return $this->getModelClass()::factory()->create();
    }
}
