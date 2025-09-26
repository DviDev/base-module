<?php

declare(strict_types=1);

namespace Modules\Base\Entities\Config;

use Modules\Base\Contracts\BaseEntityModel;
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
final class ConfigEntityModel extends BaseEntityModel
{
    use ConfigProps;
}
