<?php

namespace Modules\Base\Domain;

use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Base\Services\Response\BaseResponse;
use ReflectionObject;

/**
 * @author     Davi Menezes
 * @copyright  Copyright (c) 2020. (davimenezes.dev@gmail.com)
 * @see https://github.com/DaviMenezes
 */
abstract class BaseDomain
{
    public $repository;
    protected BaseResponse $baseResponse;

    public function __construct()
    {
        $this->baseResponse = new BaseResponse();
    }

    public function baseResponse()
    {
        return $this->baseResponse;
    }

    /**
     * @param $data
     * @throws Exception
     */
    public static function checkIfSeeding($data): void
    {
        if (config('app.env') === 'production' && $data !== config('app.key')) {
            Log::critical('PERIGO. TENTATIVA DE RESGATAR USUARIO INDEVIDAMENTE');
            throw new Exception('PERIGO. TENTATIVA DE RESGATAR USUARIO INDEVIDAMENTE');
        }
    }

    public function repository()
    {
        $class = $this->repositoryClass();
        return $this->repository = $this->repository ?? new $class();
    }

    abstract public function repositoryClass();

    public function __call($name, $arguments)
    {
        if ((new ReflectionObject($this))->hasMethod($name)) {
            $this->repository()->$name($arguments);
        }
    }
}
