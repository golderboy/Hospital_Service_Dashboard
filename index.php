<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (class_exists('App\\Support\\AuditLogger')) {
    \App\Support\AuditLogger::log('dashboard_page_view', 'SUCCESS');
}

$appConfig = class_exists('App\\Support\\Config') ? (\App\Support\Config::get('app') ?: []) : [];
$defaultStart = date('Y-m-d');
$defaultEnd = date('Y-m-d');
$cspNonce = class_exists('App\\Support\\Security') && method_exists('App\Support\Security', 'cspNonce') ? \App\Support\Security::cspNonce() : ''; 
$baseUrl = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$baseUrl = $baseUrl === '/' ? '' : $baseUrl;
$cssVersion = is_file(__DIR__ . '/assets/css/app.css') ? (string) filemtime(__DIR__ . '/assets/css/app.css') : (string) time();
$jsVersion = is_file(__DIR__ . '/assets/js/dashboard.js') ? (string) filemtime(__DIR__ . '/assets/js/dashboard.js') : (string) time();
$fontAwesomeHref = htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') . '/assets/vendor/fontawesome/css/all.css';
$hasFontAwesome = true;

$serviceRow1 = [
    ['key' => 'total_service_count', 'label' => 'รับบริการรวม', 'icon' => 'fas fa-hospital-user', 'drill' => true],
    ['key' => 'opd_patients', 'label' => 'ผู้ป่วย OPD', 'icon' => 'fas fa-user-injured', 'drill' => true],
    ['key' => 'er_patients', 'label' => 'ผู้ป่วย ER', 'icon' => 'fas fa-ambulance', 'drill' => true],
    ['key' => 'appointment_attended_count', 'label' => 'ผู้ป่วยนัด', 'icon' => 'fas fa-calendar-check', 'drill' => true],
    ['key' => 'appointment_missed_count', 'label' => 'ผู้ป่วยไม่มาตามนัด', 'icon' => 'fas fa-calendar-times', 'drill' => true],
    ['key' => 'identity_verified', 'label' => 'ยืนยันตัวตน', 'icon' => 'fas fa-id-card-alt', 'drill' => true],
];

$serviceRow2 = [
    ['key' => 'ipd_discharged_patients', 'label' => 'จำนวนผู้ป่วยใน (จำหน่ายแล้ว)', 'icon' => 'fas fa-procedures', 'drill' => true],
    ['key' => 'ipd_ot_sum', 'label' => 'วันนอนรวม (IPD)', 'icon' => 'fas fa-bed', 'drill' => true],
    ['key' => 'ipd_avg_rw', 'label' => 'เฉลี่ย RW', 'icon' => 'fas fa-notes-medical', 'drill' => false],
    ['key' => 'ipd_total_adjrw_cases', 'label' => 'Total AdjRW', 'icon' => 'fas fa-hashtag', 'drill' => true],
    ['key' => 'ipd_sum_adjrw', 'label' => 'SUM AdjRW', 'icon' => 'fas fa-calculator', 'drill' => true],
    ['key' => 'ipd_cmi', 'label' => 'CMI', 'icon' => 'fas fa-chart-area', 'drill' => true],
];

$serviceRow3 = [
    ['key' => 'total_service_today', 'label' => 'รับบริการรวม วันนี้', 'icon' => 'fas fa-calendar-day', 'drill' => true],
    ['key' => 'opd_today', 'label' => 'ผู้ป่วย OPD วันนี้', 'icon' => 'fas fa-clinic-medical', 'drill' => true],
    ['key' => 'er_today', 'label' => 'ผู้ป่วย ER วันนี้', 'icon' => 'fas fa-first-aid', 'drill' => true],
    ['key' => 'ipd_new_today', 'label' => 'ผู้ป่วย IPD (ใหม่)', 'icon' => 'fas fa-file-medical', 'drill' => true],
    ['key' => 'appointment_attended_today', 'label' => 'ผู้ป่วยนัด วันนี้', 'icon' => 'fas fa-calendar-plus', 'drill' => true],
    ['key' => 'identity_verified_today', 'label' => 'ยืนยันตัวตน วันนี้', 'icon' => 'fas fa-user-check', 'drill' => true],
];

$serviceRow4 = [
    ['key' => 'registry_population_total', 'label' => 'ประชากรจริง', 'type' => 'population', 'icon' => 'fas fa-users'],
    ['key' => 'population_in_district', 'label' => 'ประชากรในอำเภอ', 'type' => 'population', 'icon' => 'fas fa-map-marked-alt'],
    ['key' => 'population_in_district_thai', 'label' => 'ประชากรในอำเภอ คนไทย', 'type' => 'population', 'icon' => 'fas fa-flag'],
    ['key' => 'population_in_area', 'label' => 'ประชากรในเขต', 'type' => 'population', 'icon' => 'fas fa-home'],
    ['key' => 'population_in_area_thai', 'label' => 'ประชากรในเขต คนไทย', 'type' => 'population', 'icon' => 'fas fa-house-user'],
    ['key' => 'referout_total', 'label' => 'จำนวนการส่งต่อ', 'type' => 'summary', 'icon' => 'fas fa-share-square', 'drill' => true],
];
?><!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appConfig['name'] ?? 'Hospital Service Dashboard', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/vendor/fontawesome/css/all.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/css/app.css?v=<?= htmlspecialchars($cssVersion, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<div class="page-shell">
    <header class="page-header page-header-dashboard panel">
        <div class="header-brand">
            <div class="logo-slot" aria-label="Sobmoei Hospital">
                <?php if (is_file(__DIR__ . '/assets/img/moph.png')): ?>
                    <img src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/img/moph.png" alt="Sobmoei Hospital" class="logo-slot-image">
                <?php else: ?>
                    <i class="fas fa-image" aria-hidden="true"></i>
                    <span>LOGO</span>
                <?php endif; ?>
            </div>
            <div class="header-copy">
                <h1><?= htmlspecialchars($appConfig['name'] ?? 'Hospital Service Dashboard', ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="subtitle">สรุปการบริการ ประชากร สิทธิ โรค การเรียกเก็บ</p>
            </div>
        </div>
        <div class="meta-box">
            <div class="meta-label"><i class="fas fa-sync-alt" aria-hidden="true"></i> อัปเดตข้อมูลล่าสุด</div>
            <div class="meta-value" id="lastUpdated">-</div>
        </div>
    </header>

    <section class="panel">
        <form id="filterForm" class="filter-grid">
            <div class="field">
                <label for="startDate">วันที่เริ่มต้น</label>
                <input type="date" id="startDate" name="start_date" value="<?= htmlspecialchars($defaultStart, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="endDate">วันที่สิ้นสุด</label>
                <input type="date" id="endDate" name="end_date" value="<?= htmlspecialchars($defaultEnd, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field field-wide">
                <label for="clinic">คลินิก / จุดบริการ</label>
                <select id="clinic" name="clinic">
                    <option value="">ทั้งหมด</option>
                </select>
            </div>
            <div class="field">
                <label for="rights">กลุ่มสิทธิ</label>
                <select id="rights" name="rights">
                    <option value="">ทั้งหมด</option>
                </select>
            </div>
            <div class="field">
                <label for="patientType">ประเภทบริการ</label>
                <select id="patientType" name="patient_type">
                    <option value="">ทั้งหมด</option>
                    <option value="OPD">OPD</option>
                    <option value="IPD">IPD</option>
                    <option value="ER">ER</option>
                </select>
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search" aria-hidden="true"></i> ค้นหา</button>
                <button type="button" class="btn btn-secondary" id="resetFilter"><i class="fas fa-undo" aria-hidden="true"></i> รีเซ็ต</button>
                <button type="button" class="btn btn-outline" id="printDashboard"><i class="fas fa-print" aria-hidden="true"></i> พิมพ์</button>
                <button type="button" class="btn btn-outline" data-export-scope="summary" data-export-format="csv"><i class="fas fa-file-csv" aria-hidden="true"></i> Export Summary CSV</button>
                <button type="button" class="btn btn-outline" data-export-scope="summary" data-export-format="excel"><i class="fas fa-file-excel" aria-hidden="true"></i> Export Summary Excel</button>
                <span class="hint">กำหนดช่วงวันที่ได้ไม่เกิน <?= (int) ($appConfig['max_date_range_days'] ?? 370) ?> วัน และ drill-down จำกัดแสดง 1,000 แถวแรก</span>
            </div>
        </form>
        <div id="errorBox" class="alert error hidden"></div>
    </section>

    <section class="section-block summary-section">
        <div class="section-heading"><i class="fas fa-chart-line" aria-hidden="true"></i> สรุปการบริการ</div>
        <div class="summary-stack">
            <div class="summary-group">
                <div class="summary-group-label">
                    <div class="summary-group-title"><i class="fas fa-notes-medical" aria-hidden="true"></i> ภาพรวมบริการตามตัวกรอง</div>
                    <div class="summary-group-subtitle">อิงช่วงวันที่และตัวกรองที่เลือก คลิก card เพื่อดูรายละเอียด</div>
                </div>
                <div class="card-grid card-grid-6 compact-grid">
                    <?php foreach ($serviceRow1 as $metric): ?>
                        <article class="metric-card drillable" data-drill-key="<?= htmlspecialchars($metric['key'], ENT_QUOTES, 'UTF-8') ?>" data-drill-label="<?= htmlspecialchars($metric['label'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="metric-card-top">
                                <span class="metric-icon"><i class="<?= htmlspecialchars($metric['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i></span>
                                <div class="metric-label"><?= htmlspecialchars($metric['label'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="metric-value" data-key="<?= htmlspecialchars($metric['key'], ENT_QUOTES, 'UTF-8') ?>">-</div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="summary-group">
                <div class="summary-group-label">
                    <div class="summary-group-title"><i class="fas fa-procedures" aria-hidden="true"></i> ผู้ป่วยในและดัชนี DRG</div>
                    <div class="summary-group-subtitle">อิงช่วงวันที่และตัวกรองที่เลือก โดยนับจากผู้ป่วยในที่มีวันจำหน่ายแล้ว</div>
                </div>
                <div class="card-grid card-grid-6 compact-grid">
                    <?php foreach ($serviceRow2 as $metric): ?>
                        <article class="metric-card metric-card-soft<?= !empty($metric['drill']) ? ' drillable' : '' ?>"<?= !empty($metric['drill']) ? ' data-drill-key="' . htmlspecialchars($metric['key'], ENT_QUOTES, 'UTF-8') . '" data-drill-label="' . htmlspecialchars($metric['label'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                            <div class="metric-card-top">
                                <span class="metric-icon"><i class="<?= htmlspecialchars($metric['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i></span>
                                <div class="metric-label"><?= htmlspecialchars($metric['label'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="metric-value" data-key="<?= htmlspecialchars($metric['key'], ENT_QUOTES, 'UTF-8') ?>">-</div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="summary-group">
                <div class="summary-group-label">
                    <div class="summary-group-title"><i class="fas fa-calendar-day" aria-hidden="true"></i> บริการประจำวัน</div>
                    <div class="summary-group-subtitle">สรุปข้อมูลเฉพาะวันที่ปัจจุบันหรือวันที่เลือก</div>
                </div>
                <div class="card-grid card-grid-6 compact-grid">
                    <?php foreach ($serviceRow3 as $metric): ?>
                        <article class="metric-card metric-card-today<?= !empty($metric['drill']) ? ' drillable' : '' ?>"<?= !empty($metric['drill']) ? ' data-drill-key="' . htmlspecialchars($metric['key'], ENT_QUOTES, 'UTF-8') . '" data-drill-label="' . htmlspecialchars($metric['label'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                            <div class="metric-card-top">
                                <span class="metric-icon"><i class="<?= htmlspecialchars($metric['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i></span>
                                <div class="metric-label"><?= htmlspecialchars($metric['label'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="metric-value" data-key="<?= htmlspecialchars($metric['key'], ENT_QUOTES, 'UTF-8') ?>">-</div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="summary-group">
                <div class="summary-group-label">
                    <div class="summary-group-title"><i class="fas fa-users" aria-hidden="true"></i> ประชากรและการส่งต่อ</div>
                    <div class="summary-group-subtitle">ตัวเลขฐานประชากรไม่อิงตัวกรอง</div>
                </div>
                <div class="card-grid card-grid-6 compact-grid">
                    <?php foreach ($serviceRow4 as $metric): ?>
                        <article class="metric-card metric-card-static metric-card-population<?= !empty($metric['drill']) ? ' drillable' : '' ?>"<?= !empty($metric['drill']) ? ' data-drill-key="' . htmlspecialchars($metric['key'], ENT_QUOTES, 'UTF-8') . '" data-drill-label="' . htmlspecialchars($metric['label'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                            <div class="metric-card-top">
                                <span class="metric-icon metric-icon-static"><i class="<?= htmlspecialchars($metric['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i></span>
                                <div class="metric-label"><?= htmlspecialchars($metric['label'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="metric-value metric-value-static" <?= $metric['type'] === 'population' ? 'data-population-key' : 'data-key' ?>="<?= htmlspecialchars($metric['key'], ENT_QUOTES, 'UTF-8') ?>">-</div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="note" id="populationNote">ประชากรจริงทั้งอำเภออ้างอิงจากสำนักทะเบียน ส่วนประชากรฐานบริการอ้างอิงจาก hosxp</p>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading"><i class="fas fa-chart-bar" aria-hidden="true"></i> กราฟบริการรายเดือน</div>
        <div class="chart-grid chart-grid-2">
            <div class="panel chart-panel">
                <div class="panel-title">สัดส่วนผู้มารับบริการ OPD รายเดือน</div>
                <div class="chart-box" id="opdMonthlyChart"></div>
            </div>
            <div class="panel chart-panel">
                <div class="panel-title">สัดส่วนผู้มารับบริการ IPD รายเดือน</div>
                <div class="chart-box" id="ipdMonthlyChart"></div>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading"><i class="fas fa-chart-pie" aria-hidden="true"></i> สิทธิและประชากรในเขต</div>
        <div class="content-grid content-grid-3">
            <div class="panel chart-panel">
                <div class="panel-title">สัดส่วนสิทธิที่มารับบริการ รายเดือน</div>
                <div class="chart-box" id="rightsMonthlyChart"></div>
            </div>
            <div class="panel chart-panel">
                <div class="panel-title">สัดส่วนสิทธิที่ผู้ป่วยในเขต</div>
                <div class="chart-box" id="rightsInAreaChart"></div>
            </div>
            <div class="panel table-panel">
                <div class="panel-header-actions">
                    <div class="panel-title">ปริมาณประชาชนในเขต รายหมู่บ้าน</div>
                    <div class="panel-tools">
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="village_population_summary" data-export-format="csv">CSV</button>
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="village_population_summary" data-export-format="excel">Excel</button>
                    </div>
                </div>
                <div id="villagePopulationTable"></div>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading"><i class="fas fa-disease" aria-hidden="true"></i> 10 อันดับโรค</div>
        <div class="content-grid content-grid-3">
            <div class="panel table-panel table-panel-disease">
                <div class="panel-header-actions">
                    <div class="panel-title">10 อันดับ โรค OPD</div>
                    <div class="panel-tools">
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="opd_diseases_top10" data-export-format="csv">CSV</button>
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="opd_diseases_top10" data-export-format="excel">Excel</button>
                    </div>
                </div>
                <div id="opdDiseasesTable"></div>
            </div>
            <div class="panel table-panel table-panel-disease">
                <div class="panel-header-actions">
                    <div class="panel-title">10 อันดับ โรค IPD</div>
                    <div class="panel-tools">
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="ipd_diseases_top10" data-export-format="csv">CSV</button>
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="ipd_diseases_top10" data-export-format="excel">Excel</button>
                    </div>
                </div>
                <div id="ipdDiseasesTable"></div>
            </div>
            <div class="panel table-panel table-panel-disease">
                <div class="panel-header-actions">
                    <div class="panel-title">10 อันดับ โรคเรื้อรัง (OPD)</div>
                    <div class="panel-tools">
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="chronic_opd_diseases_top10" data-export-format="csv">CSV</button>
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="chronic_opd_diseases_top10" data-export-format="excel">Excel</button>
                    </div>
                </div>
                <div id="chronicDiseasesTable"></div>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading"><i class="fas fa-table" aria-hidden="true"></i> ตารางวิเคราะห์เพิ่มเติม</div>
        <div class="content-grid content-grid-2">
            <div class="panel table-panel table-panel-wide">
                <div class="panel-header-actions">
                    <div class="panel-title">10 อันดับ ค่าบริการในคลินิก</div>
                    <div class="panel-tools">
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="clinic_charges_top10" data-export-format="csv">CSV</button>
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="clinic_charges_top10" data-export-format="excel">Excel</button>
                    </div>
                </div>
                <div id="clinicChargesTable"></div>
            </div>
            <div class="panel table-panel table-panel-wide">
                <div class="panel-header-actions">
                    <div class="panel-title">10 อันดับ คลินิกที่ยืนยันตัวตน</div>
                    <div class="panel-tools">
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="auth_clinics_top10" data-export-format="csv">CSV</button>
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="auth_clinics_top10" data-export-format="excel">Excel</button>
                    </div>
                </div>
                <div id="authClinicTable"></div>
            </div>
        </div>
    </section>

    <section class="section-block claim-section">
        <div class="section-heading"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i> วิเคราะห์การเคลม</div>
        <div class="claim-note" id="claimNote">ข้อมูลเคลมแสดงเฉพาะแบบสรุปย้อนหลังตามช่วงวันที่ที่เลือก</div>
        <div class="card-grid card-grid-4 compact-grid claim-card-grid">
            <article class="metric-card metric-card-claim"><div class="metric-card-top"><span class="metric-icon"><i class="fas fa-notes-medical" aria-hidden="true"></i></span><div class="metric-label">บริการทั้งหมด</div></div><div class="metric-value" data-claim-key="total_visit">-</div></article>
            <article class="metric-card metric-card-claim"><div class="metric-card-top"><span class="metric-icon"><i class="fas fa-coins" aria-hidden="true"></i></span><div class="metric-label">ค่าใช้จ่ายรวม</div></div><div class="metric-value" data-claim-key="total_charge">-</div></article>
            <article class="metric-card metric-card-claim"><div class="metric-card-top"><span class="metric-icon"><i class="fas fa-cash-register" aria-hidden="true"></i></span><div class="metric-label">รายได้(เก็บเงินสด)</div></div><div class="metric-value" data-claim-key="patient_paid_total">-</div></article>
            <article class="metric-card metric-card-claim"><div class="metric-card-top"><span class="metric-icon"><i class="fas fa-paper-plane" aria-hidden="true"></i></span><div class="metric-label">ส่งแล้ว</div></div><div class="metric-value" data-claim-key="sent_claim_amount">-</div></article>
            <article class="metric-card metric-card-claim"><div class="metric-card-top"><span class="metric-icon"><i class="fas fa-hourglass-half" aria-hidden="true"></i></span><div class="metric-label">รอจ่าย</div></div><div class="metric-value" data-claim-key="wait_pay_claim_amount">-</div></article>
            <article class="metric-card metric-card-claim"><div class="metric-card-top"><span class="metric-icon"><i class="fas fa-check-circle" aria-hidden="true"></i></span><div class="metric-label">โอนแล้ว</div></div><div class="metric-value" data-claim-key="settled_claim_amount">-</div></article>
            <article class="metric-card metric-card-claim"><div class="metric-card-top"><span class="metric-icon"><i class="fas fa-ban" aria-hidden="true"></i></span><div class="metric-label">ไม่ประสงค์เบิก</div></div><div class="metric-value" data-claim-key="unclaimed_service_amount">-</div></article>
            <article class="metric-card metric-card-claim"><div class="metric-card-top"><span class="metric-icon"><i class="fas fa-search-dollar" aria-hidden="true"></i></span><div class="metric-label">ต้องทบทวน</div></div><div class="metric-value" data-claim-key="review_claim_amount">-</div></article>
            <article class="metric-card metric-card-claim"><div class="metric-card-top"><span class="metric-icon"><i class="fas fa-question-circle" aria-hidden="true"></i></span><div class="metric-label">ยังไม่มีข้อมูลเคลม</div></div><div class="metric-value" data-claim-key="no_claim_record_amount">-</div></article>
            <article class="metric-card metric-card-claim"><div class="metric-card-top"><span class="metric-icon"><i class="fas fa-file-medical-alt" aria-hidden="true"></i></span><div class="metric-label">โอนแล้ว: ค่าใช้จ่ายรวม</div></div><div class="metric-value" data-claim-key="settled_total_charge">-</div></article>
        </div>

        <div class="chart-grid chart-grid-2 claim-chart-grid">
            <div class="panel chart-panel">
                <div class="panel-title">ภาพรวมรายเดือน: ค่าใช้จ่าย / เงินสด / ยอดเคลม</div>
                <div id="claimMonthlyChart" class="chart-box"></div>
            </div>
            <div class="panel chart-panel">
                <div class="panel-title">เปรียบเทียบภาระที่โรงพยาบาลต้องแบกรับ</div>
                <div id="claimBurdenChart" class="chart-box"></div>
            </div>
        </div>

        <div class="content-grid content-grid-3 claim-table-grid">
            <div class="panel table-panel table-panel-wide">
                <div class="panel-header-actions">
                    <div class="panel-title">สรุปตามสถานะเคลม</div>
                    <div class="panel-tools">
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="claim_status_summary" data-export-format="csv">CSV</button>
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="claim_status_summary" data-export-format="excel">Excel</button>
                    </div>
                </div>
                <div id="claimStatusTable"></div>
            </div>
            <div class="panel table-panel table-panel-wide">
                <div class="panel-header-actions">
                    <div class="panel-title">สรุปรายเดือนการเคลม</div>
                    <div class="panel-tools">
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="claim_monthly_summary" data-export-format="csv">CSV</button>
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="claim_monthly_summary" data-export-format="excel">Excel</button>
                    </div>
                </div>
                <div id="claimMonthlyTable"></div>
            </div>
            <div class="panel table-panel table-panel-wide">
                <div class="panel-header-actions">
                    <div class="panel-title">เฉพาะเคสโอนแล้ว</div>
                    <div class="panel-tools">
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="claim_settled_finance" data-export-format="csv">CSV</button>
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="claim_settled_finance" data-export-format="excel">Excel</button>
                    </div>
                </div>
                <div id="claimSettledTable"></div>
            </div>
        </div>
    </section>
<!-- 
    #DEBIG - ส่วนนี้เป็นตัวอย่างการแสดงข้อมูล ETL Monitoring และ Audit log ซึ่งจะดึงข้อมูลจากฐานข้อมูลเดียวกับที่ใช้ใน dashboard นี้ โดยจะมีการแสดงสถานะงาน ETL ล่าสุด และประวัติการรัน ETL รวมถึง audit log ล่าสุดที่เกี่ยวข้อง
    <section class="section-block">
        <div class="section-heading"><i class="fas fa-server" aria-hidden="true"></i> ETL Monitoring / Audit</div>
        <div class="chart-grid chart-grid-2 monitoring-grid">
            <div class="panel">
                <div class="panel-title">สถานะงาน ETL ล่าสุด</div>
                <div id="etlAlertBox" class="alert hidden"></div>
                <div id="etlOverviewCards" class="monitor-overview-grid"></div>
            </div>
            <div class="panel">
                <div class="panel-header-actions">
                    <div class="panel-title">ประวัติ ETL ล่าสุด</div>
                    <div class="panel-tools">
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="etl_recent_runs" data-export-format="csv">CSV</button>
                        <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="etl_recent_runs" data-export-format="excel">Excel</button>
                    </div>
                </div>
                <div id="etlRunsTable"></div>
            </div>
        </div>
        <div class="panel monitoring-audit-panel">
            <div class="panel-header-actions">
                <div class="panel-title">Audit log ล่าสุด</div>
                <div class="panel-tools">
                    <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="audit_recent_events" data-export-format="csv">CSV</button>
                    <button type="button" class="btn btn-mini btn-outline" data-export-scope="table" data-export-key="audit_recent_events" data-export-format="excel">Excel</button>
                </div>
            </div>
            <div id="auditLogTable"></div>
        </div>
    </section>
-->
</div>

<div id="loadingOverlay" class="loading-overlay hidden" aria-live="polite" aria-busy="true" aria-label="กำลังโหลดข้อมูล">
    <div class="loading-box">
        <div class="loading-spinner-wrap">
            <i class="fas fa-spinner fa-spin loading-fa-icon" aria-hidden="true"></i>
            <span class="loading-spinner-fallback" aria-hidden="true"></span>
        </div>
        <div class="loading-title">กำลังโหลดข้อมูล</div>
        <div class="loading-subtitle" id="loadingText">กรุณารอสักครู่ ระบบกำลังประมวลผลและดึงข้อมูลขึ้นหน้าจอ</div>
    </div>
</div>

<div id="drilldownModal" class="modal hidden" aria-hidden="true">
    <div class="modal-backdrop" data-close-modal="1"></div>
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="drilldownTitle">
        <div class="modal-header">
            <div>
                <div class="modal-title" id="drilldownTitle">รายละเอียด</div>
                <div class="modal-subtitle" id="drilldownSubtitle">-</div>
            </div>
            <button type="button" class="btn btn-secondary btn-mini" id="closeDrilldown"><i class="fas fa-times"></i> ปิด</button>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline btn-mini" id="printDrilldown"><i class="fas fa-print"></i> พิมพ์</button>
            <button type="button" class="btn btn-outline btn-mini" id="exportDrilldownCsv"><i class="fas fa-file-csv"></i> CSV</button>
            <button type="button" class="btn btn-outline btn-mini" id="exportDrilldownExcel"><i class="fas fa-file-excel"></i> Excel</button>
        </div>
        <div class="modal-body" id="drilldownContent"></div>
    </div>
</div>

<script type="application/json" id="dashboardConfigJson">
<?= json_encode([
    'filtersEndpoint' => $baseUrl . '/api/dashboard_filters.php',
    'summaryEndpoint' => $baseUrl . '/api/dashboard_summary.php',
    'populationEndpoint' => $baseUrl . '/api/population.php',
    'detailEndpoint' => $baseUrl . '/api/dashboard_detail.php',
    'drilldownEndpoint' => $baseUrl . '/api/drilldown.php',
    'exportEndpoint' => $baseUrl . '/api/export.php',
    'monitoringEndpoint' => $baseUrl . '/api/monitoring.php',
    'maxDateRangeDays' => (int) ($appConfig['max_date_range_days'] ?? 370),
    'defaultStartDate' => $defaultStart,
    'defaultEndDate' => $defaultEnd,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>
<script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/js/jquery-3.7.1.min.js"></script>
<script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/bootstrap/js/bootstrap.min.js"></script>
<script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/vendor/fontawesome/js/all.js"></script>
<script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/js/dataTables.js"></script>
<script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/highcharts/js/highcharts.js"></script>
<script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/js/dashboard.js?v=<?= htmlspecialchars($jsVersion, ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
