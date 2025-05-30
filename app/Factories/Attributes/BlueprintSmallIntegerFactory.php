<?php

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

class BlueprintSmallIntegerFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->smallInteger($this->attributeEntity->name);
        $this->resolveUnsigned($t, $this->attributeEntity->unsigned);
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
