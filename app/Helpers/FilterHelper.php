<?php

namespace App\Helpers;

use App\Support\Config;
use InvalidArgumentException;

final class FilterHelper
{
    public static function sanitize(array $input): array
    {
        $appConfig = Config::get('app');
        $rightsConfig = Config::get('rights_map');

        $today = DateHelper::today();
        $maxRangeDays = (int) ($appConfig['max_date_range_days'] ?? 370);

        $startDate = $input['start_date'] ?? $today;
        $endDate = $input['end_date'] ?? $today;
        $clinic = trim((string) ($input['clinic'] ?? ''));
        $rights = strtoupper(trim((string) ($input['rights'] ?? '')));
        $patientType = strtoupper(trim((string) ($input['patient_type'] ?? '')));

        if (!self::isValidDate($startDate)) {
            $startDate = $today;
        }

        if (!self::isValidDate($endDate)) {
            $endDate = $today;
        }

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        if (DateHelper::daysDiff($startDate, $endDate) > $maxRangeDays) {
            throw new InvalidArgumentException('ช่วงวันที่ต้องห่างกันไม่เกิน ' . $maxRangeDays . ' วัน');
        }

        if (!array_key_exists($rights, $rightsConfig)) {
            $rights = '';
        }

        if (!in_array($patientType, $appConfig['allowed_patient_types'], true)) {
            $patientType = '';
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'clinic' => $clinic,
            'rights' => $rights,
            'patient_type' => $patientType,
        ];
    }

    private static function isValidDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
