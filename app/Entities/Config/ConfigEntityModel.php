<?php

namespace Modules\Base\Entities\Config;

use Modules\Base\Entities\BaseEntityModel;
use Modules\Base\Models\ConfigModel;

/**
 * @author Davi Menezes (davimenezes.dev@gmail.com)
 *
 * @link https://github.com/DaviMenezes
 *
 * @property-read ConfigModel $model
 *
 * @method self save()
 * @method static self new()
 * @method static self props($alias = null, $force = null)
 */
class ConfigEntityModel extends BaseEntityModel
{
    use ConfigProps;
}
