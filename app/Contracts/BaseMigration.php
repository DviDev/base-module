<?php

declare(strict_types=1);

namespace Modules\Base\Contracts;

use Closure;
use Exception;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Base\Factories\AttributeFactory;
use Modules\Base\Factories\Attributes\BlueprintBigIntegerFactory;
use Modules\Base\Factories\Attributes\BlueprintBooleanFactory;
use Modules\Base\Factories\Attributes\BlueprintCharFactory;
use Modules\Base\Factories\Attributes\BlueprintDateFactory;
use Modules\Base\Factories\Attributes\BlueprintDateTimeFactory;
use Modules\Base\Factories\Attributes\BlueprintDecimalFactory;
use Modules\Base\Factories\Attributes\BlueprintDoubleFactory;
use Modules\Base\Factories\Attributes\BlueprintFloatFactory;
use Modules\Base\Factories\Attributes\BlueprintIntegerFactory;
use Modules\Base\Factories\Attributes\BlueprintLongTextFactory;
use Modules\Base\Factories\Attributes\BlueprintMediumIntegerFactory;
use Modules\Base\Factories\Attributes\BlueprintMediumTextFactory;
use Modules\Base\Factories\Attributes\BlueprintSmallIntegerFactory;
use Modules\Base\Factories\Attributes\BlueprintStringFactory;
use Modules\Base\Factories\Attributes\BlueprintTextFactory;
use Modules\Base\Factories\Attributes\BlueprintTimeFactory;
use Modules\Base\Factories\Attributes\BlueprintTimestampFactory;
use Modules\Base\Factories\Attributes\BlueprintTinyIntegerFactory;
use Modules\Base\Factories\Attributes\BlueprintYearFactory;
use Modules\Project\Enums\ModuleEntityAttributeTypeEnum as AttributeTypeEnum;
use Modules\Project\Models\ProjectModuleEntityDBModel;

abstract class BaseMigration extends Migration
{
    protected function baseUp(ProjectModuleEntityDBModel $entity, ?Closure $fn = null): void
    {
        Schema::create($entity->name, function (Blueprint $table) use ($entity): void {
            $map = [
                AttributeTypeEnum::char->name => BlueprintCharFactory::class,
                AttributeTypeEnum::date->name => BlueprintDateFactory::class,
                AttributeTypeEnum::datetime->name => BlueprintDateTimeFactory::class,
                AttributeTypeEnum::decimal->name => BlueprintDecimalFactory::class,
                AttributeTypeEnum::double->name => BlueprintDoubleFactory::class,
                AttributeTypeEnum::float->name => BlueprintFloatFactory::class,
                AttributeTypeEnum::int->name => BlueprintIntegerFactory::class,
                AttributeTypeEnum::mediumtext->name => BlueprintMediumTextFactory::class,
                AttributeTypeEnum::smallint->name => BlueprintSmallIntegerFactory::class,
                AttributeTypeEnum::text->name => BlueprintTextFactory::class,
                AttributeTypeEnum::longtext->name => BlueprintLongTextFactory::class,
                AttributeTypeEnum::time->name => BlueprintTimeFactory::class,
                AttributeTypeEnum::timestamp->name => BlueprintTimestampFactory::class,
                AttributeTypeEnum::tinyint->name => BlueprintTinyIntegerFactory::class,
                AttributeTypeEnum::varchar->name => BlueprintStringFactory::class,
                AttributeTypeEnum::year->name => BlueprintYearFactory::class,
                AttributeTypeEnum::bigint->name => BlueprintBigIntegerFactory::class,
                AttributeTypeEnum::mediumint->name => BlueprintMediumIntegerFactory::class,
                AttributeTypeEnum::boolean->name => BlueprintBooleanFactory::class,
            ];

            $attributes = $entity->entityAttributes()->with('relationship.secondModelEntity')->orderBy('id')->get()->all();
            foreach ($attributes as $attribute) {
                if (! array_key_exists($attribute->typeEnum()->name, $map)) {
                    throw new Exception('🤖 Missing '.AttributeTypeEnum::from($attribute->type_id)->name.' class Factory');
                }
                $class = $map[$attribute->typeEnum()->name];
                if (is_subclass_of($class, AttributeFactory::class)) {
                    (new $class($attribute, $table))->handle();
                }
            }
            $this->createsUniqueCompositeKey($entity, $table);
        });
        if ($fn) {
            $fn();
        }
    }

    protected function createsUniqueCompositeKey(ProjectModuleEntityDBModel $entity, Blueprint $table): void
    {
        if ($columns = $entity->getAttributeUniques()->get()->pluck('name')->all()) {
            $table->unique(columns: $columns, name: collect($columns)->prepend('uniques_')->join('_'));
        }
    }
}
