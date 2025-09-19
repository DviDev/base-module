<?php

declare(strict_types=1);

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

final class BlueprintBooleanFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->boolean($this->attributeEntity->name)->unsigned();
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
