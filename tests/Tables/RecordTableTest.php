<?php

declare(strict_types=1);

namespace Modules\Base\Tests\Tables;

use Modules\Base\Contracts\BaseModel;
use Modules\Base\Models\RecordModel;
use Modules\Base\Services\Tests\BaseTest;

final class RecordTableTest extends BaseTest
{
    public function getModelClass(): string|BaseModel
    {
        return RecordModel::class;
    }
}
