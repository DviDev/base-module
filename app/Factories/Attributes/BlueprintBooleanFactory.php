<?php

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

class BlueprintBooleanFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->boolean($this->attributeEntity->name)->unsigned();
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
