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

    public function tableMustExist(): void
    {
        $this->assertTrue(
            Schema::hasTable($this->getModelClass()::table())
        );
    }

    public function tableHasExpectedColumns(): void
    {
        $this->assertTrue(
            Schema::hasColumns($this->getModelClass()::table(),
                $this->getEntityClass()::propsArray()
            )
        );
    }

    public function canCreateInstanceOfEntity(): void
    {
        $entity_class = $this->getEntityClass();
        $entity = new $entity_class;
        $this->assertInstanceOf($entity_class, $entity);
    }

    public function canCreateInstanceOfModel(): void
    {
        $model_class = $this->getModelClass();
        $model = new $model_class;
        $this->assertInstanceOf($model_class, $model);
    }

    public function shouldSave(?array $attributes = null): void
    {
        if (! $attributes) {
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

    public function shouldUpdate(?array $attributes = null): void
    {
        if (! $attributes) {
            $model = $this->create();
            $make = $this->getModelClass()::factory()->make();
            $model->update($make->attributesToArray());
            $attributes = $model->getAttributes();
        }
        $attributes = $this->fixTimestamps($attributes);
        $this->assertDatabaseHas($this->getModelClass()::table(), $attributes);
    }

    public function shouldDelete(): void
    {
        $model = $this->create();
        $model->delete();
        $attributes = $model->attributesToArray();
        $attributes = $this->fixTimestamps($attributes);
        $this->assertDatabaseMissing($this->getModelClass()::table(), $attributes);
    }

    protected function create(): BaseModel
    {
        return $this->getModelClass()::factory()->create();
    }

    protected function fixTimestamps(mixed $attributes): mixed
    {
        if (isset($attributes['created_at'])) {
            $attributes['created_at'] = is_a($attributes['created_at'], Carbon::class)
                ? $attributes['created_at']->format('Y-m-d H:i:s')
                : (new Carbon($attributes['created_at']))->format('Y-m-d H:i:s');
        }
        if (isset($attributes['updated_at'])) {
            $attributes['updated_at'] = (new Carbon($attributes['updated_at']))->format('Y-m-d H:i:s');
        }

        return $attributes;
    }
}
