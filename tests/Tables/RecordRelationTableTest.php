<?php

namespace Modules\Base\Tests\Tables;

use Modules\Base\Contracts\BaseModel;
use Modules\Base\Entities\BaseEntityModel;
use Modules\Base\Models\RecordRelationModel;
use Modules\Base\Services\Tests\BaseTest;

class RecordRelationTableTest extends BaseTest
{
    public function getModelClass(): string|BaseModel
    {
        return RecordRelationModel::class;
    }
}
