<?php

namespace Modules\Base\Services\Response;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Http\JsonResponse;
use Modules\Base\Services\Errors\Error;

class BaseResponse implements \JsonSerializable, Jsonable
{
    protected array $errors = [];

    protected string $message;

    protected string $type;

    protected array $data = [];

    public function addError(int|string $code, string $msg = null): BaseResponse
    {
        $this->errors[] = new Error($code, $msg);

        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasError(): bool
    {
        return count($this->errors);
    }

    public function setType(string $type): BaseResponse
    {
        $this->type = $type;

        return $this;
    }

    public function addData(string $key, mixed $value): BaseResponse
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setMsg(string $msg): static
    {
        $this->message = $msg;

        return $this;
    }

    public function toArray(): array
    {
        $this->data['message'] = $this->message ?? (count($this->errors) == 0 ? 'ok' : null);
        if (count($this->errors) > 0) {
            $this->data['errors'] = collect($this->errors)->toArray();
            $this->data['errors']['type'] = $this->type;
            $this->data['errors'] = collect($this->data['errors'])->reject(fn (mixed $value) => empty($value))->all();
        }

        return $this->data;
    }

    /**
     * @return JsonResponse
     */
    public function httpResponse(): JsonResponse
    {
        $code = $this->hasError() ? 400 : 200;

        return response()->json($this->toArray(), $code);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function toJson($options = 0): false|string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
