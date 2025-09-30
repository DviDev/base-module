<?php

declare(strict_types=1);

namespace Modules\Base\Tests\Tables;

use Modules\Base\Contracts\BaseModel;
use Modules\Base\Models\ConfigModel;
use Modules\Base\Contracts\BaseTest;

final class ConfigTableTest extends BaseTest
{
    public function getModelClass(): string|BaseModel
    {
        return ConfigModel::class;
    }
}
