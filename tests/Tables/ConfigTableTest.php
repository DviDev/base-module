<?php

namespace Modules\Base\Tests\Tables;

use Modules\Base\Contracts\BaseModel;
use Modules\Base\Models\ConfigModel;
use Modules\Base\Services\Tests\BaseTest;

class ConfigTableTest extends BaseTest
{
    public function getModelClass(): string|BaseModel
    {
        return ConfigModel::class;
    }
}
