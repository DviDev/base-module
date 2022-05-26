<?php

namespace Modules\Base\Contracts;

interface EntityModelInterface
{
    /**
     * @param null $alias
     * @example return parent::setTable('table_name', $alias);
     */
    public static function dbTable($alias = null);
}
