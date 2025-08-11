<?php

namespace Modules\Base\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Base\Contracts\BaseModel;
use Modules\Base\Entities\RecordType\RecordTypeEntityModel;
use Modules\Base\Entities\RecordType\RecordTypeProps;
use Modules\Base\Factories\BaseFactory;
use Modules\Base\Traits\HasFactoryFirstOrCreate;

/**
 * @author Davi Menezes (davimenezes.dev@gmail.com)
 *
 * @link https://github.com/DaviMenezes
 *
 * @property-read RecordTypeModel $model
 *
 * @method RecordTypeEntityModel toEntity()
 */
class RecordTypeModel extends BaseModel
{
    use HasFactory;
    use RecordTypeProps;
    use HasFactoryFirstOrCreate;

    public static function table($alias = null): string
    {
        return self::dbTable('base_record_types', $alias);
    }

    protected static function newFactory(): BaseFactory
    {
        return new class extends BaseFactory
        {
            protected $model = RecordTypeModel::class;
        };
    }

    public function modelEntity(): string
    {
        return RecordTypeEntityModel::class;
    }
}
