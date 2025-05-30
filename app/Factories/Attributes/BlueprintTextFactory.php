<?php

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

class BlueprintTextFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->text($this->attributeEntity->name);
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
