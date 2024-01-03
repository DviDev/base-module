<?php

namespace Modules\Base\Database\Migrations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Stringable;
use Modules\Base\Models\BaseModel;
use Modules\DBMap\Domains\ModuleTableAttributeTypeEnum;
use Modules\Project\Models\ProjectModuleEntityDBModel;

abstract class BaseMigration extends Migration
{
    public function createAttributes(Blueprint $table, $table_name): void
    {
        $entity_name = str($table_name)->replace('_', ' ')->title()->value();
        $entity = ProjectModuleEntityDBModel::firstWhere('name', $entity_name);
        if (!$entity) {
            dd($entity_name);
        }
        foreach ($entity->entityAttributes as $attribute) {
            if ($attribute->typeEnum() == ModuleTableAttributeTypeEnum::CHAR) {
                $table->char($attribute->name, $attribute->size)->nullable(!$attribute->required);
                continue;
            }
            if ($attribute->typeEnum() == ModuleTableAttributeTypeEnum::DATE) {
                $table->date($attribute->name)->nullable(!$attribute->required);
                continue;
            }
            if ($attribute->typeEnum() == ModuleTableAttributeTypeEnum::DATETIME) {
                $table->dateTime($attribute->name)->nullable(!$attribute->required);
                continue;
            }
            if ($attribute->typeEnum() == ModuleTableAttributeTypeEnum::DECIMAL) {
                $size = str($attribute['size'])->explode(',');
                $table->decimal($attribute->name, $size[0], $size[1])->nullable(!$attribute->required);
                continue;
            }
            if ($attribute->typeEnum() == ModuleTableAttributeTypeEnum::MEDIUMTEXT) {
                $table->mediumText($attribute->name)->nullable(!$attribute->required);
                continue;
            }
            if ($attribute->typeEnum() == ModuleTableAttributeTypeEnum::SMALLINT) {
                $table->smallInteger($attribute->name)->nullable(!$attribute->required);
                continue;
            }
            if ($attribute->typeEnum() == ModuleTableAttributeTypeEnum::TEXT) {
                $table->text($attribute->name)->nullable(!$attribute->required);
                continue;
            }
            if ($attribute->typeEnum() == ModuleTableAttributeTypeEnum::TIME) {
                $table->time($attribute->name)->nullable(!$attribute->required);
                continue;
            }
            if ($attribute->typeEnum() == ModuleTableAttributeTypeEnum::TIMESTAMP) {
                $table->timestamp($attribute->name)->nullable(!$attribute->required);
                continue;
            }
            if ($attribute->typeEnum() == ModuleTableAttributeTypeEnum::TINYINT) {
                $table->tinyInteger($attribute->name)->nullable(!$attribute->required)->unsigned();
                continue;
            }
            if ($attribute->typeEnum() == ModuleTableAttributeTypeEnum::VARCHAR) {
                $table->string($attribute->name, $attribute->size)->nullable(!$attribute->required);
                continue;
            }
            if ($attribute->typeEnum() == ModuleTableAttributeTypeEnum::YEAR) {
                $table->year($attribute->name)->nullable(!$attribute->required);
                continue;
            }
            if ($attribute->typeEnum() == ModuleTableAttributeTypeEnum::BIGINT) {
                if ($attribute->auto_increment) {
                    $table->id();
                    continue;
                }
                if ($attribute->relationship()->count() > 0) {
                    foreach ($attribute->relationship as $relation) {
                        /**@var BaseModel $model*/
                        $second_model_table_name = str($relation->secondModelEntity->name)->lower()->snake()->value();

                        $tb = $table->foreignId($attribute->name)->references('id')->on($second_model_table_name);
                        if ($relation->on_update == 'restrict') {
                            $tb->restrictOnUpdate();
                        } else {
                            $tb->cascadeOnUpdate();
                        }

                        if($relation->on_delete == 'cascade') {
                            $tb->cascadeOnDelete();
                        } else {
                            $tb->restrictOnDelete();
                        }
                    }
                    continue;
                }
                $table->bigInteger($attribute->name)->nullable(!$attribute->required);
                continue;
            }
            if ($attribute->typeEnum() == ModuleTableAttributeTypeEnum::MEDIUMINT) {
                $table->mediumInteger($attribute->name)->nullable(!$attribute->required);
            }
        }
    }
}
