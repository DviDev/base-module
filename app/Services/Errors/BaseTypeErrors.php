<?php

declare(strict_types=1);

namespace Modules\Base\Services\Errors;

abstract class BaseTypeErrors
{
    public const HTTP_SERVICE_WITHOUT_ENDPOINT = 1000;

    public const HTTP_SERVICE_DID_NOT_WORK = 1001;

    public const ERROR_IN_RECORD_INFORMATION = 1002;

    public const NOT_ALLOWED = 1003;

    public const ITEM_NOT_FOUND = 1004;

    public const UNINFORMED_ENTITY = 1005;

    public const ENTITY_NOT_IMPLEMENTED = 1006;

    public const ENTITY_TYPE_ERROR = 1007;

    public const TOKEN_CONFIRMATION_PENDENT = 1008;

    public const TOKEN_ALREADY_CONFIRMED = 1009;

    public const TOKEN_CREATE = 1010;

    public const TOKEN_MINUTE_LIMIT_EXCEEDED = 1011;

    public const TOKEN_EXPIRED = 1012;

    public const INVALID_TOKEN = 1013;

    public const INTERNAL_ERROR = 1014;

    public const REPOSITORY_CLASS_UNINFORMED = 1015;

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
            self::INTERNAL_ERROR => 'Erro interno, já estamos averiguando.',
            self::REPOSITORY_CLASS_UNINFORMED => 'Classe de repositório não informada.',
        ];
    }
}
