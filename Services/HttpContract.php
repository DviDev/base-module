<?php

namespace Modules\Base\Services;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Base\Entities\BaseEntity;
use Modules\App\Entities\TokenEntity;
use Modules\Base\Services\Errors\BaseTypeErrors;
use Modules\Base\Services\Errors\ExceptionBaseResponse;
use Modules\Base\Services\Response\BaseResponse;
use Modules\Base\Services\Response\ResponseType;

abstract class HttpContract
{
    protected $data;
    /**
     * @var Response
     */
    public $serviceResponse;
    /**
     * @var BaseResponse
     */
    public $baseResponse;

    public function __construct($data = [])
    {
        $this->data = $data;
        $this->baseResponse = new BaseResponse();
    }

    /** @return HttpContract */
    abstract public function run();

    abstract protected function endPoint();

    abstract protected function errorType(): int;

    abstract protected function url();

    abstract protected function loginContract();

    abstract protected function moduleName():string;

    protected function accessToken(): ?string
    {
        if (is_a($this, BaseLoginHttpServiceInterface::class)) {
            return null;
        }

        if (!$this->loginContract()) {
            return null;
        }
        $response = $this->makeLogin();

        if ($response->baseResponse->hasError()) {
            return null;
        }
        /**@var TokenEntity $token */
        $token = cache()->get($this->moduleName() . '.token');
        return $token->token;
    }

    protected function getParams()
    {
        if (is_a($this->data, BaseEntity::class)) {
            return $this->data->toArray();
        }
        return $this->data;
    }

    /**@param array|string|null $query
     * @throws Exception
     */
    protected function get($query = null)
    {
        $this->serviceResponse = $this->http()->get(
            $this->url() . $this->endPoint(),
            $query
        );
        $this->checkIfFailed();
        $this->setData();
        return $this;
    }

    /**
     * @param array $data
     * @return HttpContract
     */
    protected function post(array $data = [])
    {
        $this->serviceResponse = $this->http()->post(
            $this->url() . $this->endPoint(),
            $data
        );
        $this->checkIfFailed();
        $this->setData();
        return $this;
    }

    protected function http()
    {
        $this->validateUrl();
        return Http::withHeaders($this->getHeaders());
    }

    protected function getHeaders()
    {
        $array = [
            'Content-Type' => 'application/json'
        ];
        if ($token = $this->accessToken()) {
            $array['Authorization'] = 'Bearer ' . $token;
        }
        return $array;
    }

    public function body(): string
    {
        return $this->serviceResponse->body() === 'Invalid token'
            ? 'serviço indisponível'
            : $this->serviceResponse->body();
    }

    protected function checkIfFailed()
    {
        if (!$this->serviceResponse->failed()) {
            return;
        }

        $this->baseResponse->addError($this->errorType());
        $this->baseResponse->setType(ResponseType::DANGER);
    }

    protected function setData()
    {
        if ($this->serviceResponse && $this->serviceResponse->failed()) {
            return;
        }
        $this->baseResponse->addData(
            'result',
            $this->serviceResponse->json() ?? $this->serviceResponse->body()
        );
    }

    public function __toString()
     {
         return json_encode($this->baseResponse->toArray(), JSON_UNESCAPED_UNICODE);
     }

    protected function makeLogin(): HttpContract
    {
        $loginContract = $this->loginContract();
        /**@var BaseLoginHttpServiceInterface $loginContract */
        $loginContract = new $loginContract();
        return $loginContract->login();
    }

    protected function validateUrl(): void
    {
        $url = $this->url() . $this->endPoint();
        if (!Str::contains(Str::after($url, 'https://'), '/')) {
            ExceptionBaseResponse::throw(BaseTypeErrors::HTTP_SERVICE_WITHOUT_ENDPOINT);
        }
    }
}
