<?php

declare(strict_types=1);

namespace Modules\Base\Tests\Tables;

use Modules\Base\Contracts\BaseModel;
use Modules\Base\Contracts\BaseTest;
use Modules\Base\Models\RecordModel;

final class RecordTableTest extends BaseTest
{
    public function getModelClass(): string|BaseModel
    {
        return RecordModel::class;
    }
}
