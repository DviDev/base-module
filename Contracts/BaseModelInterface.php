<?php

namespace Modules\Base\Contracts;

interface BaseModelInterface
{
    /**@return EntityInterface*/
    function modelEntity();

    function toEntity();
}
