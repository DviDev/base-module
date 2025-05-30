<?php

namespace Modules\Base\Factories;

use Faker\Factory;
use Modules\DBMap\Models\ModuleTableAttributeModel;

class FakerFactory
{
    public static function getBigIntValue(ModuleTableAttributeModel $attribute): ?int
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $min = $attribute->unsigned ? 0 : -9223372036854775808;
        $max = min($attribute->size ?? 9223372036854775807, 9223372036854775807);

        return $faker->numberBetween($min, $max);
    }

    public static function getBinaryValue(ModuleTableAttributeModel $attribute): ?string
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $length = min($attribute->size ?? 255, 255);
        return substr(base64_encode(random_bytes($length)), 0, $length);
    }

    public static function getBitValue(ModuleTableAttributeModel $attribute): ?int
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        return $faker->boolean ? 1 : 0;
    }

    public static function getBlobValue(ModuleTableAttributeModel $attribute): ?string
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $length = min($attribute->size ?? 65535, 65535);
        return base64_encode(random_bytes($length));
    }

    public static function getCharValue(ModuleTableAttributeModel $attribute, int $length = 1): ?string
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $length = min(max($attribute->size ?? $length, 1), 255);
        return substr($faker->text($length), 0, $length);
    }

    public static function getMysqlDateValue(ModuleTableAttributeModel $attribute)
    {

        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $minDate = $attribute->requirement['min'] ?? '1000-01-01';
        $maxDate = $attribute->requirement['max'] ?? '9999-12-31';

        return $faker->dateTimeBetween($minDate, $maxDate)
            ->format('Y-m-d');
    }

    public static function getDateTimeValue(ModuleTableAttributeModel $attribute)
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $minDateTime = $attribute->requirement['min'] ?? '1000-01-01 00:00:00';
        $maxDateTime = $attribute->requirement['max'] ?? '9999-12-31 23:59:59';

        return $faker->dateTimeBetween($minDateTime, $maxDateTime)
            ->format('Y-m-d H:i:s');
    }

    public static function getDecimalValue(ModuleTableAttributeModel $attribute): ?string
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $precision = min(65, $attribute->size ?? 65);
        $scale = min(30, $attribute->scale ?? 0);

        $maxWholeDigits = $precision - $scale;
        $minWhole = $attribute->unsigned ? 0 : -pow(10, $maxWholeDigits) + 1;
        $maxWhole = $attribute->requirement['max'] ?? pow(10, $maxWholeDigits) - 1;

        $whole = $faker->numberBetween($minWhole, $maxWhole);
        $decimal = $faker->numberBetween(0, pow(10, $scale) - 1);

        return sprintf("%d.%0{$scale}d", $whole, $decimal);
    }

    public static function getDoubleValue(ModuleTableAttributeModel $attribute): float|int|null
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $isPositive = !$attribute->unsigned ? $faker->boolean : true;

        if ($faker->boolean) {
            return 0.0;
        }

        $min = $attribute->requirement['min'] ?? ($attribute->unsigned ? 2.2250738585072014E-308 : -1.7976931348623157E+308);
        $max = $attribute->requirement['max'] ?? 1.7976931348623157E+308;

        $number = $faker->randomFloat(15, $min, $max);
        return $isPositive ? abs($number) : -abs($number);
    }

    public static function getEnumValue(ModuleTableAttributeModel $attribute, array $allowedValues): mixed
    {
        $faker = Factory::create();

        if (empty($allowedValues)) {
            return null;
        }

        // MySQL ENUM is limited to 65,535 distinct elements
        $validValues = array_slice($allowedValues, 0, 65535);

        return $faker->randomElement($validValues);
    }

    public static function getFloatValue(ModuleTableAttributeModel $attribute): float|int|null
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $isPositive = !$attribute->unsigned ? $faker->boolean : true;

        if ($faker->boolean) {
            return 0.0;
        }

        $min = $attribute->requirement['min'] ?? ($attribute->unsigned ? 1.175494351E-38 : -3.402823466E+38);
        $max = $attribute->requirement['max'] ?? 3.402823466E+38;

        $number = $faker->randomFloat(7, $min, $max);
        return $isPositive ? abs($number) : -abs($number);
    }

    public static function getIntValue(ModuleTableAttributeModel $attribute): ?int
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $min = $attribute->unsigned ? 0 : -2147483648;
        $max = 2147483647;

        return $faker->numberBetween($min, $max);
    }

    public static function getJsonValue(ModuleTableAttributeModel $attribute): false|string|null
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $depth = $attribute->requirement['depth'] ?? $faker->numberBetween(1, 3);
        $elements = $attribute->requirement['elements'] ?? $faker->numberBetween(1, 5);

        $data = [];
        for ($i = 0; $i < $elements; $i++) {
            $key = $faker->word;

            switch ($faker->numberBetween(1, 6)) {
                case 1:
                    $data[$key] = $faker->word;
                    break;
                case 2:
                    $data[$key] = $faker->numberBetween(
                        $attribute->requirement['min'] ?? 1,
                        $attribute->requirement['max'] ?? 1000
                    );
                    break;
                case 3:
                    $data[$key] = $faker->boolean;
                    break;
                case 4:
                    $values = $attribute->items ? explode(',', $attribute->items) : ['a', 'b', 'c'];
                    $data[$key] = $faker->randomElements($values, $faker->numberBetween(1, 3));
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

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public static function getLongBlobValue(ModuleTableAttributeModel $attribute): ?string
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $maxSize = $attribute->requirement['max_size'] ?? 1048576; // Default 1MB
        $minSize = $attribute->requirement['min_size'] ?? 1;
        $length = $faker->numberBetween($minSize, $maxSize);

        return base64_encode(random_bytes($length));
    }

    public static function getLongTextValue(ModuleTableAttributeModel $attribute): ?string
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $maxParagraphs = $attribute->requirement['max_paragraphs'] ?? 50;
        $minParagraphs = $attribute->requirement['min_paragraphs'] ?? 1;
        $maxSize = $attribute->requirement['max_size'] ?? 1048576;

        $paragraphs = $faker->numberBetween($minParagraphs, $maxParagraphs);
        $text = $faker->paragraphs($paragraphs, true);

        return mb_substr($text, 0, $maxSize);
    }

    public static function getMediumBlobValue(ModuleTableAttributeModel $attribute): ?string
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $maxSize = $attribute->requirement['max_size'] ?? 262144; // Default 256KB
        $minSize = $attribute->requirement['min_size'] ?? 1;
        $length = $faker->numberBetween($minSize, $maxSize);

        return base64_encode(random_bytes($length));
    }

    public static function getMediumIntValue(ModuleTableAttributeModel $attribute): ?int
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $min = $attribute->unsigned ? 0 : -8388608;
        $max = $attribute->unsigned ? 16777215 : 8388607;

        return $faker->numberBetween($min, $max);
    }

    public static function getMediumTextValue(ModuleTableAttributeModel $attribute): ?string
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $maxSize = $attribute->size ?? 262144;
        $paragraphs = $faker->numberBetween(1, 25);

        return mb_substr($faker->paragraphs($paragraphs, true), 0, $maxSize);
    }

    public static function getSetValue(ModuleTableAttributeModel $attribute, array $allowedValues): ?string
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

    public static function getSmallIntValue(ModuleTableAttributeModel $attribute): ?int
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $min = $attribute->requirement['min'] ?? ($attribute->unsigned ? 0 : -32768);
        $max = $attribute->requirement['max'] ?? ($attribute->unsigned ? 65535 : 32767);
        return $faker->numberBetween($min, $max);
    }

    public static function getTextValue(ModuleTableAttributeModel $attribute): ?string
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $maxSize = $attribute->size ?? 65535;
        $minParagraphs = $attribute->requirement['min_paragraphs'] ?? 1;
        $maxParagraphs = $attribute->requirement['max_paragraphs'] ?? 10;
        $paragraphs = $faker->numberBetween($minParagraphs, $maxParagraphs);

        return mb_substr($faker->paragraphs($paragraphs, true), 0, $maxSize);
    }

    public static function getTimeValue(ModuleTableAttributeModel $attribute)
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $minTime = $attribute->requirement['min'] ?? '-838:59:59';
        $maxTime = $attribute->requirement['max'] ?? '838:59:59';

        return $faker->dateTimeBetween($minTime, $maxTime)->format('H:i:s');
    }

    public static function getTimestampValue(ModuleTableAttributeModel $attribute): ?string
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $minTimestamp = $attribute->requirement['min'] ?? '1970-01-01 00:00:01';
        $maxTimestamp = $attribute->requirement['max'] ?? '2038-01-19 03:14:07';

        return $faker->dateTimeBetween($minTimestamp, $maxTimestamp)->format('Y-m-d H:i:s');
    }

    public static function getTinyBlobValue(ModuleTableAttributeModel $attribute): ?string
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $minSize = $attribute->requirement['min_size'] ?? 1;
        $maxSize = min($attribute->requirement['max_size'] ?? 255, 255);
        $length = $faker->numberBetween($minSize, $maxSize);

        return base64_encode(random_bytes($length));
    }

    public static function getTinyIntValue(ModuleTableAttributeModel $attribute): ?int
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $min = $attribute->requirement['min'] ?? ($attribute->unsigned ? 0 : -128);
        $max = $attribute->requirement['max'] ?? ($attribute->unsigned ? 255 : 127);

        return $faker->numberBetween($min, $max);
    }

    public static function getTinyTextValue(ModuleTableAttributeModel $attribute): ?string
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $maxSize = min($attribute->requirement['max_size'] ?? 255, 255);
        return mb_substr($faker->text(), 0, $maxSize);
    }

    public static function getVarcharValue(ModuleTableAttributeModel $attribute): ?string
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $maxLength = min($attribute->size ?? 255, 65535);

        if ($attribute->items) {
            $allowedValues = explode(',', $attribute->items);
            return $faker->randomElement($allowedValues);
        }

        return mb_substr($faker->text($maxLength), 0, $maxLength);
    }

    public static function getVarbinaryValue(ModuleTableAttributeModel $attribute): ?string
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $minLength = $attribute->requirement['min_size'] ?? 1;
        $maxLength = min($attribute->requirement['max_size'] ?? 65535, 65535);
        $length = $faker->numberBetween($minLength, $maxLength);

        return substr(base64_encode(random_bytes($length)), 0, $length);
    }

    public static function getYearValue(ModuleTableAttributeModel $attribute): int|null
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $minYear = $attribute->requirement['min'] ?? 1901;
        $maxYear = $attribute->requirement['max'] ?? 2155;

        return $faker->numberBetween($minYear, $maxYear);
    }

    public static function getDateValue(ModuleTableAttributeModel $attribute)
    {
        $faker = Factory::create();

        if ($attribute->nullable && $faker->boolean(10)) {
            return null;
        }

        $minDate = $attribute->requirement['min'] ?? '1000-01-01';
        $maxDate = $attribute->requirement['max'] ?? '9999-12-31';

        return $faker->dateTimeBetween($minDate, $maxDate)->format('Y-m-d');
    }
}
