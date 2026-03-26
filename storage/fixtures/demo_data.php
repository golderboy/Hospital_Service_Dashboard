<?php

declare(strict_types=1);

return [
    'filters' => [
        'clinics' => [
            ['value' => '001', 'label' => 'ห้องตรวจ OPD'],
            ['value' => '057', 'label' => 'จุดซักประวัติ OPD'],
            ['value' => '002', 'label' => 'ห้องตรวจ ER ในเวลา'],
            ['value' => '031', 'label' => 'ห้องตรวจ แพทย์แผนไทย'],
        ],
        'rights' => [
            ['value' => 'UCS', 'label' => 'UCS'],
            ['value' => 'OFC', 'label' => 'OFC'],
            ['value' => 'SSS', 'label' => 'SSS'],
            ['value' => 'OTHERS', 'label' => 'อื่นๆ'],
        ],
    ],
    'summary' => [
        'total_patients' => 252,
        'opd_patients' => 218,
        'opd_visits' => 200,
        'ipd_patients' => 14,
        'identity_verified' => 241,
        'identity_not_verified' => 11,
        'total_service_charges' => 148250.50,
        'appointments_total_today' => 72,
        'appointments_attended_today' => 49,
        'appointments_missed_today' => 23,
        'emergency_today' => 13,
        'referred_today' => 3,
        'ipd_admissions_today' => 3,
        'ipd_admissions_open' => 18,
        'last_updated' => date('Y-m-d H:i:s'),
    ],
    'population' => [
        'registry_population_total' => 14567,
        'population_in_area' => 2415,
        'population_in_area_thai' => 2326,
        'population_in_district' => 2874,
        'population_in_district_thai' => 2760,
        'source_note' => 'ประชากรจริงทั้งอำเภออ้างอิงสำนักทะเบียน; ประชากรในเขตรับผิดชอบอ้างอิง hosxp.person',
        'last_updated' => date('Y-m-d H:i:s'),
    ],
];
