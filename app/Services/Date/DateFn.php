<?php

namespace Modules\Base\Services\Date;

class DateFn
{
    public function validate($date)
    {
        if (!str_contains($date, 'T')) {
            return false;
        }

        $date = str_replace('T', ' ', $date);
        $date = explode('.', $date);
        $newDate = explode('-', $date[0]);

        $year = $newDate[0];
        $month = $newDate[1];
        $day = explode(' ', $newDate[2])[0];
        $dateValidate = checkdate($month, $day, $year);

        return $dateValidate;
    }
}
