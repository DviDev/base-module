<?php

namespace Modules\Base\Factories;

use Faker\Factory;

class FakerFactory
{
    public static function getBigIntValue(): int
    {
        $faker = Factory::create();
        return $faker->numberBetween(-9223372036854775808, 9223372036854775807);
    }

    public static function getBinaryValue(): string
    {
        $faker = Factory::create();
        $length = $faker->numberBetween(1, 255);
        return substr(base64_encode(random_bytes($length)), 0, $length);
    }

    public static function getBitValue(): int
    {
        $faker = Factory::create();
        return $faker->boolean ? 1 : 0;
    }

    public static function getBlobValue(): string
    {
        $faker = Factory::create();
        // BLOB can store up to 65,535 bytes (64KB)
        $length = $faker->numberBetween(1, 65535);
        return base64_encode(random_bytes($length));
    }

    public static function getCharValue(int $length = 1): string
    {
        $faker = Factory::create();
        $length = min(max($length, 5), 255); // MySQL CHAR type max length is 255, min 5 for Faker
        return substr($faker->text($length), 0, $length);
    }

    public static function getMysqlDateValue()
    {
        $faker = Factory::create();
        // MySQL DATE range is '1000-01-01' to '9999-12-31'
        return $faker->dateTimeBetween('1000-01-01', '9999-12-31')
            ->format('Y-m-d');
    }

    public static function getDateTimeValue()
    {
        $faker = Factory::create();
        // MySQL DATETIME range is '1000-01-01 00:00:00' to '9999-12-31 23:59:59'
        return $faker->dateTimeBetween('1000-01-01 00:00:00', '9999-12-31 23:59:59')
            ->format('Y-m-d H:i:s');
    }

    public static function getDecimalValue(int $precision = 65, int $scale = 30): string
    {
        $faker = Factory::create();

        // MySQL DECIMAL max precision is 65, max scale is 30
        $precision = min(65, max($precision, $scale));
        $scale = min(30, max(0, $scale));

        // Calculate maximum whole number part based on precision and scale
        $maxWholeDigits = $precision - $scale;
        $maxWhole = pow(10, $maxWholeDigits) - 1;

        // Generate random number with proper precision and scale
        $whole = $faker->numberBetween(0, $maxWhole);
        $decimal = $faker->numberBetween(0, pow(10, $scale) - 1);

        // Format the number to ensure proper scale
        return sprintf("%d.%0{$scale}d", $whole, $decimal);
    }

    public static function getDoubleValue(): float|int
    {
        $faker = Factory::create();
        $isPositive = $faker->boolean;

        if ($faker->boolean) { // Sometimes return 0
            return 0.0;
        }

        $exponent = $faker->numberBetween(-308, 308);
        $mantissa = $faker->randomFloat(15, 1, 9.999999999999999);

        // Ensure the number is within MySQL DOUBLE bounds
        $number = $mantissa * pow(10, $exponent);

        // Handle very small numbers
        if (abs($number) < 2.2250738585072014E-308) {
            $number = $isPositive ? 2.2250738585072014E-308 : -2.2250738585072014E-308;
        }

        // Handle very large numbers
        if (abs($number) > 1.7976931348623157E+308) {
            $number = $isPositive ? 1.7976931348623157E+308 : -1.7976931348623157E+308;
        }

        return $isPositive ? $number : -$number;
    }

    public static function getEnumValue(array $allowedValues): mixed
    {
        $faker = Factory::create();

        if (empty($allowedValues)) {
            return null;
        }

        // MySQL ENUM is limited to 65,535 distinct elements
        $validValues = array_slice($allowedValues, 0, 65535);

        return $faker->randomElement($validValues);
    }

    public static function getFloatValue(): float|int
    {
        $faker = Factory::create();
        $isPositive = $faker->boolean;

        if ($faker->boolean) { // Sometimes return 0
            return 0.0;
        }

        $exponent = $faker->numberBetween(-38, 38);
        $mantissa = $faker->randomFloat(7, 1, 3.4028234);

        // Ensure the number is within MySQL FLOAT bounds
        $number = $mantissa * pow(10, $exponent);

        // Handle very small numbers
        if (abs($number) < 1.175494351E-38) {
            $number = $isPositive ? 1.175494351E-38 : -1.175494351E-38;
        }

        // Handle very large numbers
        if (abs($number) > 3.402823466E+38) {
            $number = $isPositive ? 3.402823466E+38 : -3.402823466E+38;
        }

        return $isPositive ? $number : -$number;
    }

    public static function getIntValue(bool $allowNull = false): ?int
    {
        $faker = Factory::create();

        if ($allowNull && $faker->boolean(10)) { // 10% chance of returning null if allowed
            return null;
        }

        // MySQL INT range is -2147483648 to 2147483647
        return $faker->numberBetween(-2147483648, 2147483647);
    }

    public static function getJsonValue(): false|string
    {
        $faker = Factory::create();

        // Generate random structure (array or object)
        $depth = $faker->numberBetween(1, 3); // Control nesting depth
        $elements = $faker->numberBetween(1, 5); // Number of elements

        $data = [];
        for ($i = 0; $i < $elements; $i++) {
            $key = $faker->word;

            // Randomly choose value type
            switch ($faker->numberBetween(1, 6)) {
                case 1:
                    $data[$key] = $faker->word;
                    break;
                case 2:
                    $data[$key] = $faker->numberBetween(1, 1000);
                    break;
                case 3:
                    $data[$key] = $faker->boolean;
                    break;
                case 4:
                    $data[$key] = $faker->randomElements(['a', 'b', 'c'], $faker->numberBetween(1, 3));
                    break;
                case 5:
                    if ($depth > 1) {
                        $data[$key] = (object)['nested' => $faker->word];
                    } else {
                        $data[$key] = $faker->word;
                    }
                    break;
                case 6:
                    $data[$key] = null;
                    break;
            }
        }

        // MySQL's maximum JSON document size is 1GB, but we keep it reasonable
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public static function getLongBlobValue(): string
    {
        $faker = Factory::create();
        // LONGBLOB can store up to 4GB (4,294,967,295 bytes)
        // For practical purposes, we'll generate a much smaller size
        $length = $faker->numberBetween(1, 1048576); // Max 1MB for practical purposes
        return base64_encode(random_bytes($length));
    }

    public static function getLongTextValue(): string
    {
        $faker = Factory::create();
        // LONGTEXT can store up to 4GB (4,294,967,295 bytes)
        // For practical purposes, we'll generate a much smaller size
        $paragraphs = $faker->numberBetween(1, 50); // Generate between 1-50 paragraphs
        $text = $faker->paragraphs($paragraphs, true);

        // Ensure text is valid UTF-8 and within reasonable size (1MB for practical purposes)
        $text = mb_substr($text, 0, 1048576);

        return $text;
    }

    public static function getMediumBlobValue(): string
    {
        $faker = Factory::create();
        // MEDIUMBLOB can store up to 16MB (16,777,215 bytes)
        // For practical purposes, we'll generate a much smaller size
        $length = $faker->numberBetween(1, 262144); // Max 256KB for practical purposes
        return base64_encode(random_bytes($length));
    }

    public static function getMediumIntValue(): int
    {
        $faker = Factory::create();
        // MEDIUMINT range is -8388608 to 8388607
        return $faker->numberBetween(-8388608, 8388607);
    }

    public static function getMediumTextValue(): string
    {
        $faker = Factory::create();
        // MEDIUMTEXT can store up to 16MB (16,777,215 bytes)
        // For practical purposes, we'll generate a much smaller size
        $paragraphs = $faker->numberBetween(1, 25); // Generate between 1-25 paragraphs
        return mb_substr($faker->paragraphs($paragraphs, true), 0, 262144); // Max 256KB for practical purposes
    }

    public static function getSetValue(array $allowedValues): ?string
    {
        $faker = Factory::create();

        if (empty($allowedValues)) {
            return null;
        }

        // MySQL SET is limited to 64 distinct elements
        $validValues = array_slice($allowedValues, 0, 64);

        // Randomly select between 0 and 3 values from the set
        $numElements = $faker->numberBetween(0, 3);
        $selected = $faker->randomElements($validValues, $numElements);

        return implode(',', $selected);
    }

    public static function getSmallIntValue(): int
    {
        $faker = Factory::create();
        // SMALLINT range is -32768 to 32767
        return $faker->numberBetween(-32768, 32767);
    }

    public static function getTextValue(): string
    {
        $faker = Factory::create();
        // TEXT can store up to 65,535 bytes
        $paragraphs = $faker->numberBetween(1, 10);
        return mb_substr($faker->paragraphs($paragraphs, true), 0, 65535);
    }

    public static function getTimeValue()
    {
        $faker = Factory::create();
        // TIME range is '-838:59:59' to '838:59:59'
        return $faker->time('H:i:s');
    }

    public static function getTimestampValue(): string
    {
        $faker = Factory::create();
        // TIMESTAMP range is '1970-01-01 00:00:01' UTC to '2038-01-19 03:14:07' UTC
        return $faker->dateTimeBetween('1970-01-01 00:00:01', '2038-01-19 03:14:07')->format('Y-m-d H:i:s');
    }

    public static function getTinyBlobValue(): string
    {
        $faker = Factory::create();
        // TINYBLOB can store up to 255 bytes
        $length = $faker->numberBetween(1, 255);
        return base64_encode(random_bytes($length));
    }

    public static function getTinyIntValue()
    {
        $faker = Factory::create();
        // TINYINT range is -128 to 127
        return $faker->numberBetween(-128, 127);
    }

    public static function getTinyTextValue(): string
    {
        $faker = Factory::create();
        // TINYTEXT can store up to 255 bytes
        return mb_substr($faker->text(), 0, 255);
    }

    public static function getVarcharValue(int $length): string
    {
        $faker = Factory::create();
        // VARCHAR max length is 65,535 bytes
        $length = min($length, 65535);
        return mb_substr($faker->text($length), 0, $length);
    }

    public static function getVarbinaryValue(int $length): string
    {
        // VARBINARY max length is 65,535 bytes
        $length = min($length, 65535);
        return substr(base64_encode(random_bytes($length)), 0, $length);
    }

    public static function getYearValue(): int
    {
        $faker = Factory::create();
        // YEAR range is 1901 to 2155
        return $faker->numberBetween(1901, 2155);
    }

    public static function getDateValue()
    {
        $faker = Factory::create();
        // MySQL DATE range is '1000-01-01' to '9999-12-31'
        return $faker->dateTimeBetween('1000-01-01', '9999-12-31')
            ->format('Y-m-d');
    }
}
