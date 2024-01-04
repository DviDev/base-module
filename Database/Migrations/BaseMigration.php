<?php

namespace Modules\Base\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\Schema;
use Modules\Base\Models\BaseModel;
use Modules\DBMap\Domains\ModuleTableAttributeTypeEnum;
use Modules\Project\Entities\ProjectEntityAttribute\ProjectEntityAttributeEntityModel;
use Modules\Seguros\Services\SeguroRegraDeNegocios;

abstract class BaseMigration extends Migration
{
    public function baseUp($table_name): void
    {
        Schema::create($table_name, function (Blueprint $table) use ($table_name) {
            $entity_name = str($table_name)->replace('_', ' ')->title()->value();

            $attributes = collect((new SeguroRegraDeNegocios)->entityAttributes())->first(fn($attr) => $attr['title'] == $entity_name);
            foreach ($attributes['attributes'] as $attribute) {
                /**@var ProjectEntityAttributeEntityModel $attributeEntity*/
                $attributeEntity = $attribute['properties'];
                if ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::CHAR->value) {
                    $t = $table->char($attributeEntity->name, $attributeEntity->size);
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::DATE->value) {
                    $t = $table->date($attributeEntity->name);
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::DATETIME->value) {
                    $t = $table->dateTime($attributeEntity->name);
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::DECIMAL->value) {
                    $size = str($attributeEntity->size)->explode(',');
                    $t = $table->decimal($attributeEntity->name, $size[0], $size[1]);
                    $this->resolveUnsigned($t, $attributeEntity->unsigned);
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::DOUBLE->value) {
                    $t = $table->double($attributeEntity->name, $attributeEntity->size);
                    $this->resolveUnsigned($t, $attributeEntity->unsigned);
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::FLOAT->value) {
                    $size = str($attributeEntity->size)->explode(',');
                    $t = $table->float($attributeEntity->name, $size[0], $size[1]);
                    $this->resolveUnsigned($t, $attributeEntity->unsigned);
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::INT->value) {
                    $t = $table->integer($attributeEntity->name, $attributeEntity->size);
                    $this->resolveUnsigned($t, $attributeEntity->unsigned);
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::MEDIUMTEXT->value) {
                    $t = $table->mediumText($attributeEntity->name);
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::SMALLINT->value) {
                    $t = $table->smallInteger($attributeEntity->name);
                    $this->resolveUnsigned($t, $attributeEntity->unsigned);
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::TEXT->value) {
                    $t = $table->text($attributeEntity->name);
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::TIME->value) {
                    $t = $table->time($attributeEntity->name);
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::TIMESTAMP->value) {
                    $t = $table->timestamp($attributeEntity->name);

                    if ($attributeEntity->use_current) {
                        $t->useCurrent();
                    }
                    if ($attributeEntity->use_current_on_update) {
                        $t->useCurrentOnUpdate();
                    }
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::TINYINT->value) {
                    $t = $table->tinyInteger($attributeEntity->name, $attributeEntity->size);
                    $this->resolveUnsigned($t, $attributeEntity->unsigned);
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::VARCHAR->value) {
                    $t = $table->string($attributeEntity->name, $attributeEntity->size);
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::YEAR->value) {
                    $t = $table->year($attributeEntity->name);
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::BIGINT->value) {
                    if ($attributeEntity->auto_increment) {
                        $table->id();
                        continue;
                    }
                    if (count($attribute['relations']) > 0) {
                        foreach ($attribute['relations'] as $relation) {
                            /**@var BaseModel $model */
                            $class = $relation['entity'];
                            $second_model_table_name = $class::table();
                            $t = $table->foreignId($attributeEntity->name);
                            $fk = $t->unsigned()->references('id')->on($second_model_table_name);
                            if (!isset($relation['on_update']) || $relation['on_update'] == 'restrict') {
                                $fk->restrictOnUpdate();
                            } else {
                                $fk->cascadeOnUpdate();
                            }
                            if (!isset($relation['on_delete']) || $relation['on_delete'] == 'cascade') {
                                $fk->cascadeOnDelete();
                            } else {
                                $fk->restrictOnDelete();
                            }
                        }
                    } else {
                        $t = $table->bigInteger($attributeEntity->name);
                        $this->resolveUnsigned($t, $attributeEntity->unsigned);
                    }
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::MEDIUMINT->value) {
                    $t = $table->mediumInteger($attributeEntity->name);
                    $this->resolveUnsigned($t, $attributeEntity->name);
                } elseif ($attributeEntity->type_id == ModuleTableAttributeTypeEnum::BOOLEAN->value) {
                    $t = $table->boolean($attributeEntity->name);
                    $t->unsigned();
                }
                if (!isset($t)) {
                    dd('🤖 Missing '.ModuleTableAttributeTypeEnum::from($attributeEntity->type_id)->name.' type');
                }
                if ($attributeEntity->name == 'plano_id') {
//                    dd($attributeEntity);
                }
                $t->default($attributeEntity->default)->nullable(!$attributeEntity->required)->comment($attributeEntity->comments);
            }

            if (isset($attributes['unique'])) {
                $table->unique($attributes['unique']['columns'], $attributes['unique']['name'] ?? null);
            }
        });
    }

    protected function resolveUnsigned(ColumnDefinition $column, $unsigned): void
    {
        if (!$unsigned) {
            return;
        }
        $column->unsigned();
    }
}
