<?php

declare(strict_types=1);

namespace Modules\Base\Models;

use Modules\Base\Contracts\BaseModel;
use Modules\Base\Entities\RecordRelation\RecordRelationEntityModel;
use Modules\Base\Entities\RecordRelation\RecordRelationProps;
use Modules\Base\Contracts\BaseFactory;

/**
 * @author Davi Menezes (davimenezes.dev@gmail.com)
 *
 * @link https://github.com/DaviMenezes
 *
 * @property-read RecordRelationModel $model
 *
 * @method RecordRelationEntityModel toEntity()
 */
final class RecordRelationModel extends BaseModel
{
    use RecordRelationProps;

    public static function table($alias = null): string
    {
        return self::dbTable('base_record_relations', $alias);
    }

    public function modelEntity(): string
    {
        return RecordRelationEntityModel::class;
    }

    protected static function newFactory(): BaseFactory
    {
        return new class extends BaseFactory
        {
            protected $model = RecordRelationModel::class;
        };
    }
}
