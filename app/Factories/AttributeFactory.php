<?php

namespace Modules\Base\Factories;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Modules\Project\Models\ProjectModuleEntityAttributeModel;

abstract class AttributeFactory
{
    public function __construct(
        public ProjectModuleEntityAttributeModel $attributeEntity,
        public Blueprint &$table
    ) {}

    abstract public function handle(): void;

    protected function checksOtherProperties(ProjectModuleEntityAttributeModel $attributeEntity, ColumnDefinition $t): void
    {
        if ($attributeEntity->unsigned) {
            $t->unsigned();
        }
        if ($attributeEntity->unique) {
            $t->unique();
        }
        if ($attributeEntity->index == 'KEY') {
            $t->index($attributeEntity->name);
        }
        if ($attributeEntity->index == 'FULLTEXT') {
            $t->fulltext($attributeEntity->name);
        }

        $t->default($attributeEntity->default)->nullable($attributeEntity->required == null)->comment($attributeEntity->comments);
    }

    protected function resolveUnsigned(ColumnDefinition $column, $unsigned): void
    {
        if (! $unsigned) {
            return;
        }
        $column->unsigned();
    }
}
