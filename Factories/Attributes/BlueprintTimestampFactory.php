<?php

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

class BlueprintTimestampFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->timestamp($this->attributeEntity->name);

        if ($this->attributeEntity->use_current) {
            $t->useCurrent();
        }
        if ($this->attributeEntity->use_current_on_update) {
            $t->useCurrentOnUpdate();
        }
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
