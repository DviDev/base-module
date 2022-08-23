<?php

namespace Modules\Base\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Base\Contracts\BaseModelImplementation;
use Modules\Base\Contracts\BaseModelInterface;
use Modules\Base\Entities\BaseEntityModel;

/**
 * @note Não use o modelo fora do repositório
 * @author     Davi Menezes
 * @copyright  Copyright (c) 2020. (davimenezes.dev@gmail.com)
 * @see https://github.com/DaviMenezes
 * @method BaseEntityModel toEntity()
 * @method self|static first()
 */
abstract class BaseModel extends Model implements BaseModelInterface
{
    use BaseModelImplementation;

    protected static function booted()
    {
        static::updating(function ($model) {
            if (isset($model->updated_at)) {
                $model->updated_at = now();
            }
        });

        parent::booted();
    }
}
