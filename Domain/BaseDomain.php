<?php

namespace Modules\Base\Domain;

use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Base\Repository\BaseRepository;
use Modules\Base\Services\Response\BaseResponse;
use ReflectionObject;

/**
 * @author     Davi Menezes
 * @copyright  Copyright (c) 2020. (davimenezes.dev@gmail.com)
 * @see https://github.com/DaviMenezes
 */
abstract class BaseDomain
{
    public BaseRepository $repository;
    protected BaseResponse $baseResponse;

    public function __construct()
    {
        $this->baseResponse = new BaseResponse();
    }

    public function baseResponse(): BaseResponse
    {
        return $this->baseResponse;
    }

    public function repository(): BaseRepository
    {
        $class = $this->repositoryClass();
        return $this->repository = $this->repository ?? new $class();
    }

    abstract public function repositoryClass(): string;

    public function __call($name, $arguments)
    {
        if ((new ReflectionObject($this))->hasMethod($name)) {
            $this->repository()->$name($arguments);
        }
    }
}
