<?php

namespace Modules\Base\Contracts;

interface BaseModelInterface
{
    /**@return EntityInterface */
    public function modelEntity();

    public function toEntity();
}
