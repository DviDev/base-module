<?php

namespace Modules\Base\Tests\Tables;

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
