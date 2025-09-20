<?php

declare(strict_types=1);

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

final class BlueprintSmallIntegerFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->smallInteger($this->attributeEntity->name);
        $this->resolveUnsigned($t, $this->attributeEntity->unsigned);
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
