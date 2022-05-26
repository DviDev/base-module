<?php

namespace Modules\Base\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Modules\App\Domain\LogErrorDomain;
use Modules\Base\Services\Errors\BaseTypeErrors;
use Modules\Base\Services\Errors\ExceptionBaseResponse;
use Modules\Base\Services\Response\BaseResponse;
use Modules\Contact\Models\UserModel;
use Throwable;

trait ResponseController
{
    public function run($actionName, callable $callable)
    {
        $response = [];
        try {
            try {
                $result = $callable();
                if (!is_a($result, BaseResponse::class)) {
                    return $result;
                }
                /**@var BaseResponse $result */
                if ($result->hasError()) {
                    throw new ExceptionBaseResponse($result);
                }
                return $result->toArray();
            } catch (Throwable $throwable) {
                if (is_a($throwable, ExceptionBaseResponse::class)) {
                    throw $throwable;
                }
                $baseResponse = new BaseResponse();
                $baseResponse->addError(BaseTypeErrors::INTERNAL_ERROR, $throwable->getMessage());
                throw new ExceptionBaseResponse($baseResponse, $throwable);
            }
        } catch (ExceptionBaseResponse $exceptionBase) {
            $exception = $exceptionBase->base() ?? $exceptionBase;
            $msg = 'Ocorreu um erro ao realizar a operação (' . $actionName . ')';
            $response['description'] = $msg;
            $response['message'] = 'Erro ao ' . $actionName;
            if ($exceptionBase->response()->hasError()) {
                $response['errors'] = $exceptionBase->response()->getErrors();
            }
            $data = $exceptionBase->response()->getData();
            if (count($data) > 0) {
                $response['data'] = $data;
            }

            $response_code = $exceptionBase->response()->hasError() ? 400 : 200;
            /**@var UserModel $user */
            $user = auth()->user();
            $is_admin = $user && collect($user->groups())->contains(fn($i) => $i->name === 'adm');
            if (config('app.env') === 'production' && !$is_admin && !config('app.debug')) {
                (new LogErrorDomain())->insert($exceptionBase, $actionName);

                return response()->json($response, $response_code);
            }

            if (!config('app.debug')) {
                return response()->json($response, $response_code);
            }

            $response['dev'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'route' => Route::getCurrentRoute()->uri,
                'request_data' => request()->all(),
                'data' => $exceptionBase->response()->toArray(),
                'error' => [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode()
                ],
                'trace' => $exception->getTrace()
            ];
            if ($exceptionBase->response()->hasError()) {
                $response['errors'] = $exceptionBase->response()->getErrors();
            }
            return response()->json($response, $response_code);
        }
    }
}
