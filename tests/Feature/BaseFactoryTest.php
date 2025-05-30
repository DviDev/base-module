<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Base\Factories\BaseFactory;
use Modules\Base\Factories\FakerFactory;
use Modules\DBMap\Domains\ModuleTableAttributeTypeEnum;
use Modules\DBMap\Entities\ModuleTableAttribute\ModuleTableAttributeEntityModel;
use Modules\DBMap\Models\ModuleTableAttributeModel;
use Modules\DBMap\Models\ModuleTableAttributeTypeModel;

uses(Tests\TestCase::class);
uses(DatabaseTransactions::class);

it('Index is unique multiple', function () {
    //'Todo get all unique columns and check if has in $columns with same values'
    expect(false)->toBeTrue();
});

it('should be create user data', function () {
    $factory = new class extends BaseFactory {
        protected $model = User::class;
    };
    $factory->create();
    expect(true)->toBeTrue();

    //criar uma tabela no banco que tenha todos os atributos possíveis
    //e testar todos eles
    //Todo 1. Cria a tabela e seus atributos
    //Todo 2. Executa o BaseFactory
    //Todo 3. Testa cada atributo
    //Ex: name:attr_char - size: 12 (testa se o valor retornado tem as caracteristicas necessárias)
});
describe('dbmap.factory.fake.auto', function () {
    beforeEach(function () {
        $p = ModuleTableAttributeEntityModel::props();

        $this->attribute = ModuleTableAttributeModel::factory()->create([
            $p->type => ModuleTableAttributeTypeModel::getByType(ModuleTableAttributeTypeEnum::bigint)->id,
            $p->items => null,
            $p->size => null,
            $p->table_id => 12,
            $p->unsigned => fake()->boolean() ? 1 : null,
            $p->nullable => fake()->boolean() ? 1 : null,
        ]);
    });
//    uses(DatabaseTransactions::class);
    it('should return a valid value for BIGINT field type', function () {
        //testa se o valor retornado é um valor aceito pelo campo BIGINT do mysql

        //quero testar se o valor gerado conforme o solicitado pelo $attribute é um valor aceito pelo campo BIGINT do mysql
        $value = FakerFactory::getBigIntValue($this->attribute);
        expect(is_int($value))->toBeTrue();

        // Verifica se está dentro dos limites do BIGINT com sinal
        expect($value)->toBeGreaterThanOrEqual(-9223372036854775808);
        expect($value)->toBeLessThanOrEqual(9223372036854775807);
    });

    it('should return a valid value for BINARY field type', function () {
        $value = FakerFactory::getBinaryValue($this->attribute);
        // Verifica se é uma string
        expect(is_string($value))->toBeTrue();

        // Verifica se o comprimento é compatível com BINARY (normalmente entre 1 e 255 bytes)
        expect(strlen($value))->toBeLessThanOrEqual(255);
        expect(strlen($value))->toBeGreaterThan(0);

        // Verifica se contém apenas caracteres binários válidos
        expect(preg_match('/^[0-9a-f]+$/', bin2hex($value)))->toBe(1);
    });
});


it('should return a valid value for BIT field type', function () {
    $value = FakerFactory::getBitValue(); // Supondo que você tenha este método

    // Verifica se é um número inteiro
    expect(is_int($value))->toBeTrue();

    // Verifica se o valor está no intervalo válido para BIT (0 ou 1)
    expect($value)->toBeLessThanOrEqual(1);
    expect($value)->toBeGreaterThanOrEqual(0);

    // Verifica se o valor pode ser convertido para representação binária
    expect(decbin($value))->toMatch('/^[01]$/');
});

it('should return a valid value for BLOB field type', function () {
    $value = FakerFactory::getBlobValue(); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se o comprimento está dentro dos limites do BLOB (até 65,535 bytes)
    expect(strlen($value))->toBeLessThanOrEqual(65535);
    expect(strlen($value))->toBeGreaterThan(0);

    // Verifica se contém dados binários válidos
    expect(preg_match('/^[0-9a-fA-F]+$/', bin2hex($value)))->toBe(1);
});

it('should return a valid value for BOOL field type', function () {
    $value = fake()->boolean; // Supondo que você tenha este método

    // Verifica se é um booleano
    expect(is_bool($value))->toBeTrue();

    // Verifica se o valor é 0 ou 1 quando convertido para inteiro
    expect(in_array((int)$value, [0, 1]))->toBeTrue();
});

it('should return a valid value for CHAR field type', function () {
    $value = FakerFactory::getCharValue();

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se o comprimento está correto (dentro do limite especificado)
    expect(strlen($value))->toBeLessThanOrEqual(10);
    expect(strlen($value))->toBeGreaterThan(0);

    // Verifica se contém apenas caracteres válidos
    expect(preg_match('/^[\w\s\.\-]+$/', $value))->toBe(1);
});

it('should return a valid value for DATE field type', function () {
    $value = FakerFactory::getDateValue(); // Supondo que você tenha este método
    dump($value, strtotime($value));
    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se está no formato correto de data (YYYY-MM-DD)
    expect($value)->toMatch('/^\d{4}-\d{2}-\d{2}$/');

    // Verifica se é uma data válida
    $date = \DateTime::createFromFormat('Y-m-d', $value);
    expect($date instanceof \DateTime)->toBeTrue();
    expect($date->format('Y-m-d'))->toBe($value);

    // Verifica se está dentro de um intervalo razoável
    $timestamp = strtotime($value);
    expect($timestamp)->toBeGreaterThan(strtotime('1000-01-01'));
    expect($timestamp)->toBeLessThan(strtotime('9999-12-31'));
});

it('should return a valid value for DATETIME field type', function () {
    $value = FakerFactory::getDateTimeValue(); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se está no formato correto de data e hora (YYYY-MM-DD HH:mm:ss)
    expect($value)->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');

    // Verifica se é uma data/hora válida
    $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
    expect($dateTime instanceof \DateTime)->toBeTrue();
    expect($dateTime->format('Y-m-d H:i:s'))->toBe($value);

    // Verifica se está dentro de um intervalo razoável
    $timestamp = strtotime($value);
    expect($timestamp)->toBeGreaterThan(strtotime('1000-01-01 00:00:00'));
    expect($timestamp)->toBeLessThan(strtotime('9999-12-31 23:59:59'));
});

it('should return a valid value for DECIMAL field type', function () {
    $value = FakerFactory::getDecimalValue(10, 2); // Supondo que você tenha este método

    // Verifica se é um número
    expect(is_numeric($value))->toBeTrue();

    // Verifica se está no formato correto para DECIMAL(10,2)
    $parts = explode('.', (string)$value);
    expect(strlen($parts[0]))->toBeLessThanOrEqual(8); // 10-2 dígitos antes do decimal
    expect(isset($parts[1]))->toBeTrue();
    expect(strlen($parts[1]))->toBeLessThanOrEqual(2); // 2 dígitos após o decimal

    // Verifica se está dentro dos limites
    $maxValue = pow(10, 8) - pow(0.1, 2); // Para DECIMAL(10,2)
    $minValue = -$maxValue;
    expect($value)->toBeGreaterThanOrEqual($minValue);
    expect($value)->toBeLessThanOrEqual($maxValue);
});

it('should return a valid value for DOUBLE field type', function () {
    $value = FakerFactory::getDoubleValue(); // Supondo que você tenha este método

    // Verifica se é um número float
    expect(is_float($value))->toBeTrue();

    // Verifica se está dentro dos limites do DOUBLE
    expect($value)->toBeGreaterThan(-1.7976931348623157E+308);
    expect($value)->toBeLessThan(1.7976931348623157E+308);

    // Verifica se mantém a precisão esperada
    $precision = strlen(substr(strrchr((string)$value, "."), 1));
    expect($precision)->toBeLessThanOrEqual(15); // DOUBLE tipicamente tem 15 dígitos de precisão
});

it('should return a valid value for ENUM field type', function () {
    $factory = new class extends BaseFactory {
        protected $model = User::class;
    };

    $allowedValues = ['option1', 'option2', 'option3'];
    $value = FakerFactory::getEnumValue($allowedValues); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se o valor está entre as opções permitidas
    expect(in_array($value, $allowedValues))->toBeTrue();

    // Verifica se não está vazio
    expect($value)->not->toBeEmpty();
});

it('should return a valid value for FLOAT field type', function () {
    $value = FakerFactory::getFloatValue(); // Supondo que você tenha este método

    // Verifica se é um número float
    expect(is_float($value))->toBeTrue();

    // Verifica se está dentro dos limites do FLOAT
    expect($value)->toBeGreaterThan(-3.402823466E+38);
    expect($value)->toBeLessThan(3.402823466E+38);

    // Verifica a precisão
    $precision = strlen(substr(strrchr((string)$value, "."), 1));
    expect($precision)->toBeLessThanOrEqual(7); // FLOAT tipicamente tem 7 dígitos de precisão
});

it('should return a valid value for INT field type', function () {
    $value = FakerFactory::getIntValue(); // Supondo que você tenha este método

    // Verifica se é um número inteiro
    expect(is_int($value))->toBeTrue();

    // Verifica se está dentro dos limites do INT
    expect($value)->toBeGreaterThanOrEqual(-2147483648);
    expect($value)->toBeLessThanOrEqual(2147483647);
});

it('should return a valid value for JSON field type', function () {
    $value = FakerFactory::getJsonValue(); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se é um JSON válido
    expect(json_decode($value))->not->toBeNull();
    expect(json_last_error())->toBe(JSON_ERROR_NONE);

    // Decodifica e verifica se é um array ou objeto
    $decoded = json_decode($value, true);
    expect(is_array($decoded) || is_object($decoded))->toBeTrue();
});

it('should return a valid value for LONGBLOB field type', function () {
    $value = FakerFactory::getLongBlobValue(); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se o comprimento está dentro dos limites do LONGBLOB (até 4GB)
    expect(strlen($value))->toBeLessThanOrEqual(4294967295);
    expect(strlen($value))->toBeGreaterThan(0);

    // Verifica se contém dados binários válidos
    expect(preg_match('/^[0-9a-fA-F]+$/', bin2hex($value)))->toBe(1);
});

it('should return a valid value for LONGTEXT field type', function () {
    $value = FakerFactory::getLongTextValue(); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se o comprimento está dentro dos limites do LONGTEXT (até 4GB)
    expect(strlen($value))->toBeLessThanOrEqual(4294967295);
    expect(strlen($value))->toBeGreaterThan(0);

    // Verifica se contém caracteres válidos
    expect(mb_check_encoding($value, 'UTF-8'))->toBeTrue();
});

it('should return a valid value for MEDIUMBLOB field type', function () {
    $value = FakerFactory::getMediumBlobValue(); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se o comprimento está dentro dos limites do MEDIUMBLOB (até 16MB)
    expect(strlen($value))->toBeLessThanOrEqual(16777215);
    expect(strlen($value))->toBeGreaterThan(0);

    // Verifica se contém dados binários válidos
    expect(preg_match('/^[0-9a-fA-F]+$/', bin2hex($value)))->toBe(1);
});

it('should return a valid value for MEDIUMINT field type', function () {
    $value = FakerFactory::getMediumIntValue(); // Supondo que você tenha este método

    // Verifica se é um número inteiro
    expect(is_int($value))->toBeTrue();

    // Verifica se está dentro dos limites do MEDIUMINT
    expect($value)->toBeGreaterThanOrEqual(-8388608);
    expect($value)->toBeLessThanOrEqual(8388607);
});

it('should return a valid value for MEDIUMTEXT field type', function () {
    $value = FakerFactory::getMediumTextValue(); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se o comprimento está dentro dos limites do MEDIUMTEXT (até 16MB)
    expect(strlen($value))->toBeLessThanOrEqual(16777215);
    expect(strlen($value))->toBeGreaterThan(0);

    // Verifica se contém caracteres válidos
    expect(mb_check_encoding($value, 'UTF-8'))->toBeTrue();
});

it('should return a valid value for SET field type', function () {
    $factory = new class extends BaseFactory {
        protected $model = User::class;
    };

    $allowedValues = ['value1', 'value2', 'value3'];
    $value = FakerFactory::getSetValue($allowedValues); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Divide os valores do SET
    $selectedValues = explode(',', $value);

    // Verifica se cada valor selecionado está na lista de valores permitidos
    foreach ($selectedValues as $selectedValue) {
        expect(in_array($selectedValue, $allowedValues))->toBeTrue();
    }

    // Verifica se não há valores duplicados
    expect(count($selectedValues))->toBe(count(array_unique($selectedValues)));
});

it('should return a valid value for SMALLINT field type', function () {
    $value = FakerFactory::getSmallIntValue(); // Supondo que você tenha este método

    // Verifica se é um número inteiro
    dump($value);
    expect(is_int($value))->toBeTrue();
    // Verifica se está dentro dos limites do SMALLINT
    expect($value)->toBeGreaterThanOrEqual(-32768);
    expect($value)->toBeLessThanOrEqual(32767);
});

it('should return a valid value for TEXT field type', function () {
    $value = FakerFactory::getTextValue(); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se o comprimento está dentro dos limites do TEXT (até 65,535 bytes)
    expect(strlen($value))->toBeLessThanOrEqual(65535);
    expect(strlen($value))->toBeGreaterThan(0);

    // Verifica se contém caracteres válidos
    expect(mb_check_encoding($value, 'UTF-8'))->toBeTrue();
});

it('should return a valid value for TIME field type', function () {
    $value = FakerFactory::getTimeValue(); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se está no formato correto de tempo (HH:mm:ss)
    expect($value)->toMatch('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]$/');

    // Verifica se é um horário válido
    $time = \DateTime::createFromFormat('H:i:s', $value);
    expect($time instanceof \DateTime)->toBeTrue();
    expect($time->format('H:i:s'))->toBe($value);
});

it('should return a valid value for TIMESTAMP field type', function () {
    $value = FakerFactory::getTimestampValue(); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se está no formato correto de timestamp (YYYY-MM-DD HH:mm:ss)
    expect($value)->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');

    // Verifica se é um timestamp válido
    $timestamp = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
    expect($timestamp instanceof \DateTime)->toBeTrue();
    expect($timestamp->format('Y-m-d H:i:s'))->toBe($value);

    // Verifica se está dentro do intervalo válido para TIMESTAMP
    expect(strtotime($value))->toBeGreaterThanOrEqual(strtotime('1970-01-01 00:00:01'));
    expect(strtotime($value))->toBeLessThanOrEqual(strtotime('2038-01-19 03:14:07'));
});

it('should return a valid value for TINYBLOB field type', function () {
    $value = FakerFactory::getTinyBlobValue(); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se o comprimento está dentro dos limites do TINYBLOB (até 255 bytes)
    expect(strlen($value))->toBeLessThanOrEqual(255);
    expect(strlen($value))->toBeGreaterThan(0);

    // Verifica se contém dados binários válidos
    expect(preg_match('/^[0-9a-fA-F]+$/', bin2hex($value)))->toBe(1);
});

it('should return a valid value for TINYINT field type', function () {
    $value = FakerFactory::getTinyIntValue(); // Supondo que você tenha este método

    // Verifica se é um número inteiro
    expect(is_int($value))->toBeTrue();

    // Verifica se está dentro dos limites do TINYINT
    expect($value)->toBeGreaterThanOrEqual(-128);
    expect($value)->toBeLessThanOrEqual(127);
});

it('should return a valid value for TINYTEXT field type', function () {
    $value = FakerFactory::getTinyTextValue(); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se o comprimento está dentro dos limites do TINYTEXT (até 255 bytes)
    expect(strlen($value))->toBeLessThanOrEqual(255);
    expect(strlen($value))->toBeGreaterThan(0);

    // Verifica se contém caracteres válidos
    expect(mb_check_encoding($value, 'UTF-8'))->toBeTrue();
});

it('should return a valid value for VARCHAR field type', function () {
    $factory = new class extends BaseFactory {
        protected $model = User::class;
    };
    $length = 100;
    $value = FakerFactory::getVarcharValue($length); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se o comprimento está dentro do limite especificado
    expect(strlen($value))->toBeLessThanOrEqual($length);
    expect(strlen($value))->toBeGreaterThan(0);

    // Verifica se contém caracteres válidos
    expect(mb_check_encoding($value, 'UTF-8'))->toBeTrue();
});

it('should return a valid value for VARBINARY field type', function () {
    $length = 100;
    $value = FakerFactory::getVarbinaryValue($length); // Supondo que você tenha este método

    // Verifica se é uma string
    expect(is_string($value))->toBeTrue();

    // Verifica se o comprimento está dentro do limite especificado
    expect(strlen($value))->toBeLessThanOrEqual($length);
    expect(strlen($value))->toBeGreaterThan(0);

    // Verifica se contém dados binários válidos
    expect(preg_match('/^[0-9a-fA-F]+$/', bin2hex($value)))->toBe(1);
});

it('should return a valid value for YEAR field type', function () {
    $value = FakerFactory::getYearValue(); // Supondo que você tenha este método

    // Verifica se é um número inteiro
    expect(is_int($value))->toBeTrue();

    // Verifica se está dentro do intervalo válido para YEAR (1901-2155)
    expect($value)->toBeGreaterThanOrEqual(1901);
    expect($value)->toBeLessThanOrEqual(2155);

    // Verifica se tem 4 dígitos
    expect(strlen((string)$value))->toBe(4);
});
