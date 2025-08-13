<?php

namespace Modules\Base\Entities\Actions;

use Modules\Permission\Enums\Actions;

/**
 * @method static string builder(Actions $action)
 * @method static self can()
 */
class Builder extends GateContract {}
