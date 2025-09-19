<?php

declare(strict_types=1);

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

final class BlueprintTinyIntegerFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->tinyInteger($this->attributeEntity->name, $this->attributeEntity->size);
        $this->resolveUnsigned($t, $this->attributeEntity->unsigned);
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
