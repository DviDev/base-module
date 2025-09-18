<?php

namespace Modules\Base\Listeners;

use Modules\Project\Contracts\DefineSearchableAttributesContract;

class DefineSearchableAttributes extends DefineSearchableAttributesContract
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
