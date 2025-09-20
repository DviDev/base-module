<?php

declare(strict_types=1);

namespace Modules\Base\Listeners;

use Modules\Project\Contracts\DefineSearchableAttributesContract;

final class DefineSearchableAttributes extends DefineSearchableAttributesContract
{
    protected function searchableFields(): array
    {
        return [];
    }

    protected function moduleName(): string
    {
        return config('base.name');
    }
}
