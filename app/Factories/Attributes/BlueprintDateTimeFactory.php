<?php

declare(strict_types=1);

namespace Modules\Base\Factories\Attributes;

use Modules\Base\Factories\AttributeFactory;

final class BlueprintDateTimeFactory extends AttributeFactory
{
    public function handle(): void
    {
        $t = $this->table->dateTime($this->attributeEntity->name);
        $this->checksOtherProperties($this->attributeEntity, $t);
    }
}
