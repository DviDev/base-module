<?php

declare(strict_types=1);

namespace Modules\Base\Entities\RecordRelation;

use Modules\Base\Entities\BaseEntityModel;
use Modules\Base\Models\RecordRelationModel;

/**
 * @author Davi Menezes (davimenezes.dev@gmail.com)
 *
 * @link https://github.com/DaviMenezes
 *
 * @property-read RecordRelationModel $model
 *
 * @method self save()
 * @method static self new()
 * @method static self props($alias = null, $force = null)
 */
final class RecordRelationEntityModel extends BaseEntityModel
{
    use RecordRelationProps;
}
