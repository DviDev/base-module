<?php

namespace Modules\Base\Services\Tests;

use Illuminate\Support\Facades\Schema;
use Modules\Base\Entities\BaseEntityModel;
use Modules\Base\Models\BaseModel;
use Tests\TestCase;

abstract class BaseTest extends TestCase
{
    abstract public function getEntityClass(): string|BaseEntityModel;

    abstract public function getModelClass(): string|BaseModel;

    /**
     * @test
     */
    public function tableMustExist()
    {
        $this->assertTrue(
            Schema::hasTable($this->getModelClass()::table())
        );
    }

    /**
     * @test
     */
    public function tableHasExpectedColumns()
    {
        $this->assertTrue(
            Schema::hasColumns($this->getModelClass()::table(),
                $this->getEntityClass()::propsArray()
            )
        );
    }

    /**
     * @test
     */
    public function canCreateInstanceOfEntity()
    {
        $entity_class = $this->getEntityClass();
        $entity = new $entity_class();
        $this->assertInstanceOf($entity_class, $entity);
    }

    /**
     * @test
     */
    public function canCreateInstanceOfModel()
    {
        $model_class = $this->getModelClass();
        $model = new $model_class();
        $this->assertInstanceOf($model_class, $model);
    }

    /**
     * @test
     */
    public function shouldSave()
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::factory()->create();
        $this->assertDatabaseHas($modelClass::table(), $model->attributesToArray());
    }

    /**
     * @test
     */
    public function shouldUpdate()
    {
        $model = $this->getModelClass()::factory()->create();
        $make = $this->getModelClass()::factory()->make();
        $model->update($make->attributesToArray());
        $this->assertDatabaseHas($this->getModelClass()::table(), $model->attributesToArray());
    }

    /**
     * @test
     */
    public function shouldDelete()
    {
        $model = $this->getModelClass()::factory()->create();
        $model->delete();
        $this->assertDatabaseMissing($this->getModelClass()::table(), $model->attributesToArray());
    }
}
