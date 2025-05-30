<?php

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

class BlueprintTimeFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->time($this->attributeEntity->name);
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
