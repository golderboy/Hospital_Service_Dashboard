<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DashboardRepository
{
    private const RIGHTS = [
        ['value' => 'UCS', 'label' => 'UCS'],
        ['value' => 'OFC', 'label' => 'OFC'],
        ['value' => 'SSS', 'label' => 'SSS'],
        ['value' => 'OTHERS', 'label' => 'อื่นๆ'],
    ];

    /** @var PDO|null */
    private $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db;
    }

    private function queryAll(string $sql): array
    {
        if (!$this->db) {
            return [];
        }

        $stmt = $this->db->query($sql);
        if (!$stmt) {
            return [];
        }

        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    private function queryRow(string $sql): array
    {
        if (!$this->db) {
            return [];
        }

        $stmt = $this->db->query($sql);
        if (!$stmt) {
            return [];
        }

        $row = $stmt->fetch();
        return is_array($row) ? $row : [];
    }

    private function queryValue(string $sql, $default = null)
    {
        if (!$this->db) {
            return $default;
        }

        $stmt = $this->db->query($sql);
        if (!$stmt) {
            return $default;
        }

        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    }

    private function prepareStatement(string $sql, array $params = [])
    {
        if (!$this->db) {
            return null;
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->execute($params);
        return $stmt;
    }

    private function preparedRow(string $sql, array $params = []): array
    {
        $stmt = $this->prepareStatement($sql, $params);
        if (!$stmt) {
            return [];
        }

        $row = $stmt->fetch();
        return is_array($row) ? $row : [];
    }

    private function preparedAll(string $sql, array $params = []): array
    {
        $stmt = $this->prepareStatement($sql, $params);
        if (!$stmt) {
            return [];
        }

        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    private function preparedValue(string $sql, array $params = [], $default = null)
    {
        $stmt = $this->prepareStatement($sql, $params);
        if (!$stmt) {
            return $default;
        }

        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    }

    public function isDemoMode(): bool
    {
        return false;
    }

    public function getClinicOptions(): array
    {
        $sql = "
            SELECT DISTINCT fs.main_dep AS value, fs.department_name AS label
            FROM fact_visit_service fs
            WHERE COALESCE(fs.main_dep, '') <> ''
              AND COALESCE(fs.department_name, '') <> ''
              AND fs.is_cancelled = 0
              AND fs.is_test_patient = 0
            ORDER BY fs.department_name ASC
        ";

        return $this->queryAll($sql);
    }

    public function getRightsOptions(): array
    {
        return self::RIGHTS;
    }

    public function getSummary(array $filters): array
    {
        $summary = array_merge(
            $this->fetchServiceSummary($filters),
            $this->fetchIpdDischargeSummary($filters),
            $this->fetchTodaySummary(),
            $this->fetchReferoutSummary($filters)
        );
        $summary['last_updated'] = $this->fetchLastUpdated();

        return $summary;
    }

    public function getPopulationSummary(): array
    {
        $sql = "
            SELECT
                fps.registry_population_total,
                fps.population_in_area,
                fps.population_in_area_thai,
                fps.population_in_district,
                fps.population_in_district_thai,
                fps.source_note,
                DATE_FORMAT(fps.last_refreshed_at, '%Y-%m-%d %H:%i:%s') AS last_updated
            FROM fact_population_snapshot fps
            ORDER BY fps.snapshot_date DESC
            LIMIT 1
        ";

        $row = $this->queryRow($sql);

        $registryPopulationTotal = (int) ($row['registry_population_total'] ?? 0);
        if ($registryPopulationTotal === 0) {
            $fallback = $this->queryValue("SELECT COALESCE(population_total, 0) FROM population_master WHERE is_active = 1 ORDER BY reference_date DESC, population_master_id DESC LIMIT 1");
            $registryPopulationTotal = (int) ($fallback ?: 0);
        }

        return [
            'registry_population_total' => $registryPopulationTotal,
            'population_in_area' => (int) ($row['population_in_area'] ?? 0),
            'population_in_area_thai' => (int) ($row['population_in_area_thai'] ?? 0),
            'population_in_district' => (int) ($row['population_in_district'] ?? 0),
            'population_in_district_thai' => (int) ($row['population_in_district_thai'] ?? 0),
            'source_note' => (string) ($row['source_note'] ?? 'ประชากรจริงทั้งอำเภออ้างอิงสำนักทะเบียน; ประชากรฐานบริการอ้างอิง hosxp.person'),
            'last_updated' => (string) ($row['last_updated'] ?? date('Y-m-d H:i:s')),
        ];
    }

    public function getPhase2Data(array $filters): array
    {
        return [
            'charts' => [
                'opd_monthly_service' => $this->getMonthlyOpdChart($filters),
                'ipd_monthly_service' => $this->getMonthlyIpdChart($filters),
                'rights_monthly_service' => $this->getRightsMonthlyServiceChart($filters),
                'rights_in_area_distribution' => $this->getRightsInAreaDistribution($filters),
            ],
            'tables' => [
                'village_population_summary' => $this->getVillagePopulationSummary(),
                'opd_diseases_top10' => $this->getOpdDiseaseTop10($filters),
                'ipd_diseases_top10' => $this->getIpdDiseaseTop10($filters),
                'chronic_opd_diseases_top10' => $this->getChronicOpdDiseaseTop10($filters),
                'clinic_charges_top10' => $this->getClinicChargesTop10($filters),
                'auth_clinics_top10' => $this->getAuthClinicTop10($filters),
            ],
            'claim' => $this->getClaimAnalytics($filters),
            'last_updated' => $this->fetchLastUpdated(),
        ];
    }


    public function getDrilldownData(string $metric, array $filters): array
    {
        switch ($metric) {
            case 'total_service_count':
                return $this->drillTotalService($filters);
            case 'opd_patients':
                return $this->drillOpdPatients($filters);
            case 'er_patients':
                return $this->drillErPatients($filters);
            case 'appointment_attended_count':
                return $this->drillAppointmentSummary($filters, true);
            case 'appointment_missed_count':
                return $this->drillAppointmentSummary($filters, false);
            case 'identity_verified':
                return $this->drillIdentityVerified($filters);
            case 'ipd_discharged_patients':
            case 'ipd_ot_sum':
                return $this->drillIpdDischarged($filters, false);
            case 'ipd_total_adjrw_cases':
            case 'ipd_sum_adjrw':
            case 'ipd_cmi':
                return $this->drillIpdDischarged($filters, true);
            case 'referout_total':
                return $this->drillReferoutDaily($filters);
            case 'total_service_today':
                return $this->drillTotalService($this->todayFilters());
            case 'opd_today':
                return $this->drillOpdPatients($this->todayFilters());
            case 'er_today':
                return $this->drillErPatients($this->todayFilters());
            case 'appointment_attended_today':
                return $this->drillAppointmentSummary($this->todayFilters(), true);
            case 'identity_verified_today':
                return $this->drillIdentityVerified($this->todayFilters());
            case 'ipd_new_today':
                return $this->drillIpdNewToday();
            default:
                return [
                    'title' => 'ยังไม่รองรับ drill-down',
                    'columns' => [],
                    'rows' => [],
                    'summary' => ['metric' => $metric],
                ];
        }
    }

    public function getExportDataset(string $scope, string $key, array $filters): array
    {
        if ($scope === 'summary') {
            $summary = $this->getSummary($filters);
            $population = $this->getPopulationSummary();
            return [
                'title' => 'dashboard_summary',
                'columns' => [
                    ['key' => 'section', 'label' => 'Section'],
                    ['key' => 'metric', 'label' => 'Metric'],
                    ['key' => 'value', 'label' => 'Value'],
                ],
                'rows' => [
                    ['section' => 'summary', 'metric' => 'total_service_count', 'value' => $summary['total_service_count'] ?? 0],
                    ['section' => 'summary', 'metric' => 'opd_patients', 'value' => $summary['opd_patients'] ?? 0],
                    ['section' => 'summary', 'metric' => 'er_patients', 'value' => $summary['er_patients'] ?? 0],
                    ['section' => 'summary', 'metric' => 'appointment_attended_count', 'value' => $summary['appointment_attended_count'] ?? 0],
                    ['section' => 'summary', 'metric' => 'appointment_missed_count', 'value' => $summary['appointment_missed_count'] ?? 0],
                    ['section' => 'summary', 'metric' => 'identity_verified', 'value' => $summary['identity_verified'] ?? 0],
                    ['section' => 'summary', 'metric' => 'ipd_discharged_patients', 'value' => $summary['ipd_discharged_patients'] ?? 0],
                    ['section' => 'summary', 'metric' => 'ipd_ot_sum', 'value' => $summary['ipd_ot_sum'] ?? 0],
                    ['section' => 'summary', 'metric' => 'ipd_avg_rw', 'value' => $summary['ipd_avg_rw'] ?? 0],
                    ['section' => 'summary', 'metric' => 'ipd_total_adjrw_cases', 'value' => $summary['ipd_total_adjrw_cases'] ?? 0],
                    ['section' => 'summary', 'metric' => 'ipd_sum_adjrw', 'value' => $summary['ipd_sum_adjrw'] ?? 0],
                    ['section' => 'summary', 'metric' => 'ipd_cmi', 'value' => $summary['ipd_cmi'] ?? 0],
                    ['section' => 'summary', 'metric' => 'total_service_today', 'value' => $summary['total_service_today'] ?? 0],
                    ['section' => 'summary', 'metric' => 'opd_today', 'value' => $summary['opd_today'] ?? 0],
                    ['section' => 'summary', 'metric' => 'er_today', 'value' => $summary['er_today'] ?? 0],
                    ['section' => 'summary', 'metric' => 'ipd_new_today', 'value' => $summary['ipd_new_today'] ?? 0],
                    ['section' => 'summary', 'metric' => 'appointment_attended_today', 'value' => $summary['appointment_attended_today'] ?? 0],
                    ['section' => 'summary', 'metric' => 'identity_verified_today', 'value' => $summary['identity_verified_today'] ?? 0],
                    ['section' => 'summary', 'metric' => 'referout_total', 'value' => $summary['referout_total'] ?? 0],
                    ['section' => 'population', 'metric' => 'registry_population_total', 'value' => $population['registry_population_total'] ?? 0],
                    ['section' => 'population', 'metric' => 'population_in_district', 'value' => $population['population_in_district'] ?? 0],
                    ['section' => 'population', 'metric' => 'population_in_district_thai', 'value' => $population['population_in_district_thai'] ?? 0],
                    ['section' => 'population', 'metric' => 'population_in_area', 'value' => $population['population_in_area'] ?? 0],
                    ['section' => 'population', 'metric' => 'population_in_area_thai', 'value' => $population['population_in_area_thai'] ?? 0],
                ],
            ];
        }

        if ($scope === 'drilldown') {
            $data = $this->getDrilldownData($key, $filters);
            return [
                'title' => $key,
                'columns' => $data['columns'] ?? [],
                'rows' => $data['rows'] ?? [],
            ];
        }

        if ($scope === 'table' && in_array($key, ['etl_recent_runs', 'audit_recent_events'], true)) {
            $monitoring = $this->getMonitoringData();
            $rows = $key === 'etl_recent_runs' ? ($monitoring['recent_runs'] ?? []) : ($monitoring['audit_recent'] ?? []);
            return [
                'title' => $key,
                'columns' => $this->guessColumnsFromRows($rows),
                'rows' => $rows,
            ];
        }

        $detail = $this->getPhase2Data($filters);
        $tables = $detail['tables'] ?? [];
        $claimTables = $detail['claim']['tables'] ?? [];
        $rows = $tables[$key] ?? $claimTables[$key] ?? [];
        $columns = $this->guessColumnsFromRows($rows);

        return [
            'title' => $key,
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    public function getMonitoringData(): array
    {
        $latest = $this->queryRow("
            SELECT
                job_name,
                status,
                started_at,
                finished_at,
                rows_affected,
                message,
                TIMESTAMPDIFF(SECOND, started_at, COALESCE(finished_at, NOW())) AS duration_seconds
            FROM etl_job_log
            ORDER BY etl_job_log_id DESC
            LIMIT 1
        ");

        $failCount24h = (int) $this->queryValue("
            SELECT COUNT(*)
            FROM etl_job_log
            WHERE status <> 'SUCCESS'
              AND started_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
        ", 0);

        $runningCount = (int) $this->queryValue("
            SELECT COUNT(*)
            FROM etl_job_log
            WHERE status = 'RUNNING'
        ", 0);

        $recentRuns = $this->queryAll("
            SELECT
                etl_job_log_id,
                job_name,
                status,
                DATE_FORMAT(started_at, '%Y-%m-%d %H:%i:%s') AS started_at,
                DATE_FORMAT(finished_at, '%Y-%m-%d %H:%i:%s') AS finished_at,
                TIMESTAMPDIFF(SECOND, started_at, COALESCE(finished_at, NOW())) AS duration_seconds,
                rows_affected,
                message
            FROM etl_job_log
            ORDER BY etl_job_log_id DESC
            LIMIT 20
        ");

        $auditRows = [];
        if ($this->tableExists('web_audit_log')) {
            $auditRows = $this->queryAll("
                SELECT
                    web_audit_log_id,
                    action,
                    status,
                    user_ip,
                    request_method,
                    request_uri,
                    message,
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at
                FROM web_audit_log
                ORDER BY web_audit_log_id DESC
                LIMIT 20
            ");
        }

        $latestStatus = (string) ($latest['status'] ?? 'UNKNOWN');

        return [
            'overview' => [
                'latest_job_name' => (string) ($latest['job_name'] ?? '-'),
                'latest_status' => $latestStatus,
                'latest_started_at' => (string) ($latest['started_at'] ?? '-'),
                'latest_finished_at' => (string) ($latest['finished_at'] ?? '-'),
                'latest_duration_seconds' => (int) ($latest['duration_seconds'] ?? 0),
                'running_count' => $runningCount,
                'failed_count_24h' => $failCount24h,
                'last_message' => (string) ($latest['message'] ?? ''),
                'alert_active' => $latestStatus !== 'SUCCESS' || $failCount24h > 0,
            ],
            'recent_runs' => $recentRuns,
            'audit_recent' => $auditRows,
        ];
    }

    private function fetchServiceSummary(array $filters): array
    {
        $params = [];
        $where = $this->buildVisitBaseConditions($filters, $params, 'fs');

        $sql = "
            SELECT
                COUNT(DISTINCT fs.vn) AS total_service_count,
                COUNT(DISTINCT CASE WHEN fs.patient_type = 'OPD' THEN fs.hn END) AS opd_patients,
                COUNT(DISTINCT CASE WHEN fs.patient_type = 'ER' THEN fs.vn END) AS er_patients,
                COUNT(DISTINCT CASE WHEN fs.is_identity_verified = 1 THEN fs.vn END) AS identity_verified
            FROM fact_visit_service fs
            WHERE {$where}
        ";

        $row = $this->preparedRow($sql, $params);

        $appointmentSql = "
            SELECT
                COALESCE(SUM(fd.appointment_attended_hn), 0) AS appointment_attended_count,
                COALESCE(SUM(fd.appointment_missed_hn), 0) AS appointment_missed_count
            FROM fact_dashboard_daily fd
            WHERE fd.service_date BETWEEN :start_date AND :end_date
        ";
        $appointments = $this->preparedRow($appointmentSql, [
            'start_date' => (string) ($filters['start_date'] ?? date('Y-m-d')),
            'end_date' => (string) ($filters['end_date'] ?? date('Y-m-d')),
        ]);

        return [
            'total_service_count' => (int) ($row['total_service_count'] ?? 0),
            'opd_patients' => (int) ($row['opd_patients'] ?? 0),
            'er_patients' => (int) ($row['er_patients'] ?? 0),
            'appointment_attended_count' => (int) ($appointments['appointment_attended_count'] ?? 0),
            'appointment_missed_count' => (int) ($appointments['appointment_missed_count'] ?? 0),
            'identity_verified' => (int) ($row['identity_verified'] ?? 0),
        ];
    }

    private function fetchIpdDischargeSummary(array $filters): array
    {
        $conditions = [
            'ip.dchdate BETWEEN :start_date AND :end_date',
            'ip.dchdate IS NOT NULL',
        ];

        $params = [
            'start_date' => (string) ($filters['start_date'] ?? date('Y-m-d')),
            'end_date' => (string) ($filters['end_date'] ?? date('Y-m-d')),
        ];

        $rights = trim((string) ($filters['rights'] ?? ''));
        if ($rights !== '') {
            $conditions[] = "COALESCE(rg.rights_group, 'OTHERS') = :rights_group";
            $params['rights_group'] = $rights;
        }

        $where = implode("\n AND ", $conditions);

        $sql = "
            SELECT
                COUNT(DISTINCT ip.an) AS ipd_discharged_patients,
                COALESCE(SUM(COALESCE(ip.ot, 0)), 0) AS ipd_ot_sum,
                COALESCE(AVG(COALESCE(ip.rw, 0)), 0) AS ipd_avg_rw,

                SUM(
                    CASE
                        WHEN NOT (
                            UPPER(TRIM(COALESCE(pdx.pdx_icd10, ''))) REGEXP '^O8[0-4]'
                            OR (
                                UPPER(TRIM(COALESCE(pdx.pdx_icd10, ''))) REGEXP '^Z38'
                                AND COALESCE(ip.adjrw, 0) < 0.5
                            )
                        )
                        THEN 1 ELSE 0
                    END
                ) AS ipd_total_adjrw_cases,

                COALESCE(SUM(
                    CASE
                        WHEN NOT (
                            UPPER(TRIM(COALESCE(pdx.pdx_icd10, ''))) REGEXP '^O8[0-4]'
                            OR (
                                UPPER(TRIM(COALESCE(pdx.pdx_icd10, ''))) REGEXP '^Z38'
                                AND COALESCE(ip.adjrw, 0) < 0.5
                            )
                        )
                        THEN COALESCE(ip.adjrw, 0)
                        ELSE 0
                    END
                ), 0) AS ipd_sum_adjrw,

                CASE
                    WHEN COUNT(DISTINCT ip.an) > 0 THEN
                        COALESCE(SUM(
                            CASE
                                WHEN NOT (
                                    UPPER(TRIM(COALESCE(pdx.pdx_icd10, ''))) REGEXP '^O8[0-4]'
                                    OR (
                                        UPPER(TRIM(COALESCE(pdx.pdx_icd10, ''))) REGEXP '^Z38'
                                        AND COALESCE(ip.adjrw, 0) < 0.5
                                    )
                                )
                                THEN COALESCE(ip.adjrw, 0)
                                ELSE 0
                            END
                        ), 0) / COUNT(DISTINCT ip.an)
                    ELSE 0
                END AS ipd_cmi

            FROM hosxpv4.ipt ip
            LEFT JOIN (
                SELECT
                    d.an,
                    COALESCE(
                        MAX(CASE WHEN TRIM(COALESCE(d.diagtype, '')) = '1' THEN d.icd10 END),
                        MAX(d.icd10)
                    ) AS pdx_icd10
                FROM hosxpv4.iptdiag d
                GROUP BY d.an
            ) pdx ON pdx.an = ip.an
            LEFT JOIN (
                SELECT an, MAX(rights_group) AS rights_group
                FROM fact_ipd_stay
                GROUP BY an
            ) rg ON rg.an = ip.an
            WHERE {$where}
        ";

        $row = $this->preparedRow($sql, $params);

        return [
            'ipd_discharged_patients' => (int) ($row['ipd_discharged_patients'] ?? 0),
            'ipd_ot_sum' => (float) ($row['ipd_ot_sum'] ?? 0),
            'ipd_avg_rw' => (float) ($row['ipd_avg_rw'] ?? 0),
            'ipd_total_adjrw_cases' => (int) ($row['ipd_total_adjrw_cases'] ?? 0),
            'ipd_sum_adjrw' => (float) ($row['ipd_sum_adjrw'] ?? 0),
            'ipd_cmi' => (float) ($row['ipd_cmi'] ?? 0),
        ];
    }                           

    private function fetchTodaySummary(): array
    {
        $sql = "
            SELECT
                fd.total_service_count,
                fd.opd_all_hn,
                fd.er_visit_vn,
                fd.ipd_admit_today_an,
                fd.appointment_attended_hn,
                fd.identity_verified_vn
            FROM fact_dashboard_daily fd
            WHERE fd.service_date = CURDATE()
            LIMIT 1
        ";

        $row = $this->queryRow($sql);

        return [
            'total_service_today' => (int) ($row['total_service_count'] ?? 0),
            'opd_today' => (int) ($row['opd_all_hn'] ?? 0),
            'er_today' => (int) ($row['er_visit_vn'] ?? 0),
            'ipd_new_today' => (int) ($row['ipd_admit_today_an'] ?? 0),
            'appointment_attended_today' => (int) ($row['appointment_attended_hn'] ?? 0),
            'identity_verified_today' => (int) ($row['identity_verified_vn'] ?? 0),
        ];
    }

    private function fetchReferoutSummary(array $filters): array
    {
        $sql = "
            SELECT COALESCE(SUM(fd.referout_vn), 0) AS referout_total
            FROM fact_dashboard_daily fd
            WHERE fd.service_date BETWEEN :start_date AND :end_date
        ";

        $row = $this->preparedRow($sql, [
            'start_date' => (string) ($filters['start_date'] ?? date('Y-m-d')),
            'end_date' => (string) ($filters['end_date'] ?? date('Y-m-d')),
        ]);

        return [
            'referout_total' => (int) ($row['referout_total'] ?? 0),
        ];
    }

    private function getMonthlyOpdChart(array $filters): array
    {
        $params = [];
        $where = $this->buildFixedVisitConditions($filters, $params, 'fs', 'OPD', true, true, 'visit_date');

        $sql = "
            SELECT DATE_FORMAT(fs.visit_date, '%Y-%m') AS month_label, COUNT(DISTINCT fs.vn) AS total_count
            FROM fact_visit_service fs
            WHERE {$where}
            GROUP BY DATE_FORMAT(fs.visit_date, '%Y-%m')
            ORDER BY month_label ASC
        ";

        $rows = $this->preparedAll($sql, $params);

        return [
            'categories' => array_map(static fn(array $row): string => (string) $row['month_label'], $rows),
            'series' => [[
                'name' => 'OPD',
                'data' => array_map(static fn(array $row): int => (int) $row['total_count'], $rows),
            ]],
        ];
    }

    private function getMonthlyIpdChart(array $filters): array
    {
        $params = [];
        $where = $this->buildFixedVisitConditions($filters, $params, 'fs', 'IPD', false, true, 'admit_date');

        $sql = "
            SELECT DATE_FORMAT(fs.admit_date, '%Y-%m') AS month_label, COUNT(DISTINCT fs.an) AS total_count
            FROM fact_visit_service fs
            WHERE {$where}
            GROUP BY DATE_FORMAT(fs.admit_date, '%Y-%m')
            ORDER BY month_label ASC
        ";

        $rows = $this->preparedAll($sql, $params);

        return [
            'categories' => array_map(static fn(array $row): string => (string) $row['month_label'], $rows),
            'series' => [[
                'name' => 'IPD',
                'data' => array_map(static fn(array $row): int => (int) $row['total_count'], $rows),
            ]],
        ];
    }

    private function getRightsMonthlyServiceChart(array $filters): array
    {
        $params = [];
        $where = $this->buildVisitBaseConditions($filters, $params, 'fs');

        $sql = "
            SELECT DATE_FORMAT(fs.service_date, '%Y-%m') AS month_label, fs.rights_group, COUNT(DISTINCT fs.vn) AS total_count
            FROM fact_visit_service fs
            WHERE {$where}
            GROUP BY DATE_FORMAT(fs.service_date, '%Y-%m'), fs.rights_group
            ORDER BY month_label ASC, fs.rights_group ASC
        ";

        $rows = $this->preparedAll($sql, $params);

        $categories = [];
        $rightsSeries = [];

        foreach ($rows as $row) {
            $month = (string) $row['month_label'];
            $rights = (string) ($row['rights_group'] ?: 'OTHERS');
            $count = (int) $row['total_count'];

            if (!in_array($month, $categories, true)) {
                $categories[] = $month;
            }
            $rightsSeries[$rights][$month] = $count;
        }

        $series = [];
        foreach ($rightsSeries as $rights => $valuesByMonth) {
            $series[] = [
                'name' => $rights,
                'data' => array_map(static fn(string $month): int => (int) ($valuesByMonth[$month] ?? 0), $categories),
            ];
        }

        return [
            'categories' => $categories,
            'series' => $series,
        ];
    }

    private function getRightsInAreaDistribution(array $filters): array
    {
        $params = [];
        $where = $this->buildVisitBaseConditions($filters, $params, 'fs');

        $sql = "
            SELECT COALESCE(fs.rights_group, 'OTHERS') AS rights_group, COUNT(DISTINCT fs.hn) AS total_patients
            FROM fact_visit_service fs
            INNER JOIN (
                SELECT pr.patient_hn,
                       MAX(pr.house_regist_type_id) AS house_regist_type_id,
                       MAX(COALESCE(pr.death, 'N')) AS death,
                       MAX(pr.person_discharge_id) AS person_discharge_id
                FROM hosxpv4.person pr
                GROUP BY pr.patient_hn
            ) p
                ON p.patient_hn = fs.hn
            WHERE {$where}
              AND p.house_regist_type_id IN (1, 3)
              AND COALESCE(p.death, 'N') <> 'Y'
              AND COALESCE(p.person_discharge_id, '') = '9'
            GROUP BY COALESCE(fs.rights_group, 'OTHERS')
            ORDER BY total_patients DESC, rights_group ASC
        ";

        $rows = $this->preparedAll($sql, $params);

        return array_map(static function (array $row): array {
            return [
                'name' => (string) $row['rights_group'],
                'y' => (int) $row['total_patients'],
            ];
        }, $rows);
    }

    private function getVillagePopulationSummary(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $sql = "
            SELECT
                v.village_moo,
                v.village_name,
                (COALESCE(v.typearea_1, 0) + COALESCE(v.typearea_2, 0) + COALESCE(v.typearea_3, 0)) AS total_population,
                COALESCE(v.typearea_1_3, (COALESCE(v.typearea_1, 0) + COALESCE(v.typearea_3, 0))) AS in_area_population
            FROM vw_village_typearea_pivot v
            ORDER BY CAST(COALESCE(NULLIF(v.village_moo, ''), '0') AS UNSIGNED), v.village_name
            LIMIT {$limit}
        ";

        return $this->queryAll($sql);
    }

    private function getOpdDiseaseTop10(array $filters): array
    {
        return $this->getDiseaseTop10($filters, 'OPD', false, true, true);
    }

    private function getIpdDiseaseTop10(array $filters): array
    {
        return $this->getDiseaseTop10($filters, 'IPD', false, false, true);
    }

    private function getChronicOpdDiseaseTop10(array $filters): array
    {
        return $this->getDiseaseTop10($filters, 'OPD', true, true, true);
    }

    private function getDiseaseTop10(array $filters, string $fixedPatientType, bool $chronicOnly, bool $allowClinicFilter, bool $allowRightsFilter): array
    {
        $params = [];
        $where = $this->buildDiseaseConditions($filters, $params, $fixedPatientType, $chronicOnly, $allowClinicFilter, $allowRightsFilter);

        $totalSql = "SELECT COUNT(*) FROM fact_visit_diag fd WHERE {$where}";
        $totalRows = (int) $this->preparedValue($totalSql, $params, 0);

        $sql = "
            SELECT
                fd.icd10,
                COALESCE(fd.icd_name, '') AS icd_name,
                COUNT(*) AS total_count
            FROM fact_visit_diag fd
            WHERE {$where}
            GROUP BY fd.icd10, COALESCE(fd.icd_name, '')
            ORDER BY total_count DESC, fd.icd10 ASC
            LIMIT 10
        ";

        $rows = $this->preparedAll($sql, $params);

        return array_map(static function (array $row) use ($totalRows): array {
            $count = (int) $row['total_count'];
            return [
                'icd10' => (string) $row['icd10'],
                'icd_name' => (string) $row['icd_name'],
                'total_count' => $count,
                'percent' => $totalRows > 0 ? round(($count / $totalRows) * 100, 2) : 0.0,
            ];
        }, $rows);
    }

    private function getClinicChargesTop10(array $filters): array
    {
        $params = [];
        $where = $this->buildFixedMultiVisitConditions($filters, $params, 'fs', ['OPD', 'ER'], true, true, 'service_date');

        $sql = "
            SELECT
                COALESCE(fs.department_name, fs.main_dep, '-') AS clinic_name,
                COALESCE(SUM(COALESCE(vs.income, 0)), 0) AS total_income,
                COALESCE(SUM(COALESCE(vs.paid_money, 0)), 0) AS total_paid_money,
                COALESCE(SUM(COALESCE(vs.income, 0) - COALESCE(vs.paid_money, 0)), 0) AS total_diff
            FROM fact_visit_service fs
            INNER JOIN hosxpv4.vn_stat vs
                ON vs.vn = fs.vn
            WHERE {$where}
            GROUP BY COALESCE(fs.department_name, fs.main_dep, '-')
            ORDER BY total_income DESC, clinic_name ASC
            LIMIT 10
        ";

        return $this->preparedAll($sql, $params);
    }

    private function getClaimAnalytics(array $filters): array
    {
        return [
            'note' => 'ข้อมูลเคลมแสดงแบบสรุปเท่านั้น ไม่แสดงข้อมูลรายบุคคล และตัวกรองเคลมรอบนี้อิงช่วงวันที่ร่วมกับกลุ่ม admit/non-admit จากตัวกรองประเภทบริการ',
            'cards' => $this->getClaimCards($filters),
            'charts' => [
                'claim_monthly_summary' => $this->getClaimMonthlyChart($filters),
                'claim_burden_comparison' => $this->getClaimBurdenChart($filters),
            ],
            'tables' => [
                'claim_status_summary' => $this->getClaimStatusSummary($filters),
                'claim_monthly_summary' => $this->getClaimMonthlySummary($filters),
                'claim_settled_finance' => $this->getClaimSettledFinance($filters),
            ],
        ];
    }

    private function getClaimCards(array $filters): array
    {
        if (!$this->tableExists('sp_claim_audit_3y')) {
            return [
                'total_visit' => 0,
                'total_charge' => 0.0,
                'patient_paid_total' => 0.0,
                'sent_claim_amount' => 0.0,
                'wait_pay_claim_amount' => 0.0,
                'settled_claim_amount' => 0.0,
                'unclaimed_service_amount' => 0.0,
                'review_claim_amount' => 0.0,
                'no_claim_record_amount' => 0.0,
                'pending_claim_burden' => 0.0,
                'settled_total_charge' => 0.0,
                'settled_claim_received' => 0.0,
                'settled_balance_after_claim' => 0.0,
            ];
        }

        $params = [];
        $where = $this->buildClaimConditions($filters, $params, 'c');
        $sql = "
            SELECT
                COUNT(*) AS total_visit,
                COALESCE(SUM(c.total_charge), 0) AS total_charge,
                COALESCE(SUM(c.patient_paid_total), 0) AS patient_paid_total,
                COALESCE(SUM(CASE WHEN c.claim_status_code = 'approved' THEN c.sent_claim_amount ELSE 0 END), 0) AS sent_claim_amount,
                COALESCE(SUM(CASE WHEN c.claim_status_code = 'cut_off_batch' THEN c.wait_pay_claim_amount ELSE 0 END), 0) AS wait_pay_claim_amount,
                COALESCE(SUM(CASE WHEN c.claim_status_code = 'settled' THEN c.settled_claim_amount ELSE 0 END), 0) AS settled_claim_amount,
                COALESCE(SUM(CASE WHEN c.claim_status_code = 'unclaimed' THEN c.unclaimed_service_amount ELSE 0 END), 0) AS unclaimed_service_amount,
                COALESCE(SUM(CASE WHEN c.claim_status_code = 'received' THEN c.other_review_amount ELSE 0 END), 0) AS review_claim_amount,
                COALESCE(SUM(CASE WHEN c.claim_status_code = 'no_claim_record' THEN c.no_claim_record_amount ELSE 0 END), 0) AS no_claim_record_amount,
                COALESCE(SUM(CASE WHEN c.claim_status_code IN ('approved', 'cut_off_batch', 'received') THEN c.hospital_burden_before_claim ELSE 0 END), 0) AS pending_claim_burden,
                COALESCE(SUM(CASE WHEN c.claim_status_code = 'settled' THEN c.total_charge ELSE 0 END), 0) AS settled_total_charge,
                COALESCE(SUM(CASE WHEN c.claim_status_code = 'settled' THEN c.claim_amount ELSE 0 END), 0) AS settled_claim_received,
                COALESCE(SUM(CASE WHEN c.claim_status_code = 'settled' THEN c.hospital_burden_after_claim ELSE 0 END), 0) AS settled_balance_after_claim
            FROM sp_claim_audit_3y c
            WHERE {$where}
        ";

        $row = $this->preparedRow($sql, $params);

        return [
            'total_visit' => (int) ($row['total_visit'] ?? 0),
            'total_charge' => (float) ($row['total_charge'] ?? 0),
            'patient_paid_total' => (float) ($row['patient_paid_total'] ?? 0),
            'sent_claim_amount' => (float) ($row['sent_claim_amount'] ?? 0),
            'wait_pay_claim_amount' => (float) ($row['wait_pay_claim_amount'] ?? 0),
            'settled_claim_amount' => (float) ($row['settled_claim_amount'] ?? 0),
            'unclaimed_service_amount' => (float) ($row['unclaimed_service_amount'] ?? 0),
            'review_claim_amount' => (float) ($row['review_claim_amount'] ?? 0),
            'no_claim_record_amount' => (float) ($row['no_claim_record_amount'] ?? 0),
            'pending_claim_burden' => (float) ($row['pending_claim_burden'] ?? 0),
            'settled_total_charge' => (float) ($row['settled_total_charge'] ?? 0),
            'settled_claim_received' => (float) ($row['settled_claim_received'] ?? 0),
            'settled_balance_after_claim' => (float) ($row['settled_balance_after_claim'] ?? 0),
        ];
    }

    private function getClaimMonthlySummary(array $filters): array
    {
        if (!$this->tableExists('sp_claim_audit_3y')) {
            return [];
        }

        $params = [];
        $where = $this->buildClaimConditions($filters, $params, 'c');
        $sql = "
            SELECT
                c.month_key,
                COUNT(*) AS total_visit,
                COALESCE(SUM(c.total_charge), 0) AS total_charge,
                COALESCE(SUM(c.patient_paid_total), 0) AS patient_paid_total,
                COALESCE(SUM(c.sent_claim_amount), 0) AS sent_claim_amount,
                COALESCE(SUM(c.wait_pay_claim_amount), 0) AS wait_pay_claim_amount,
                COALESCE(SUM(c.settled_claim_amount), 0) AS settled_claim_amount,
                COALESCE(SUM(c.unclaimed_service_amount), 0) AS unclaimed_service_amount,
                COALESCE(SUM(c.other_review_amount), 0) AS review_claim_amount,
                COALESCE(SUM(c.no_claim_record_amount), 0) AS no_claim_record_amount,
                COALESCE(SUM(c.hospital_burden_before_claim), 0) AS hospital_burden_before_claim,
                COALESCE(SUM(c.hospital_burden_after_claim), 0) AS hospital_burden_after_claim
            FROM sp_claim_audit_3y c
            WHERE {$where}
            GROUP BY c.month_key
            ORDER BY c.month_key
        ";

        return $this->preparedAll($sql, $params);
    }

    private function getClaimStatusSummary(array $filters): array
    {
        if (!$this->tableExists('sp_claim_audit_3y')) {
            return [];
        }

        $params = [];
        $where = $this->buildClaimConditions($filters, $params, 'c');
        $sql = "
            SELECT
                c.claim_status_code,
                c.claim_status_group,
                COUNT(*) AS visit_count,
                COALESCE(SUM(c.total_charge), 0) AS total_charge,
                COALESCE(SUM(c.patient_paid_total), 0) AS patient_paid_total,
                COALESCE(SUM(c.claim_amount), 0) AS claim_amount,
                COALESCE(SUM(c.hospital_burden_before_claim), 0) AS hospital_burden_before_claim,
                COALESCE(SUM(c.hospital_burden_after_claim), 0) AS hospital_burden_after_claim
            FROM sp_claim_audit_3y c
            WHERE {$where}
            GROUP BY c.claim_status_group
            ORDER BY visit_count DESC, c.claim_status_code ASC
        ";

        return $this->preparedAll($sql, $params);
    }

    private function getClaimSettledFinance(array $filters): array
    {
        if (!$this->tableExists('sp_claim_audit_3y')) {
            return [];
        }

        $params = [];
        $where = $this->buildClaimConditions($filters, $params, 'c');
        $sql = "
            SELECT
                c.month_key,
                COUNT(*) AS settled_visit_count,
                COALESCE(SUM(c.total_charge), 0) AS settled_total_charge,
                COALESCE(SUM(c.patient_paid_total), 0) AS settled_patient_paid_total,
                COALESCE(SUM(c.claim_amount), 0) AS settled_claim_received,
                COALESCE(SUM(c.hospital_burden_before_claim), 0) AS settled_hospital_burden_before_claim,
                COALESCE(SUM(c.hospital_burden_after_claim), 0) AS settled_balance_after_claim
            FROM sp_claim_audit_3y c
            WHERE {$where}
              AND c.claim_status_code = 'settled'
            GROUP BY c.month_key
            ORDER BY c.month_key
        ";

        return $this->preparedAll($sql, $params);
    }

    private function getClaimMonthlyChart(array $filters): array
    {
        $rows = $this->getClaimMonthlySummary($filters);
        return [
            'categories' => array_map(static fn(array $row): string => (string) ($row['month_key'] ?? ''), $rows),
            'series' => [
                ['name' => 'ค่าใช้จ่ายรวม', 'data' => array_map(static fn(array $row): float => (float) ($row['total_charge'] ?? 0), $rows)],
                ['name' => 'รายได้เงินสด', 'data' => array_map(static fn(array $row): float => (float) ($row['patient_paid_total'] ?? 0), $rows)],
                ['name' => 'โอนแล้ว', 'data' => array_map(static fn(array $row): float => (float) ($row['settled_claim_amount'] ?? 0), $rows)],
                ['name' => 'รอจ่าย', 'data' => array_map(static fn(array $row): float => (float) ($row['wait_pay_claim_amount'] ?? 0), $rows)],
            ],
        ];
    }

    private function getClaimBurdenChart(array $filters): array
    {
        $rows = $this->getClaimMonthlySummary($filters);
        return [
            'categories' => array_map(static fn(array $row): string => (string) ($row['month_key'] ?? ''), $rows),
            'series' => [
                ['name' => 'ภาระก่อนหักเคลม', 'data' => array_map(static fn(array $row): float => (float) ($row['hospital_burden_before_claim'] ?? 0), $rows)],
                ['name' => 'ภาระหลังหักเคลม', 'data' => array_map(static fn(array $row): float => (float) ($row['hospital_burden_after_claim'] ?? 0), $rows)],
                ['name' => 'ไม่ประสงค์เบิก', 'data' => array_map(static fn(array $row): float => (float) ($row['unclaimed_service_amount'] ?? 0), $rows)],
                ['name' => 'ยังไม่มีข้อมูลเคลม', 'data' => array_map(static fn(array $row): float => (float) ($row['no_claim_record_amount'] ?? 0), $rows)],
            ],
        ];
    }

    private function getAuthClinicTop10(array $filters): array
    {
        $params = [];
        $where = $this->buildFixedMultiVisitConditions($filters, $params, 'fs', ['OPD', 'ER'], true, true, 'service_date');

        $sql = "
            SELECT
                COALESCE(fs.department_name, fs.main_dep, '-') AS clinic_name,
                COUNT(DISTINCT CASE WHEN fs.is_identity_verified = 1 THEN fs.vn END) AS verified_count,
                COUNT(DISTINCT CASE WHEN fs.is_identity_not_verified = 1 THEN fs.vn END) AS not_verified_count
            FROM fact_visit_service fs
            WHERE {$where}
            GROUP BY COALESCE(fs.department_name, fs.main_dep, '-')
            ORDER BY verified_count DESC, clinic_name ASC
            LIMIT 10
        ";

        return $this->preparedAll($sql, $params);
    }

    private function fetchLastUpdated(): string
    {
        $sql = "
            SELECT DATE_FORMAT(MAX(t.last_refreshed_at), '%Y-%m-%d %H:%i:%s')
            FROM (
                SELECT MAX(fs.refreshed_at) AS last_refreshed_at FROM fact_visit_service fs
                UNION ALL
                SELECT MAX(fd.last_refreshed_at) AS last_refreshed_at FROM fact_dashboard_daily fd
                UNION ALL
                SELECT MAX(fdiag.refreshed_at) AS last_refreshed_at FROM fact_visit_diag fdiag
                UNION ALL
                SELECT MAX(fipd.refreshed_at) AS last_refreshed_at FROM fact_ipd_stay fipd
                UNION ALL
                SELECT MAX(ps.last_refreshed_at) AS last_refreshed_at FROM fact_population_snapshot ps
            ) t
        ";

        $value = $this->queryValue($sql);

        return is_string($value) && $value !== '' ? $value : date('Y-m-d H:i:s');
    }

    private function buildVisitBaseConditions(array $filters, array &$params, string $alias = 'fs', string $dateColumn = 'service_date'): string
    {
        $conditions = [
            "{$alias}.is_cancelled = 0",
            "{$alias}.is_test_patient = 0",
            "{$alias}.{$dateColumn} BETWEEN :start_date AND :end_date",
        ];

        $params['start_date'] = (string) ($filters['start_date'] ?? date('Y-m-d'));
        $params['end_date'] = (string) ($filters['end_date'] ?? date('Y-m-d'));

        $clinic = trim((string) ($filters['clinic'] ?? ''));
        $rights = trim((string) ($filters['rights'] ?? ''));
        $patientType = trim((string) ($filters['patient_type'] ?? ''));

        if ($clinic !== '') {
            $conditions[] = "{$alias}.main_dep = :clinic";
            $params['clinic'] = $clinic;
        }

        if ($rights !== '') {
            $conditions[] = "{$alias}.rights_group = :rights_group";
            $params['rights_group'] = $rights;
        }

        if ($patientType !== '') {
            $conditions[] = "{$alias}.patient_type = :patient_type";
            $params['patient_type'] = $patientType;
        }

        return implode("
 AND ", $conditions);
    }

    private function buildFixedVisitConditions(
        array $filters,
        array &$params,
        string $alias,
        string $fixedPatientType,
        bool $allowClinicFilter,
        bool $allowRightsFilter,
        string $dateColumn
    ): string {
        $conditions = [
            "{$alias}.is_cancelled = 0",
            "{$alias}.is_test_patient = 0",
            "{$alias}.{$dateColumn} BETWEEN :start_date AND :end_date",
            "{$alias}.patient_type = :fixed_patient_type",
        ];

        $params['start_date'] = (string) ($filters['start_date'] ?? date('Y-m-d'));
        $params['end_date'] = (string) ($filters['end_date'] ?? date('Y-m-d'));
        $params['fixed_patient_type'] = $fixedPatientType;

        if ($allowClinicFilter) {
            $clinic = trim((string) ($filters['clinic'] ?? ''));
            if ($clinic !== '') {
                $conditions[] = "{$alias}.main_dep = :clinic";
                $params['clinic'] = $clinic;
            }
        }

        if ($allowRightsFilter) {
            $rights = trim((string) ($filters['rights'] ?? ''));
            if ($rights !== '') {
                $conditions[] = "{$alias}.rights_group = :rights_group";
                $params['rights_group'] = $rights;
            }
        }

        return implode("
 AND ", $conditions);
    }

    private function buildFixedMultiVisitConditions(
        array $filters,
        array &$params,
        string $alias,
        array $patientTypes,
        bool $allowClinicFilter,
        bool $allowRightsFilter,
        string $dateColumn
    ): string {
        $conditions = [
            "{$alias}.is_cancelled = 0",
            "{$alias}.is_test_patient = 0",
            "{$alias}.{$dateColumn} BETWEEN :start_date AND :end_date",
        ];

        $params['start_date'] = (string) ($filters['start_date'] ?? date('Y-m-d'));
        $params['end_date'] = (string) ($filters['end_date'] ?? date('Y-m-d'));

        $typePlaceholders = [];
        foreach (array_values($patientTypes) as $index => $patientType) {
            $key = 'fixed_patient_type_' . $index;
            $typePlaceholders[] = ':' . $key;
            $params[$key] = $patientType;
        }
        $conditions[] = "{$alias}.patient_type IN (" . implode(', ', $typePlaceholders) . ')';

        if ($allowClinicFilter) {
            $clinic = trim((string) ($filters['clinic'] ?? ''));
            if ($clinic !== '') {
                $conditions[] = "{$alias}.main_dep = :clinic";
                $params['clinic'] = $clinic;
            }
        }

        if ($allowRightsFilter) {
            $rights = trim((string) ($filters['rights'] ?? ''));
            if ($rights !== '') {
                $conditions[] = "{$alias}.rights_group = :rights_group";
                $params['rights_group'] = $rights;
            }
        }

        return implode("
 AND ", $conditions);
    }

    private function buildDiseaseConditions(
        array $filters,
        array &$params,
        string $fixedPatientType,
        bool $chronicOnly,
        bool $allowClinicFilter,
        bool $allowRightsFilter
    ): string {
        $conditions = [
            'fd.service_date BETWEEN :start_date AND :end_date',
            'fd.patient_type = :fixed_patient_type',
            "TRIM(COALESCE(fd.icd10, '')) <> ''",
            'fd.icd10 NOT LIKE :exclude_z',
        ];

        $params['start_date'] = (string) ($filters['start_date'] ?? date('Y-m-d'));
        $params['end_date'] = (string) ($filters['end_date'] ?? date('Y-m-d'));
        $params['fixed_patient_type'] = $fixedPatientType;
        $params['exclude_z'] = 'Z%';

        if ($allowClinicFilter) {
            $clinic = trim((string) ($filters['clinic'] ?? ''));
            if ($clinic !== '') {
                $conditions[] = 'fd.main_dep = :clinic';
                $params['clinic'] = $clinic;
            }
        }

        if ($allowRightsFilter) {
            $rights = trim((string) ($filters['rights'] ?? ''));
            if ($rights !== '') {
                $conditions[] = 'fd.rights_group = :rights_group';
                $params['rights_group'] = $rights;
            }
        }

        if ($chronicOnly) {
            $conditions[] = 'fd.is_chronic_target = 1';
        }

        return implode("
 AND ", $conditions);
    }


    private function buildClaimConditions(array $filters, array &$params, string $alias = 'c'): string
    {
        $conditions = [
            "{$alias}.service_date BETWEEN :claim_start_date AND :claim_end_date",
        ];

        $params['claim_start_date'] = (string) ($filters['start_date'] ?? date('Y-m-d'));
        $params['claim_end_date'] = (string) ($filters['end_date'] ?? date('Y-m-d'));

        $patientType = strtoupper(trim((string) ($filters['patient_type'] ?? '')));
        if ($patientType === 'IPD') {
            $conditions[] = "{$alias}.is_admit = 1";
        } elseif (in_array($patientType, ['OPD', 'ER'], true)) {
            $conditions[] = "{$alias}.is_admit = 0";
        }

        return implode("
 AND ", $conditions);
    }


    private function todayFilters(): array
    {
        $today = date('Y-m-d');
        return [
            'start_date' => $today,
            'end_date' => $today,
            'clinic' => '',
            'rights' => '',
            'patient_type' => '',
        ];
    }

    private function guessColumnsFromRows(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $first = $rows[0];
        if (!is_array($first)) {
            return [];
        }

        $columns = [];
        foreach (array_keys($first) as $key) {
            $columns[] = [
                'key' => (string) $key,
                'label' => ucwords(str_replace('_', ' ', (string) $key)),
            ];
        }

        return $columns;
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->preparedValue('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name', ['table_name' => $tableName], 0) > 0;
    }

    private function drillTotalService(array $filters): array
    {
        $params = [];
        $where = $this->buildVisitBaseConditions($filters, $params, 'fs');
        $sql = "
            SELECT
                fs.service_date,
                fs.vn,
                fs.hn,
                fs.patient_type,
                COALESCE(fs.department_name, fs.main_dep, '-') AS clinic_name,
                fs.rights_group,
                CASE WHEN fs.is_identity_verified = 1 THEN 'Y' ELSE 'N' END AS verified
            FROM fact_visit_service fs
            WHERE {$where}
            ORDER BY fs.service_date DESC, fs.vn DESC
            LIMIT 1000
        ";
        $rows = $this->preparedAll($sql, $params);
        return [
            'title' => 'รายละเอียดรับบริการรวม',
            'columns' => [
                ['key' => 'service_date', 'label' => 'วันที่'],
                ['key' => 'vn', 'label' => 'VN'],
                ['key' => 'hn', 'label' => 'HN'],
                ['key' => 'patient_type', 'label' => 'ประเภท'],
                ['key' => 'clinic_name', 'label' => 'คลินิก'],
                ['key' => 'rights_group', 'label' => 'สิทธิ'],
                ['key' => 'verified', 'label' => 'ยืนยันตัวตน'],
            ],
            'rows' => $rows,
            'summary' => ['limit' => 1000],
        ];
    }

    private function drillOpdPatients(array $filters): array
    {
        $params = [];
        $where = $this->buildFixedVisitConditions($filters, $params, 'fs', 'OPD', true, true, 'service_date');
        $sql = "
            SELECT
                fs.hn,
                MAX(fs.service_date) AS last_service_date,
                COUNT(DISTINCT fs.vn) AS visit_count,
                MAX(COALESCE(fs.department_name, fs.main_dep, '-')) AS clinic_name,
                MAX(fs.rights_group) AS rights_group,
                SUM(CASE WHEN fs.is_identity_verified = 1 THEN 1 ELSE 0 END) AS verified_visits
            FROM fact_visit_service fs
            WHERE {$where}
            GROUP BY fs.hn
            ORDER BY visit_count DESC, last_service_date DESC, fs.hn ASC
            LIMIT 1000
        ";
        $rows = $this->preparedAll($sql, $params);
        return [
            'title' => 'รายละเอียดผู้ป่วย OPD',
            'columns' => [
                ['key' => 'hn', 'label' => 'HN'],
                ['key' => 'last_service_date', 'label' => 'วันที่ล่าสุด'],
                ['key' => 'visit_count', 'label' => 'จำนวนครั้ง'],
                ['key' => 'clinic_name', 'label' => 'คลินิก'],
                ['key' => 'rights_group', 'label' => 'สิทธิ'],
                ['key' => 'verified_visits', 'label' => 'ครั้งที่ยืนยันตัวตน'],
            ],
            'rows' => $rows,
            'summary' => ['limit' => 1000],
        ];
    }

    private function drillErPatients(array $filters): array
    {
        $params = [];
        $where = $this->buildFixedVisitConditions($filters, $params, 'fs', 'ER', true, true, 'service_date');
        $sql = "
            SELECT
                fs.service_date,
                fs.vn,
                fs.hn,
                COALESCE(fs.department_name, fs.main_dep, '-') AS clinic_name,
                fs.rights_group,
                CASE WHEN fs.is_identity_verified = 1 THEN 'Y' ELSE 'N' END AS verified
            FROM fact_visit_service fs
            WHERE {$where}
            ORDER BY fs.service_date DESC, fs.vn DESC
            LIMIT 1000
        ";
        $rows = $this->preparedAll($sql, $params);
        return [
            'title' => 'รายละเอียดผู้ป่วย ER',
            'columns' => [
                ['key' => 'service_date', 'label' => 'วันที่'],
                ['key' => 'vn', 'label' => 'VN'],
                ['key' => 'hn', 'label' => 'HN'],
                ['key' => 'clinic_name', 'label' => 'คลินิก'],
                ['key' => 'rights_group', 'label' => 'สิทธิ'],
                ['key' => 'verified', 'label' => 'ยืนยันตัวตน'],
            ],
            'rows' => $rows,
            'summary' => ['limit' => 1000],
        ];
    }

    private function drillIdentityVerified(array $filters): array
    {
        $params = [];
        $where = $this->buildVisitBaseConditions($filters, $params, 'fs');
        $where .= "
 AND fs.is_identity_verified = 1";
        $sql = "
            SELECT
                fs.service_date,
                fs.vn,
                fs.hn,
                fs.patient_type,
                COALESCE(fs.department_name, fs.main_dep, '-') AS clinic_name,
                fs.rights_group,
                COALESCE(fs.auth_code, '-') AS auth_code
            FROM fact_visit_service fs
            WHERE {$where}
            ORDER BY fs.service_date DESC, fs.vn DESC
            LIMIT 1000
        ";
        $rows = $this->preparedAll($sql, $params);
        return [
            'title' => 'รายละเอียดการยืนยันตัวตน',
            'columns' => [
                ['key' => 'service_date', 'label' => 'วันที่'],
                ['key' => 'vn', 'label' => 'VN'],
                ['key' => 'hn', 'label' => 'HN'],
                ['key' => 'patient_type', 'label' => 'ประเภท'],
                ['key' => 'clinic_name', 'label' => 'คลินิก'],
                ['key' => 'rights_group', 'label' => 'สิทธิ'],
                ['key' => 'auth_code', 'label' => 'Auth code'],
            ],
            'rows' => $rows,
            'summary' => ['limit' => 1000],
        ];
    }

    private function drillAppointmentSummary(array $filters, bool $attended): array
    {
        $sql = "
            SELECT
                fd.service_date,
                fd.appointment_total_hn,
                fd.appointment_attended_hn,
                fd.appointment_missed_hn
            FROM fact_dashboard_daily fd
            WHERE fd.service_date BETWEEN :start_date AND :end_date
            ORDER BY fd.service_date DESC
            LIMIT 1000
        ";
        $rows = $this->preparedAll($sql, [
            'start_date' => (string) ($filters['start_date'] ?? date('Y-m-d')),
            'end_date' => (string) ($filters['end_date'] ?? date('Y-m-d')),
        ]);
        return [
            'title' => $attended ? 'รายละเอียดผู้ป่วยนัดที่มาตามนัด' : 'รายละเอียดผู้ป่วยไม่มาตามนัด',
            'columns' => [
                ['key' => 'service_date', 'label' => 'วันที่'],
                ['key' => 'appointment_total_hn', 'label' => 'นัดทั้งหมด'],
                ['key' => 'appointment_attended_hn', 'label' => 'มาตามนัด'],
                ['key' => 'appointment_missed_hn', 'label' => 'ไม่มาตามนัด'],
            ],
            'rows' => $rows,
            'summary' => ['mode' => $attended ? 'attended' : 'missed'],
        ];
    }

    private function drillIpdDischarged(array $filters, bool $adjrwOnly): array
    {
        $conditions = [
            'ip.dchdate BETWEEN :start_date AND :end_date',
            'ip.dchdate IS NOT NULL',
        ];
        $params = [
            'start_date' => (string) ($filters['start_date'] ?? date('Y-m-d')),
            'end_date' => (string) ($filters['end_date'] ?? date('Y-m-d')),
        ];
        $rights = trim((string) ($filters['rights'] ?? ''));
        if ($rights !== '') {
            $conditions[] = "COALESCE(rg.rights_group, 'OTHERS') = :rights_group";
            $params['rights_group'] = $rights;
        }
        if ($adjrwOnly) {
            $conditions[] = "NOT (
                UPPER(TRIM(COALESCE(pdx.pdx_icd10, ''))) REGEXP '^O8[0-4]'
                OR (UPPER(TRIM(COALESCE(pdx.pdx_icd10, ''))) REGEXP '^Z38' AND COALESCE(ip.adjrw, 0) < 0.5)
            )";
        }
        $where = implode("
 AND ", $conditions);
        $sql = "
            SELECT
                ip.an,
                ip.hn,
                ip.regdate,
                ip.dchdate,
                COALESCE(ip.ot, 0) AS ot,
                COALESCE(ip.rw, 0) AS rw,
                COALESCE(ip.adjrw, 0) AS adjrw,
                COALESCE(rg.rights_group, 'OTHERS') AS rights_group,
                COALESCE(pdx.pdx_icd10, '-') AS pdx_icd10
            FROM hosxpv4.ipt ip
            LEFT JOIN (
                SELECT
                    d.an,
                    COALESCE(
                        MAX(CASE WHEN TRIM(COALESCE(d.diagtype, '')) = '1' THEN d.icd10 END),
                        MAX(d.icd10)
                    ) AS pdx_icd10
                FROM hosxpv4.iptdiag d
                GROUP BY d.an
            ) pdx ON pdx.an = ip.an
            LEFT JOIN (
                SELECT an, MAX(rights_group) AS rights_group
                FROM fact_ipd_stay
                GROUP BY an
            ) rg ON rg.an = ip.an
            WHERE {$where}
            ORDER BY ip.dchdate DESC, ip.an DESC
            LIMIT 1000
        ";
        $rows = $this->preparedAll($sql, $params);
        return [
            'title' => $adjrwOnly ? 'รายละเอียดเคสที่เข้าเกณฑ์ AdjRW' : 'รายละเอียดผู้ป่วยในที่จำหน่ายแล้ว',
            'columns' => [
                ['key' => 'an', 'label' => 'AN'],
                ['key' => 'hn', 'label' => 'HN'],
                ['key' => 'regdate', 'label' => 'วัน admit'],
                ['key' => 'dchdate', 'label' => 'วันจำหน่าย'],
                ['key' => 'ot', 'label' => 'OT'],
                ['key' => 'rw', 'label' => 'RW'],
                ['key' => 'adjrw', 'label' => 'AdjRW'],
                ['key' => 'rights_group', 'label' => 'สิทธิ'],
                ['key' => 'pdx_icd10', 'label' => 'PDx'],
            ],
            'rows' => $rows,
            'summary' => ['limit' => 1000, 'adjrw_only' => $adjrwOnly],
        ];
    }

    private function drillReferoutDaily(array $filters): array
    {
        $sql = "
            SELECT
                fd.service_date,
                fd.referout_vn
            FROM fact_dashboard_daily fd
            WHERE fd.service_date BETWEEN :start_date AND :end_date
            ORDER BY fd.service_date DESC
            LIMIT 1000
        ";
        $rows = $this->preparedAll($sql, [
            'start_date' => (string) ($filters['start_date'] ?? date('Y-m-d')),
            'end_date' => (string) ($filters['end_date'] ?? date('Y-m-d')),
        ]);
        return [
            'title' => 'รายละเอียดการส่งต่อ',
            'columns' => [
                ['key' => 'service_date', 'label' => 'วันที่'],
                ['key' => 'referout_vn', 'label' => 'จำนวนส่งต่อ'],
            ],
            'rows' => $rows,
            'summary' => [],
        ];
    }

    private function drillIpdNewToday(): array
    {
        $today = date('Y-m-d');
        $sql = "
            SELECT
                ip.an,
                ip.hn,
                ip.regdate,
                ip.dchdate,
                COALESCE(ip.ot, 0) AS ot,
                COALESCE(ip.rw, 0) AS rw,
                COALESCE(ip.adjrw, 0) AS adjrw
            FROM hosxpv4.ipt ip
            WHERE ip.regdate = :today
            ORDER BY ip.an DESC
            LIMIT 1000
        ";
        $rows = $this->preparedAll($sql, ['today' => $today]);
        return [
            'title' => 'รายละเอียดผู้ป่วย IPD ใหม่วันนี้',
            'columns' => [
                ['key' => 'an', 'label' => 'AN'],
                ['key' => 'hn', 'label' => 'HN'],
                ['key' => 'regdate', 'label' => 'วัน admit'],
                ['key' => 'dchdate', 'label' => 'วันจำหน่าย'],
                ['key' => 'ot', 'label' => 'OT'],
                ['key' => 'rw', 'label' => 'RW'],
                ['key' => 'adjrw', 'label' => 'AdjRW'],
            ],
            'rows' => $rows,
            'summary' => ['date' => $today],
        ];
    }

}
