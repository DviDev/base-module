<?php

namespace Base\tests\Tables;

use Modules\Base\Contracts\BaseModel;
use Modules\Base\Models\RecordModel;
use Modules\Base\Services\Tests\BaseTest;

class RecordTableTest extends BaseTest
{
    public function getModelClass(): string|BaseModel
    {
        return RecordModel::class;
    }
}
