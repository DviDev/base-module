<?php

namespace Modules\Base\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Base\Contracts\BaseModelImplementation;
use Modules\Base\Contracts\BaseModelInterface;
use Modules\Base\Entities\BaseEntityModel;
use Modules\Base\Factories\BaseFactory;

/**
 * @note Não use o modelo fora do repositório
 * @author     Davi Menezes
 * @copyright  Copyright (c) 2020. (davimenezes.dev@gmail.com)
 * @see https://github.com/DaviMenezes
 * @method BaseEntityModel toEntity()
 * @method self|static first()
 * @method BaseFactory factory()
 */
abstract class BaseModel extends Model implements BaseModelInterface
{
    use BaseModelImplementation;
}
