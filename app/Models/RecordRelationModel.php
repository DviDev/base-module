<?php

namespace Modules\Base\Models;

use Modules\Base\Contracts\BaseModel;
use Modules\Base\Entities\RecordRelation\RecordRelationEntityModel;
use Modules\Base\Entities\RecordRelation\RecordRelationProps;
use Modules\Base\Factories\BaseFactory;

/**
 * @author Davi Menezes (davimenezes.dev@gmail.com)
 *
 * @link https://github.com/DaviMenezes
 *
 * @property-read RecordRelationModel $model
 *
 * @method RecordRelationEntityModel toEntity()
 */
class RecordRelationModel extends BaseModel
{
    use RecordRelationProps;

    public static function table($alias = null): string
    {
        return self::dbTable('base_record_relations', $alias);
    }

    protected static function newFactory(): BaseFactory
    {
        return new class extends BaseFactory {
            protected $model = RecordRelationModel::class;
        };
    }

    public function modelEntity(): string
    {
        return RecordRelationEntityModel::class;
    }
}
