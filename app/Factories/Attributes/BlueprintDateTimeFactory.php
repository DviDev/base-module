<?php

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

class BlueprintDateTimeFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->dateTime($this->attributeEntity->name);
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
