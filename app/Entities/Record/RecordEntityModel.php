<?php

declare(strict_types=1);

namespace Modules\Base\Entities\Record;

use Modules\Base\Entities\BaseEntityModel;
use Modules\Base\Models\RecordModel;

/**
 * @author Davi Menezes (davimenezes.dev@gmail.com)
 *
 * @link https://github.com/DaviMenezes
 *
 * @property-read RecordModel $model
 *
 * @method self save()
 * @method static self new()
 * @method static self props($alias = null, $force = null)
 */
final class RecordEntityModel extends BaseEntityModel
{
    use RecordProps;
}
