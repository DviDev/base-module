<?php

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

class BlueprintDateFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->date($this->attributeEntity->name);
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
