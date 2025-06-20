<?php

namespace Modules\Base\Entities\RecordType;

use Modules\Base\Entities\BaseEntityModel;
use Modules\Base\Models\RecordTypeModel;

/**
 * @author Davi Menezes (davimenezes.dev@gmail.com)
 *
 * @link https://github.com/DaviMenezes
 *
 * @property-read RecordTypeModel $model
 *
 * @method self save()
 * @method static self new()
 * @method static self props($alias = null, $force = null)
 */
class RecordTypeEntityModel extends BaseEntityModel
{
    use RecordTypeProps;
}
