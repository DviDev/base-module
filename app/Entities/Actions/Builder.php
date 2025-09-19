<?php

declare(strict_types=1);

namespace Modules\Base\Entities\Actions;

use Modules\Permission\Enums\Actions;

/**
 * @method static string builder(Actions $action)
 * @method static self can()
 */
final class Builder extends GateContract {}
