<?php

declare(strict_types=1);

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

final class BlueprintCharFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->char($this->attributeEntity->name, $this->attributeEntity->size);
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
