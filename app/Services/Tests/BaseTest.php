<?php

namespace Modules\Base\Services\Tests;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Modules\Base\Contracts\BaseModel;
use Tests\TestCase;

abstract class BaseTest extends TestCase
{
    public function test_table_must_exist()
    {
        $this->tableMustExist();
    }

    public function tableMustExist(): void
    {
        $this->assertTrue(
            Schema::hasTable($this->getModelClass()::table())
        );
    }

    abstract public function getModelClass(): string|BaseModel;

    public function test_table_has_expected_columns()
    {
        $this->tableHasExpectedColumns();
    }

    public function tableHasExpectedColumns(): void
    {
        $modelClass = $this->getModelClass();
        $entityClass = (new $modelClass)->modelEntity();
        foreach ($entityClass::propsArray() as $item) {
            $hasColumn = Schema::hasColumn($modelClass::table(), $item);
            if (!$hasColumn) {
                dump('💥 '.$modelClass::table()."->{$item} not found 💥");
            }
            $this->assertTrue($hasColumn);
        }
    }

    public function test_can_create_instance_of_entity()
    {
        $this->canCreateInstanceOfEntity();
    }

    public function canCreateInstanceOfEntity(): void
    {
        $model_class = $this->getModelClass();
        $entity_class = (new $model_class)->modelEntity();
        $entity = new $entity_class;
        $this->assertInstanceOf($entity_class, $entity);
    }

    public function test_can_create_instance_of_model()
    {
        $this->canCreateInstanceOfModel();
    }

    public function canCreateInstanceOfModel(): void
    {
        $model_class = $this->getModelClass();
        $model = new $model_class;
        $this->assertInstanceOf($model_class, $model);
    }

    public function test_should_save($attributes = null)
    {
        $this->shouldSave($attributes);
    }

    public function shouldSave(?array $attributes = null): void
    {
        if (! $attributes) {
            $modelClass = $this->getModelClass();
            $model = $modelClass::factory()->create();
            $attributes = $model->getAttributes();
        }
        if (isset($attributes['created_at'])) {
            $created_at = $attributes['created_at'];
            if (! is_a($created_at, Carbon::class)) {
                $created_at = new Carbon($created_at);
            }
            $attributes['created_at'] = $created_at->format('Y-m-d H:i:s');
        }
        if (isset($attributes['updated_at'])) {
            $attributes['updated_at'] = new Carbon($attributes['updated_at'])->format('Y-m-d H:i:s');
        }

        $this->assertDatabaseHas($this->getModelClass()::table(), $attributes);
    }

    protected function create(): BaseModel
    {
        return $this->getModelClass()::factory()->create();
    }

    public function test_should_update($attributes = null)
    {
        $this->shouldUpdate($attributes);
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

    protected function fixTimestamps(mixed $attributes): mixed
    {
        if (isset($attributes['created_at'])) {
            $attributes['created_at'] = is_a($attributes['created_at'], Carbon::class)
                ? $attributes['created_at']->format('Y-m-d H:i:s')
                : new Carbon($attributes['created_at'])->format('Y-m-d H:i:s');
        }
        if (isset($attributes['updated_at'])) {
            $attributes['updated_at'] = new Carbon($attributes['updated_at'])->format('Y-m-d H:i:s');
        }
        if (isset($attributes['deleted_at'])) {
            $attributes['deleted_at'] = new Carbon($attributes['deleted_at'])->format('Y-m-d H:i:s');
        }

        return $attributes;
    }

    public function test_should_delete()
    {
        $this->shouldDelete();
    }

    public function shouldDelete(): void
    {
        /** @var BaseModel $model */
        $model = $this->create();
        $model->delete();
        if (in_array(SoftDeletes::class, class_uses($model))) {
            $this->assertDatabaseHas($this->getModelClass()::table(), [$model->getKeyName() => $model->getKey(), 'deleted_at' => $model->deleted_at]);

            return;
        }
        $this->assertDatabaseMissing($this->getModelClass()::table(), [$model->getKeyName() => $model->getKey()]);
    }
}
