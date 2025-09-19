<?php

declare(strict_types=1);

namespace Modules\Base\Services\Date;

final class DateFn
{
    public function validate(string $date): bool
    {
        if (! str_contains($date, 'T')) {
            return false;
        }

        $date = str_replace('T', ' ', $date);
        $date = explode('.', $date);
        $newDate = explode('-', $date[0]);

        $year = (int) $newDate[0];
        $month = (int) $newDate[1];
        $day = (int) explode(' ', $newDate[2])[0];

        return checkdate($month, $day, $year);
    }
}
