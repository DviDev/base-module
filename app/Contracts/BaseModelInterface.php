<?php

namespace Modules\Base\Contracts;

use Modules\Base\Entities\BaseEntityModel;

interface BaseModelInterface
{
    /**@return string|BaseEntityModel|EntityInterface */
    public function modelEntity();

    public function toEntity();

    /**
     * @param  null  $alias
     *
     * @example return parent::setTable('table_name', $alias);
     */
    public static function table($alias = null): string;
}
