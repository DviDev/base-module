<?php

declare(strict_types=1);

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

final class BlueprintMediumIntegerFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->mediumInteger($this->attributeEntity->name);
        $this->resolveUnsigned($t, $this->attributeEntity->name);
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
