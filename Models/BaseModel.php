<?php

namespace Modules\Base\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Base\Contracts\BaseModelImplementation;
use Modules\Base\Contracts\BaseModelInterface;
use Modules\Base\Entities\BaseEntityModel;

/**
 * @note Try not to create queries outside the repository
 * @author Davi Menezes
 * @copyright Copyright (c) 2020. (davimenezes.dev@gmail.com)
 * @see https://github.com/DaviMenezes
 * @method BaseEntityModel toEntity()
 * @method self|static first()
 * @method self|static setAttribute(string $key, mixed $value)
 * @method static self factory($count = null, $state = [])
 */
abstract class BaseModel extends Model implements BaseModelInterface
{
    public $timestamps = false;

    use BaseModelImplementation;

    public function getGuarded()
    {
        return ['id'];
    }

    public function getTable(): string
    {
        return $this->table();
    }
}
