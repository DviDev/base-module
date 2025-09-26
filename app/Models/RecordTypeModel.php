<?php

declare(strict_types=1);

namespace Modules\Base\Models;

use Modules\Base\Contracts\BaseModel;
use Modules\Base\Entities\RecordType\RecordTypeEntityModel;
use Modules\Base\Entities\RecordType\RecordTypeProps;
use Modules\Base\Contracts\BaseFactory;

/**
 * @author Davi Menezes (davimenezes.dev@gmail.com)
 *
 * @link https://github.com/DaviMenezes
 *
 * @property-read RecordTypeModel $model
 *
 * @method RecordTypeEntityModel toEntity()
 */
final class RecordTypeModel extends BaseModel
{
    use RecordTypeProps;

    public static function table($alias = null): string
    {
        return self::dbTable('base_record_types', $alias);
    }

    public function modelEntity(): string
    {
        return RecordTypeEntityModel::class;
    }

    protected static function newFactory(): BaseFactory
    {
        return new class extends BaseFactory
        {
            protected $model = RecordTypeModel::class;
        };
    }
}
