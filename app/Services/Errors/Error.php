<?php

namespace Modules\Base\Services\Errors;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class Error implements Arrayable, JsonSerializable
{
    public int $code;

    public string $msg;

    public function __construct($code, $msg)
    {
        $this->code = $code;
        $this->msg = $msg ?? ErrorMessages::getMessageDefault($code);
    }

    public function toJson($options = 0): false|string
    {
        return json_encode($this->toArray(), $options);
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'msg' => $this->msg,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
