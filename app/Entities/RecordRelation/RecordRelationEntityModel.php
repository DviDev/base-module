<?php

namespace Modules\Base\Entities\RecordRelation;

use Modules\Base\Entities\BaseEntityModel;
use Modules\Base\Models\RecordRelationModel;

/**
 * @author Davi Menezes (davimenezes.dev@gmail.com)
 * @link https://github.com/DaviMenezes
 * @property-read RecordRelationModel $model
 * @method self save()
 * @method static self new()
 * @method static self props($alias = null, $force = null)
 */
class RecordRelationEntityModel extends BaseEntityModel
{
    use RecordRelationProps;
}
