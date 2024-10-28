<?php

namespace Modules\Base\Factories;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Modules\Project\Models\ProjectEntityAttributeModel;

abstract class AttributeFactory
{
    public function __construct(
        public ProjectEntityAttributeModel $attributeEntity,
        public Blueprint                   &$table
    )
    {
    }

    abstract public function handle(): void;

    protected function checksOtherProperties(ProjectEntityAttributeModel $attributeEntity, ColumnDefinition $t): void
    {
        if ($attributeEntity->unsigned) {
            $t->unsigned();
        }
        if ($attributeEntity->unique) {
            $t->unique();
        }
        if ($attributeEntity->index) {
            $t->index($attributeEntity->name);
        }
        $t->default($attributeEntity->default)->nullable($attributeEntity->required == null)->comment($attributeEntity->comments);
    }

    protected function resolveUnsigned(ColumnDefinition $column, $unsigned): void
    {
        if (!$unsigned) {
            return;
        }
        $column->unsigned();
    }
}
