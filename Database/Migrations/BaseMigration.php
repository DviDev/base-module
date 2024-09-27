<?php

namespace Modules\Base\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\Schema;
use Modules\DBMap\Domains\ModuleTableAttributeTypeEnum as AttributeTypeEnum;
use Modules\Project\Models\ProjectEntityAttributeModel;
use Modules\Project\Models\ProjectModuleEntityDBModel;

abstract class BaseMigration extends Migration
{
    public function baseUp(ProjectModuleEntityDBModel $entity, \Closure $fn = null): void
    {
        Schema::create($entity->name, function (Blueprint $table) use ($entity) {
            $attributes = $entity->entityAttributes()->with('relationship.secondModelEntity')->orderBy('id')->get()->all();
            foreach ($attributes as $attribute) {
                $this->createAttribute($attribute, $table);
            }
            $this->createsUniqueCompositeKey($entity, $table);
        });
        if ($fn) {
            $fn();
        }
    }

    protected function resolveUnsigned(ColumnDefinition $column, $unsigned): void
    {
        if (!$unsigned) {
            return;
        }
        $column->unsigned();
    }

    protected function createAttribute(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): void
    {
        if ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::char)) {
            $t = $table->char($attributeEntity->name, $attributeEntity->size);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::date)) {
            $t = $table->date($attributeEntity->name);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::datetime)) {
            $t = $table->dateTime($attributeEntity->name);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::decimal)) {
            $size = str($attributeEntity->size)->explode(',');
            $t = $table->decimal($attributeEntity->name, $size[0], $size[1]);
            $this->resolveUnsigned($t, $attributeEntity->unsigned);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::double)) {
            $t = $table->double($attributeEntity->name, $attributeEntity->size);
            $this->resolveUnsigned($t, $attributeEntity->unsigned);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::float)) {
            $size = str($attributeEntity->size)->explode(',');
            $t = $table->float($attributeEntity->name, $size[0], $size[1]);
            $this->resolveUnsigned($t, $attributeEntity->unsigned);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::int)) {
            $t = $table->integer($attributeEntity->name, $attributeEntity->size);
            $this->resolveUnsigned($t, $attributeEntity->unsigned);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::mediumtext)) {
            $t = $table->mediumText($attributeEntity->name);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::smallint)) {
            $t = $table->smallInteger($attributeEntity->name);
            $this->resolveUnsigned($t, $attributeEntity->unsigned);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::text)) {
            $t = $table->text($attributeEntity->name);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::longtext)) {
            $t = $table->longText($attributeEntity->name);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::time)) {
            $t = $table->time($attributeEntity->name);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::timestamp)) {
            $t = $table->timestamp($attributeEntity->name);

            if ($attributeEntity->use_current) {
                $t->useCurrent();
            }
            if ($attributeEntity->use_current_on_update) {
                $t->useCurrentOnUpdate();
            }
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::tinyint)) {
            $t = $table->tinyInteger($attributeEntity->name, $attributeEntity->size);
            $this->resolveUnsigned($t, $attributeEntity->unsigned);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::varchar)) {
            $t = $table->string($attributeEntity->name, $attributeEntity->size);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::year)) {
            $t = $table->year($attributeEntity->name);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::bigint)) {
            if ($attributeEntity->auto_increment) {
                $table->id();
                return;
            }
            if (count($attributeEntity->relationship) > 0) {
                foreach ($attributeEntity->relationship as $relation) {
                    $t = $table->foreignId($attributeEntity->name);
                    $fk = $t->unsigned()->references('id')->on($relation->secondModelEntity->name);
                    if (!isset($relation->on_update) || $relation->on_update == 'restrict') {
                        $fk->restrictOnUpdate();
                    } else {
                        $fk->cascadeOnUpdate();
                    }
                    if (!isset($relation->on_delete) || $relation->on_delete == 'cascade') {
                        $fk->cascadeOnDelete();
                    } else {
                        $fk->restrictOnDelete();
                    }
                }
            } else {
                $t = $table->bigInteger($attributeEntity->name);
                $this->resolveUnsigned($t, $attributeEntity->unsigned);
            }
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::mediumint)) {
            $t = $table->mediumInteger($attributeEntity->name);
            $this->resolveUnsigned($t, $attributeEntity->name);
        } elseif ($attributeEntity->type_id == AttributeTypeEnum::getId(AttributeTypeEnum::boolean)) {
            $t = $table->boolean($attributeEntity->name);
            $t->unsigned();
        }
        if (!isset($t)) {
            dd('🤖 Missing ' . AttributeTypeEnum::from($attributeEntity->type_id)->name . ' type');
        }
        if ($attributeEntity->unique) {
            $t->unique();
        }
        if ($attributeEntity->index) {
            $t->index();
        }
        $t->default($attributeEntity->default)->nullable(!$attributeEntity->required)->comment($attributeEntity->comments);
    }

    function createsUniqueCompositeKey(ProjectModuleEntityDBModel $entity, Blueprint $table): void
    {
        if ($columns = $entity->getAttributeUniques()->get()->pluck('name')->all()) {
            $table->unique(columns: $columns, name: collect($columns)->join('_'));
        }
    }

    function createUniqueAloneKeys(ProjectModuleEntityDBModel $entity, Blueprint $table): void
    {
        if ($columns = $entity->getAttributeUniques()->whereNull('multiple')->get()->pluck('name')->all()) {
            foreach ($columns as $column) {
                $table->unique(columns: $column, name: $column);
            }
        }
    }
}
