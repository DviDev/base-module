<?php

namespace Modules\Base\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Base\Contracts\BaseModelImplementation;
use Modules\Base\Contracts\BaseModelInterface;
use Modules\Base\Entities\BaseEntityModel;
use Modules\App\Models\MessageModel;

/**
 * @note Try not to create queries outside the repository
 * @author Davi Menezes
 * @copyright Copyright (c) 2020. (davimenezes.dev@gmail.com)
 * @see https://github.com/DaviMenezes
 * @method BaseEntityModel toEntity()
 * @method self|static first()
 * @method static self factory($count = null, $state = [])
 */
abstract class BaseModel extends Model implements BaseModelInterface
{
    use BaseModelImplementation;

    public function getGuarded(): array
    {
        $p = $this->modelEntity()::props();
        return collect($p->toArray())->filter(fn($i) => in_array($i, [
            'id', 'created_at', 'updated_at', 'deleted_at'
        ]))->toArray();
    }

    public function getTable(): string
    {
        return $this->table();
    }
}
