<?php

namespace Modules\Base\Services\Errors;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class Error implements Arrayable, JsonSerializable
{
    public $code;

    public $msg;

    public function __construct($code, $msg)
    {
        $this->code = $code;
        $this->msg = $msg ?? ErrorMessages::getMessageDefault($code);
    }

    public function toJson($options = 0)
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

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
