<?php

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

class BlueprintDoubleFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->double($this->attributeEntity->name);
        $this->resolveUnsigned($t, $this->attributeEntity->unsigned);

        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
