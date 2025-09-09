<?php

namespace Modules\Base\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Base\Entities\BaseEntityModel;
use Modules\Base\Traits\BaseModelImplementation;
use Modules\Base\Traits\HasFactory;
use Modules\Base\Traits\HasFirstOrCreateViaFactory;

/**
 * @note Try not to create queries outside the repository
 *
 * @author Davi Menezes
 * @copyright Copyright (c) 2020. (davimenezes.dev@gmail.com)
 *
 * @see https://github.com/DaviMenezes
 *
 * @method BaseEntityModel toEntity()
 * @method self|static first()
 * @method self|static setAttribute(string $key, mixed $value)
 * @method static self factory($count = null, $state = [])
 *
 * @mixin Builder
 */
abstract class BaseModel extends Model implements BaseModelInterface
{
    public $timestamps = false;

    use BaseModelImplementation;
    use HasFactory;
    use HasFirstOrCreateViaFactory;

    public function getGuarded(): array
    {
        return ['id'];
    }

    public function getTable(): string
    {
        return $this->table();
    }
}
