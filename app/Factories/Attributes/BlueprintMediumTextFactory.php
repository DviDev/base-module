<?php

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

class BlueprintMediumTextFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->mediumText($this->attributeEntity->name);
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
