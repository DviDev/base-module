<?php

namespace Modules\Base\Contracts;

interface BaseModelInterface
{
    /**@return EntityInterface */
    public function modelEntity();

    public function toEntity();

    /**
     * @param null $alias
     * @example return parent::setTable('table_name', $alias);
     */
    public static function table($alias = null): string;
}
