<?php

declare(strict_types=1);

namespace Modules\Base\Http\Controllers;

use Exception;
use Modules\Base\Domain\BaseDomain;

/**
 * @author     Davi Menezes
 * @copyright  Copyright (c) 2020. (davimenezes.dev@gmail.com)
 *
 * @see https://github.com/DaviMenezes
 */
abstract class BaseController
{
    protected BaseDomain $domain;

    public function domain()
    {
        $class = $this->domainClass();

        return $this->domain = $this->domain ?? new $class;
    }

    /**@return string
     * @throws Exception
     */
    public function domainClass()
    {
        throw new Exception('A classe domínio não foi implementada');
    }
}
