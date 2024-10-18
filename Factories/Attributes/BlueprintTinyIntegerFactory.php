<?php

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

class BlueprintTinyIntegerFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->tinyInteger($this->attributeEntity->name, $this->attributeEntity->size);
        $this->resolveUnsigned($t, $this->attributeEntity->unsigned);
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
