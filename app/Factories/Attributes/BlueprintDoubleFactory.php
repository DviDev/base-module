<?php

declare(strict_types=1);

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

final class BlueprintDoubleFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->double($this->attributeEntity->name);
        $this->resolveUnsigned($t, $this->attributeEntity->unsigned);

        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
