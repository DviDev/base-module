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

    protected function createAttribute(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): void
    {
        if ($this->getChar($attributeEntity, $table)) {
            return;
        }
        if ($this->getDate($attributeEntity, $table)) {
            return;
        }
        if ($this->getDateTime($attributeEntity, $table)) {
            return;
        }
        if ($this->getDecimal($attributeEntity, $table)) {
            return;
        }
        if ($this->getDouble($attributeEntity, $table)) {
            return;
        }
        if ($this->getFloat($attributeEntity, $table)) {
            return;
        }
        if ($this->getInteger($attributeEntity, $table)) {
            return;
        }
        if ($this->getMediumText($attributeEntity, $table)) {
            return;
        }
        if ($this->getSmallInteger($attributeEntity, $table)) {
            return;
        }
        if ($this->getText($attributeEntity, $table)) {
            return;
        }
        if ($this->getLongText($attributeEntity, $table)) {
            return;
        }
        if ($this->getTime($attributeEntity, $table)) {
            return;
        }
        if ($this->getTimestamp($attributeEntity, $table)) {
            return;
        }
        if ($this->getTinyInteger($attributeEntity, $table)) {
            return;
        }
        if ($this->getString($attributeEntity, $table)) {
            return;
        }
        if ($this->getYear($attributeEntity, $table)) {
            return;
        }
        if ($this->getBitInteger($attributeEntity, $table)) {
            return;
        }
        if ($this->getMediumInteger($attributeEntity, $table)) {
            return;
        }
        if ($this->getBoolean($attributeEntity, $table)) {
            return;
        }

        dd('🤖 Missing ' . AttributeTypeEnum::from($attributeEntity->type_id)->name . ' type');
    }

    protected function getChar(ProjectEntityAttributeModel $attributeEntity, Blueprint $table)
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::char)) {
            return null;
        }
        return $table->char($attributeEntity->name, $attributeEntity->size);
    }

    protected function getDate(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::date)) {
            return null;
        }
        $t = $table->date($attributeEntity->name);
        $this->checkAnotherProperties($attributeEntity, $t);
        return $t;
    }

    protected function checkAnotherProperties(ProjectEntityAttributeModel $attributeEntity, ColumnDefinition $t): void
    {
        if ($attributeEntity->unique) {
            $t->unique();
        }
        if ($attributeEntity->index) {
            $t->index();
        }
        $t->default($attributeEntity->default)->nullable(!$attributeEntity->required)->comment($attributeEntity->comments);
    }

    protected function getDateTime(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::datetime)) {
            return null;
        }
        $t = $table->dateTime($attributeEntity->name);
        $this->checkAnotherProperties(
            $attributeEntity,
            $t
        );
        return $t;
    }

    protected function getDecimal(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::decimal)) {
            return null;
        }
        $size = str($attributeEntity->size)->explode(',');
        $t = $table->decimal($attributeEntity->name, $size[0], $size[1]);
        $this->resolveUnsigned($t, $attributeEntity->unsigned);

        $this->checkAnotherProperties($attributeEntity, $t);

        return $t;
    }

    protected function resolveUnsigned(ColumnDefinition $column, $unsigned): void
    {
        if (!$unsigned) {
            return;
        }
        $column->unsigned();
    }

    protected function getDouble(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::double)) {
            return null;
        }
        $t = $table->double($attributeEntity->name);
        $this->resolveUnsigned($t, $attributeEntity->unsigned);

        $this->checkAnotherProperties($attributeEntity, $t);

        return $t;
    }

    protected function getFloat(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::float)) {
            return null;
        }
        $size = str($attributeEntity->size)->explode(',');
        $t = $table->float($attributeEntity->name, $size[0]);
        $this->resolveUnsigned($t, $attributeEntity->unsigned);
        $this->checkAnotherProperties($attributeEntity, $t);

        return $t;
    }

    protected function getInteger(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::int)) {
            return null;
        }
        $t = $table->integer($attributeEntity->name, $attributeEntity->size);
        $this->resolveUnsigned($t, $attributeEntity->unsigned);
        $this->checkAnotherProperties($attributeEntity, $t);

        return $t;
    }

    protected function getMediumText(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::mediumtext)) {
            return null;
        }
        $t = $table->mediumText($attributeEntity->name);
        $this->checkAnotherProperties($attributeEntity, $t);

        return $t;
    }

    protected function getSmallInteger(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::smallint)) {
            return null;
        }
        $t = $table->smallInteger($attributeEntity->name);
        $this->resolveUnsigned($t, $attributeEntity->unsigned);
        $this->checkAnotherProperties($attributeEntity, $t);

        return $t;
    }

    protected function getText(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::text)) {
            return null;
        }
        $t = $table->text($attributeEntity->name);
        $this->checkAnotherProperties($attributeEntity, $t);

        return $t;
    }

    protected function getLongText(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::longtext)) {
            return null;
        }
        $t = $table->longText($attributeEntity->name);
        $this->checkAnotherProperties($attributeEntity, $t);

        return $t;
    }

    protected function getTime(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::time)) {
            return null;
        }
        $t = $table->time($attributeEntity->name);
        $this->checkAnotherProperties($attributeEntity, $t);

        return $t;
    }

    protected function getTimestamp(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::timestamp)) {
            return null;
        }
        $t = $table->timestamp($attributeEntity->name);

        if ($attributeEntity->use_current) {
            $t->useCurrent();
        }
        if ($attributeEntity->use_current_on_update) {
            $t->useCurrentOnUpdate();
        }
        $this->checkAnotherProperties($attributeEntity, $t);

        return $t;
    }

    protected function getTinyInteger(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::tinyint)) {
            return null;
        }
        $t = $table->tinyInteger($attributeEntity->name, $attributeEntity->size);
        $this->resolveUnsigned($t, $attributeEntity->unsigned);
        $this->checkAnotherProperties($attributeEntity, $t);
        return $t;
    }

    protected function getString(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::varchar)) {
            return null;
        }
        $t = $table->string($attributeEntity->name, $attributeEntity->size);

        $this->checkAnotherProperties($attributeEntity, $t);

        return $t;
    }

    protected function getYear(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::year)) {
            return null;
        }
        $t = $table->year($attributeEntity->name);
        $this->checkAnotherProperties($attributeEntity, $t);

        return $t;
    }

    protected function getBitInteger(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::bigint)) {
            return null;
        }
        if ($attributeEntity->auto_increment) {
            return $table->id();
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
            return $t;
        }

        $t = $table->bigInteger($attributeEntity->name);
        $this->resolveUnsigned($t, $attributeEntity->unsigned);

        $this->checkAnotherProperties($attributeEntity, $t);

        return $t;
    }

    protected function getMediumInteger(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::mediumint)) {
            return null;
        }
        $t = $table->mediumInteger($attributeEntity->name);
        $this->resolveUnsigned($t, $attributeEntity->name);
        $this->checkAnotherProperties($attributeEntity, $t);

        return $t;
    }

    protected function getBoolean(ProjectEntityAttributeModel $attributeEntity, Blueprint $table): ?ColumnDefinition
    {
        if ($attributeEntity->type_id !== AttributeTypeEnum::getId(AttributeTypeEnum::boolean)) {
            return null;
        }
        $t = $table->boolean($attributeEntity->name)->unsigned();
        $this->checkAnotherProperties($attributeEntity, $t);

        return $t;
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
