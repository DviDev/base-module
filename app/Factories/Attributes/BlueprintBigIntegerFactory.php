<?php

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

class BlueprintBigIntegerFactory extends AttributeFactory
{
    public function handle(): void
    {
        if ($this->attributeEntity->auto_increment) {
            $this->table->id();

            return;
        }
        if (count($this->attributeEntity->relationship) > 0) {
            foreach ($this->attributeEntity->relationship as $relation) {
                $t = $this->table->foreignId($this->attributeEntity->name);
                $fk = $t->unsigned()->references('id')->on($relation->secondModelEntity->name);
                if (! isset($relation->on_update) || $relation->on_update == 'restrict') {
                    $fk->restrictOnUpdate();
                } else {
                    $fk->cascadeOnUpdate();
                }
                if (! isset($relation->on_delete) || $relation->on_delete == 'cascade') {
                    $fk->cascadeOnDelete();
                } else {
                    $fk->restrictOnDelete();
                }
            }
            $this->checksOtherProperties($this->attributeEntity, $t);

            return;
        }

        $t = $this->table->bigInteger($this->attributeEntity->name);
        $this->resolveUnsigned($t, $this->attributeEntity->unsigned);

        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
