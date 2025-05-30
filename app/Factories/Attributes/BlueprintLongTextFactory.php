<?php

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

class BlueprintLongTextFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->longText($this->attributeEntity->name);
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
