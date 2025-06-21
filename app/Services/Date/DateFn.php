<?php

namespace Modules\Base\Services\Date;

class DateFn
{
    public function validate(string $date): bool
    {
        if (! str_contains($date, 'T')) {
            return false;
        }

        $date = str_replace('T', ' ', $date);
        $date = explode('.', $date);
        $newDate = explode('-', $date[0]);

        $year = $newDate[0];
        $month = $newDate[1];
        $day = explode(' ', $newDate[2])[0];
        return checkdate($month, $day, $year);
    }
}
