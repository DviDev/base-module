<?php

namespace Modules\Base\Services\Errors;

use Exception;
use Modules\Base\Services\Response\BaseResponse;
use Throwable;

/**
 * @method-red getMessage()
 */
class ExceptionBaseResponse extends Exception
{
    private BaseResponse $response;

    private ?Throwable $exception;

    public function __construct(BaseResponse $response, ?Throwable $exception = null)
    {
        $this->response = $response;
        $this->exception = $exception;

        parent::__construct();
    }

    public static function throw($errorCode, $msg = null, $exception = null)
    {
        if (is_a($exception, ExceptionBaseResponse::class)) {
            /** @var ExceptionBaseResponse $exception */
            $exception->response()->addError($errorCode);
            throw $exception;
        }
        throw new self((new BaseResponse)->addError($errorCode, $msg), $exception);
    }

    public static function throwWithBaseResponse(BaseResponse $baseResponse, Exception $exception)
    {
        if (is_a($exception, ExceptionBaseResponse::class)) {
            throw $exception;
        }
        throw new self($baseResponse, $exception);
    }

    public function response(): BaseResponse
    {
        return $this->response;
    }

    public function base(): ?Throwable
    {
        return $this->exception;
    }

    public function message()
    {
        $message = '';
        if ($this->exception) {
            $message = $this->exception->getMessage();
        }
        $response_data = $this->response->toArray();
        $message .= json_encode($response_data, JSON_UNESCAPED_UNICODE);

        return $message;
    }

    public function code()
    {
        if ($this->exception) {
            return $this->exception->getCode();
        }

        return collect($this->response->getErrors())
            ->each(fn ($error) => $error->code)->join(',');
    }

    public function file()
    {
        if ($this->exception) {
            return $this->exception->getFile();
        }

        return null;
    }

    public function line()
    {
        if ($this->exception) {
            return $this->exception->getLine();
        }

        return null;
    }

    public function trace()
    {
        if ($this->exception) {
            return $this->exception->getTrace();
        }

        return null;
    }

    public function traceAsString()
    {
        if ($this->exception) {
            return $this->exception->getTraceAsString();
        }

        return null;
    }

    public function previous()
    {
        if ($this->exception) {
            return $this->exception->getPrevious();
        }

        return null;
    }

    public function __toString()
    {
        $str = '';
        if ($message = $this->message()) {
            $str = 'Error: '.$message;
        }
        if ($file = $this->file()) {
            $str .= ' in '.$file;
        }

        if ($line = $this->line()) {
            $str .= ' line '.$line;
        }

        return $str;
    }
}
