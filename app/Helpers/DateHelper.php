<?php

namespace App\Helpers;

use DateInterval;
use DateTimeImmutable;

final class DateHelper
{
    public static function today(): string
    {
        return date('Y-m-d');
    }

    public static function currentYearStart(): string
    {
        return date('Y-01-01');
    }

    public static function daysDiff(string $startDate, string $endDate): int
    {
        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);

        return (int) $start->diff($end)->days;
    }

    public static function subtractDays(string $date, int $days): string
    {
        $base = new DateTimeImmutable($date);
        return $base->sub(new DateInterval('P' . max(0, $days) . 'D'))->format('Y-m-d');
    }
}
