<?php

namespace Base\tests\Tables;

use Modules\Base\Contracts\BaseModel;
use Modules\Base\Models\RecordTypeModel;
use Modules\Base\Services\Tests\BaseTest;

class RecordTypeTableTest extends BaseTest
{
    public function getModelClass(): string|BaseModel
    {
        return RecordTypeModel::class;
    }
}
