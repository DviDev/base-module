<?php

declare(strict_types=1);

namespace Modules\Base\Contracts;

use Modules\Base\Contracts\BaseEntityModel;

interface BaseModelInterface
{
    /**
     * @param  null  $alias
     *
     * @example return parent::setTable('table_name', $alias);
     */
    public static function table($alias = null): string;

    public function modelEntity(): string|BaseEntityModel;

    public function toEntity(): BaseEntityModel;
}
