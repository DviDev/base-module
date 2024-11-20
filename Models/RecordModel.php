<?php

namespace Modules\Base\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Base\Entities\Record\RecordEntityModel;
use Modules\Base\Entities\Record\RecordProps;
use Modules\Base\Factories\BaseFactory;

/**
 * @author Davi Menezes (davimenezes.dev@gmail.com)
 * @link https://github.com/DaviMenezes
 * @property-read RecordModel $model
 * @method RecordEntityModel toEntity()
 * @mixin Builder
 */
class RecordModel extends BaseModel
{
    use HasFactory;
    use RecordProps;

    public static function table($alias = null): string
    {
        return self::dbTable('base_records', $alias);
    }

    protected static function newFactory(): BaseFactory
    {
        return new class extends BaseFactory {
            protected $model = RecordModel::class;
        };
    }

    public function modelEntity(): string
    {
        return RecordEntityModel::class;
    }
}
