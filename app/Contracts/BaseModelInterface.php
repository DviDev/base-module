<?php

namespace Modules\Base\Contracts;

use Modules\Base\Entities\BaseEntityModel;

interface BaseModelInterface
{
    public function modelEntity(): EntityInterface|string|BaseEntityModel;

    public function toEntity(): BaseEntityModel;

    /**
     * @param  null  $alias
     *
     * @example return parent::setTable('table_name', $alias);
     */
    public static function table($alias = null): string;
}
