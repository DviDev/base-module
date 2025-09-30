<?php

declare(strict_types=1);

namespace Modules\Base\Factories;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Modules\Schema\Models\ModuleEntityAttributeModel;

abstract class AttributeFactory
{
    public function __construct(
        public ModuleEntityAttributeModel $attributeEntity,
        public Blueprint &$table
    ) {}

    abstract public function handle(): void;

    protected function checksOtherProperties(ModuleEntityAttributeModel $attributeEntity, ColumnDefinition $t): void
    {
        if ($attributeEntity->unsigned) {
            $t->unsigned();
        }
        if ($attributeEntity->unique) {
            $t->unique();
        }
        if ($attributeEntity->index === 'KEY') {
            $t->index($attributeEntity->name);
        }
        if ($attributeEntity->index === 'FULLTEXT') {
            $t->fulltext($attributeEntity->name);
        }

        $t->default($attributeEntity->default)->nullable($attributeEntity->required === null)->comment($attributeEntity->comments);
    }

    protected function resolveUnsigned(ColumnDefinition $column, $unsigned): void
    {
        if (! $unsigned) {
            return;
        }
        $column->unsigned();
    }
}
