<?php

declare(strict_types=1);

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

final class BlueprintDecimalFactory extends AttributeFactory
{
    public function handle(): void
    {
        $size = str($this->attributeEntity->size)->explode(',');
        $t = $this->table->decimal($this->attributeEntity->name, $size[0], $size[1]);
        $this->resolveUnsigned($t, $this->attributeEntity->unsigned);

        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
