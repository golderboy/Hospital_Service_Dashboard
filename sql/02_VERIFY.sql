USE `hos_dashboard`;

SET @start_date = CURDATE() - INTERVAL 30 DAY;
SET @end_date = CURDATE();

/* ---------------------------------------------------------
   1) ETL summary
   --------------------------------------------------------- */
SELECT COUNT(*) AS fact_visit_service_rows FROM `fact_visit_service`;
SELECT COUNT(*) AS fact_visit_diag_rows FROM `fact_visit_diag`;
SELECT COUNT(*) AS fact_ipd_stay_rows FROM `fact_ipd_stay`;
SELECT COUNT(*) AS fact_dashboard_daily_rows FROM `fact_dashboard_daily`;
SELECT COUNT(*) AS fact_population_snapshot_rows FROM `fact_population_snapshot`;
SELECT COUNT(*) AS fact_population_village_typearea_snapshot_rows FROM `fact_population_village_typearea_snapshot`;
SELECT COUNT(*) AS etl_job_log_rows FROM `etl_job_log`;

SELECT * FROM `fact_dashboard_daily` ORDER BY `service_date` DESC LIMIT 7;
SELECT * FROM `fact_ipd_stay` ORDER BY `dchdate` DESC, `an` DESC LIMIT 20;
SELECT * FROM `fact_visit_diag` ORDER BY `fact_visit_diag_id` DESC LIMIT 20;
SELECT * FROM `fact_population_snapshot` ORDER BY `snapshot_date` DESC LIMIT 3;
SELECT * FROM `population_master` ORDER BY `reference_date` DESC, `population_master_id` DESC LIMIT 5;
SELECT * FROM `fact_population_village_typearea_snapshot` ORDER BY `snapshot_date` DESC, `moo`, `typearea` LIMIT 20;
SELECT * FROM `etl_job_log` ORDER BY `etl_job_log_id` DESC LIMIT 10;

/* ---------------------------------------------------------
   2) Manual verify: Top 10 โรค OPD
   - ovst เป็นหลัก
   - ถ้ามี vn ใน ipt ถือเป็น IPD ไม่เอามานับ OPD
   --------------------------------------------------------- */
SELECT
    od.icd10,
    icd.`name` AS icd_name,
    COUNT(*) AS total_count
FROM `hosxp`.`ovst` o
INNER JOIN `hosxp`.`ovstdiag` od
    ON od.vn = o.vn
INNER JOIN `hosxp`.`icd101` icd
    ON icd.code = od.icd10
LEFT JOIN (
    SELECT DISTINCT ip.vn
    FROM `hosxp`.`ipt` ip
    WHERE ip.vn IS NOT NULL
) ipd
    ON ipd.vn = o.vn
WHERE o.vstdate BETWEEN @start_date AND @end_date
  AND ipd.vn IS NULL
  AND TRIM(COALESCE(od.icd10, '')) <> ''
  AND od.icd10 NOT LIKE 'Z%'
GROUP BY od.icd10, icd.`name`
ORDER BY total_count DESC, od.icd10 ASC
LIMIT 10;

SELECT
    fd.icd10,
    fd.icd_name,
    COUNT(*) AS total_count
FROM `fact_visit_diag` fd
WHERE fd.patient_type = 'OPD'
  AND fd.service_date BETWEEN @start_date AND @end_date
GROUP BY fd.icd10, fd.icd_name
ORDER BY total_count DESC, fd.icd10 ASC
LIMIT 10;

/* ---------------------------------------------------------
   3) Manual verify: Top 10 โรค IPD
   - ipt เป็นหลัก
   - ใช้วัน admit (regdate) เป็นตัวกรองตารางโรค IPD
   --------------------------------------------------------- */
SELECT
    idg.icd10,
    icd.`name` AS icd_name,
    COUNT(*) AS total_count
FROM `hosxp`.`ipt` ip
INNER JOIN `hosxp`.`iptdiag` idg
    ON idg.an = ip.an
INNER JOIN `hosxp`.`icd101` icd
    ON icd.code = idg.icd10
WHERE ip.regdate BETWEEN @start_date AND @end_date
  AND TRIM(COALESCE(idg.icd10, '')) <> ''
  AND idg.icd10 NOT LIKE 'Z%'
GROUP BY idg.icd10, icd.`name`
ORDER BY total_count DESC, idg.icd10 ASC
LIMIT 10;

SELECT
    fd.icd10,
    fd.icd_name,
    COUNT(*) AS total_count
FROM `fact_visit_diag` fd
WHERE fd.patient_type = 'IPD'
  AND fd.service_date BETWEEN @start_date AND @end_date
GROUP BY fd.icd10, fd.icd_name
ORDER BY total_count DESC, fd.icd10 ASC
LIMIT 10;

/* ---------------------------------------------------------
   4) Manual verify: Top 10 โรคเรื้อรัง (OPD)
   --------------------------------------------------------- */
SELECT
    od.icd10,
    icd.`name` AS icd_name,
    COUNT(*) AS total_count
FROM `hosxp`.`ovst` o
INNER JOIN `hosxp`.`ovstdiag` od
    ON od.vn = o.vn
INNER JOIN `hosxp`.`icd101` icd
    ON icd.code = od.icd10
LEFT JOIN (
    SELECT DISTINCT ip.vn
    FROM `hosxp`.`ipt` ip
    WHERE ip.vn IS NOT NULL
) ipd
    ON ipd.vn = o.vn
WHERE o.vstdate BETWEEN @start_date AND @end_date
  AND ipd.vn IS NULL
  AND TRIM(COALESCE(od.icd10, '')) <> ''
  AND od.icd10 NOT LIKE 'Z%'
  AND (
      od.icd10 REGEXP '^(E1[0-4])'
      OR od.icd10 REGEXP '^(I1[0-5])'
      OR od.icd10 REGEXP '^(I2[0-5])'
      OR od.icd10 REGEXP '^(J4[1-4])'
      OR od.icd10 REGEXP '^(I6[0-4])'
  )
GROUP BY od.icd10, icd.`name`
ORDER BY total_count DESC, od.icd10 ASC
LIMIT 10;

SELECT
    fd.icd10,
    fd.icd_name,
    COUNT(*) AS total_count
FROM `fact_visit_diag` fd
WHERE fd.patient_type = 'OPD'
  AND fd.is_chronic_target = 1
  AND fd.service_date BETWEEN @start_date AND @end_date
GROUP BY fd.icd10, fd.icd_name
ORDER BY total_count DESC, fd.icd10 ASC
LIMIT 10;

/* ---------------------------------------------------------
   5) Manual verify: รายการผู้ป่วยในจำหน่ายแล้ว
   - ยึด dchdate เป็นตัวกรอง
   --------------------------------------------------------- */
SELECT
    ip.an,
    ip.regdate,
    ip.dchdate,
    ip.ot,
    ip.rw,
    ip.adjrw
FROM `hosxp`.`ipt` ip
WHERE ip.dchdate BETWEEN @start_date AND @end_date
ORDER BY ip.dchdate DESC, ip.an DESC
LIMIT 100;

SELECT
    s.an,
    s.regdate,
    s.dchdate,
    s.ot,
    s.rw,
    s.adjrw
FROM `fact_ipd_stay` s
WHERE s.dchdate BETWEEN @start_date AND @end_date
ORDER BY s.dchdate DESC, s.an DESC
LIMIT 100;

SELECT COUNT(*) AS web_audit_log_rows FROM `web_audit_log`;
