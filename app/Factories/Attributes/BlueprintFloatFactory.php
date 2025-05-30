<?php

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

class BlueprintFloatFactory extends AttributeFactory
{
    public function handle(): void
    {
        $size = str($this->attributeEntity->size)->explode(',');
        $t = $this->table->float($this->attributeEntity->name, $size[0]);
        $this->resolveUnsigned($t, $this->attributeEntity->unsigned);
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
