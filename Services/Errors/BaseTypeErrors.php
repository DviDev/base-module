<?php

namespace Modules\Base\Services\Errors;

abstract class BaseTypeErrors
{
    const HTTP_SERVICE_WITHOUT_ENDPOINT = 1000;
    const HTTP_SERVICE_DID_NOT_WORK = 1001;
    const ERROR_IN_RECORD_INFORMATION = 1002;
    const NOT_ALLOWED = 1003;
    const ITEM_NOT_FOUND = 1004;
    const UNINFORMED_ENTITY = 1005;
    const ENTITY_NOT_IMPLEMENTED = 1006;
    const ENTITY_TYPE_ERROR = 1007;
    const TOKEN_CONFIRMATION_PENDENT = 1008;
    const TOKEN_ALREADY_CONFIRMED = 1009;
    const TOKEN_CREATE = 1010;
    const TOKEN_MINUTE_LIMIT_EXCEEDED = 1011;
    const TOKEN_EXPIRED = 1012;
    const INVALID_TOKEN = 1013;
    const INTERNAL_ERROR = 1014;

    public static function errorMessages(): array
    {
        return [
            self::HTTP_SERVICE_WITHOUT_ENDPOINT => 'O endpoint não foi informado',
            self::HTTP_SERVICE_DID_NOT_WORK => 'Falha na comunicação com o prestador de serviço',
            self::ERROR_IN_RECORD_INFORMATION => 'Não foi possível salvar a informação. Tente mais tarde',
            self::NOT_ALLOWED => 'Sem permissão',
            self::ITEM_NOT_FOUND => 'Item não encontrado',
            self::UNINFORMED_ENTITY => 'A entidade modelo não foi informada',
            self::ENTITY_NOT_IMPLEMENTED => 'A entidade modelo não foi implementada',
            self::ENTITY_TYPE_ERROR => 'A entidade deve corresponder ao repositório',
            self::TOKEN_CONFIRMATION_PENDENT => 'Confirmação pendente',
            self::TOKEN_ALREADY_CONFIRMED => 'Token já confirmado',
            self::TOKEN_CREATE => 'Não foi possível gerar o token',
            self::TOKEN_MINUTE_LIMIT_EXCEEDED => 'A quantidade de token emitido nos últimos minutos foram excedidos',
            self::TOKEN_EXPIRED => 'Token expirado',
            self::INVALID_TOKEN => 'Token inválido',
            self::INTERNAL_ERROR => 'Erro interno, já estamos averiguando.'
        ];
    }
}
