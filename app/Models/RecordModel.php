<?php

declare(strict_types=1);

namespace Modules\Base\Models;

use Illuminate\Database\Eloquent\Builder;
use Modules\Base\Contracts\BaseModel;
use Modules\Base\Entities\BaseEntityModel;
use Modules\Base\Entities\Record\RecordEntityModel;
use Modules\Base\Entities\Record\RecordProps;
use Modules\Base\Factories\BaseFactory;

/**
 * @author Davi Menezes (davimenezes.dev@gmail.com)
 *
 * @link https://github.com/DaviMenezes
 *
 * @property-read RecordModel $model
 *
 * @method RecordEntityModel toEntity()
 * @method self factory()
 *
 * @mixin Builder
 */
final class RecordModel extends BaseModel
{
    use RecordProps;

    public static function table($alias = null): string
    {
        return self::dbTable('base_records', $alias);
    }

    public static function createViaFactory(string $type_name): self
    {
        return self::factory()->create([
            'type_id' => RecordTypeModel::firstOrcreate(['name' => $type_name])->id,
        ]);
    }

    public static function createWithType(string $type): self
    {
        return self::create([
            'type_id' => RecordTypeModel::firstOrCreate(['name' => $type])->id,
        ]);
    }

    public function modelEntity(): string|BaseEntityModel
    {
        return RecordEntityModel::class;
    }

    protected static function newFactory(): BaseFactory
    {
        return new class extends BaseFactory
        {
            protected $model = RecordModel::class;
        };
    }
}
