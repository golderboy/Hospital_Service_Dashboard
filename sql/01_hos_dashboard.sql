/*
 Navicat Premium Dump SQL

 Source Server         : SLAVE7
 Source Server Type    : MySQL
 Source Server Version : 110806 (11.8.6-MariaDB-log)
 Source Host           : 192.168.1.7:3306
 Source Schema         : hos_dashboard

 Target Server Type    : MySQL
 Target Server Version : 110806 (11.8.6-MariaDB-log)
 File Encoding         : 65001

 Date: 26/03/2026 14:55:32
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for etl_job_log
-- ----------------------------
DROP TABLE IF EXISTS `etl_job_log`;
CREATE TABLE `etl_job_log`  (
  `etl_job_log_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `started_at` datetime NOT NULL,
  `finished_at` datetime NULL DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'RUNNING',
  `rows_affected` int NOT NULL DEFAULT 0,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  PRIMARY KEY (`etl_job_log_id`) USING BTREE,
  INDEX `idx_etl_job_name`(`job_name` ASC) USING BTREE,
  INDEX `idx_etl_status`(`status` ASC) USING BTREE,
  INDEX `idx_etl_finished_at`(`finished_at` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 82 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for fact_dashboard_daily
-- ----------------------------
DROP TABLE IF EXISTS `fact_dashboard_daily`;
CREATE TABLE `fact_dashboard_daily`  (
  `service_date` date NOT NULL,
  `total_service_count` int NOT NULL DEFAULT 0,
  `opd_all_hn` int NOT NULL DEFAULT 0,
  `opd_doctor_screen_vn` int NOT NULL DEFAULT 0,
  `ipd_an_count` int NOT NULL DEFAULT 0,
  `identity_verified_vn` int NOT NULL DEFAULT 0,
  `identity_not_verified_vn` int NOT NULL DEFAULT 0,
  `total_service_charges_opd` decimal(14, 2) NOT NULL DEFAULT 0.00,
  `appointment_total_hn` int NOT NULL DEFAULT 0,
  `appointment_attended_hn` int NOT NULL DEFAULT 0,
  `appointment_missed_hn` int NOT NULL DEFAULT 0,
  `er_visit_vn` int NOT NULL DEFAULT 0,
  `referout_vn` int NOT NULL DEFAULT 0,
  `ipd_admit_today_an` int NOT NULL DEFAULT 0,
  `ipd_open_an` int NOT NULL DEFAULT 0,
  `last_refreshed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`service_date`) USING BTREE,
  INDEX `idx_fdd_last_refreshed_at`(`last_refreshed_at` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for fact_ipd_stay
-- ----------------------------
DROP TABLE IF EXISTS `fact_ipd_stay`;
CREATE TABLE `fact_ipd_stay`  (
  `an` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `hn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `regdate` date NOT NULL,
  `dchdate` date NOT NULL,
  `ot` decimal(10, 2) NOT NULL DEFAULT 0.00,
  `rw` decimal(12, 4) NOT NULL DEFAULT 0.0000,
  `adjrw` decimal(12, 4) NOT NULL DEFAULT 0.0000,
  `rights_group` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'OTHERS',
  `refreshed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`an`) USING BTREE,
  INDEX `idx_fis_regdate`(`regdate` ASC) USING BTREE,
  INDEX `idx_fis_dchdate`(`dchdate` ASC) USING BTREE,
  INDEX `idx_fis_rights_group`(`rights_group` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for fact_population_snapshot
-- ----------------------------
DROP TABLE IF EXISTS `fact_population_snapshot`;
CREATE TABLE `fact_population_snapshot`  (
  `snapshot_date` date NOT NULL,
  `registry_population_total` int NOT NULL DEFAULT 0,
  `population_in_area` int NOT NULL DEFAULT 0,
  `population_in_area_thai` int NOT NULL DEFAULT 0,
  `population_in_district` int NOT NULL DEFAULT 0,
  `population_in_district_thai` int NOT NULL DEFAULT 0,
  `source_note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `last_refreshed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`snapshot_date`) USING BTREE,
  INDEX `idx_fps_last_refreshed_at`(`last_refreshed_at` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for fact_population_village_typearea_snapshot
-- ----------------------------
DROP TABLE IF EXISTS `fact_population_village_typearea_snapshot`;
CREATE TABLE `fact_population_village_typearea_snapshot`  (
  `snapshot_date` date NOT NULL,
  `village_id` bigint NULL DEFAULT NULL,
  `village_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `village_moo` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `village_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `home` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `moo` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `chwat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `aumpur` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `tumbon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `addr_full` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `typearea` tinyint UNSIGNED NOT NULL,
  `in_area_flag` tinyint(1) NOT NULL DEFAULT 0,
  `in_district_flag` tinyint(1) NOT NULL DEFAULT 0,
  `population_count` int NOT NULL DEFAULT 0,
  `population_thai_count` int NOT NULL DEFAULT 0,
  INDEX `idx_fpvts_snapshot_date`(`snapshot_date` ASC) USING BTREE,
  INDEX `idx_fpvts_moo_typearea`(`moo` ASC, `typearea` ASC) USING BTREE,
  INDEX `idx_fpvts_typearea`(`typearea` ASC) USING BTREE,
  INDEX `idx_fpvts_village_code`(`village_code` ASC) USING BTREE,
  INDEX `idx_fpvts_village_id`(`village_id` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for fact_visit_diag
-- ----------------------------
DROP TABLE IF EXISTS `fact_visit_diag`;
CREATE TABLE `fact_visit_diag`  (
  `fact_visit_diag_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `vn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `an` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `hn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `service_date` date NOT NULL,
  `visit_date` date NULL DEFAULT NULL,
  `admit_date` date NULL DEFAULT NULL,
  `discharge_date` date NULL DEFAULT NULL,
  `main_dep` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `department_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `rights_group` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'OTHERS',
  `patient_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `icd10` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `icd_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `diag_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `is_chronic_target` tinyint(1) NOT NULL DEFAULT 0,
  `refreshed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`fact_visit_diag_id`) USING BTREE,
  INDEX `idx_fvd_service_date`(`service_date` ASC) USING BTREE,
  INDEX `idx_fvd_visit_date`(`visit_date` ASC) USING BTREE,
  INDEX `idx_fvd_icd10`(`icd10` ASC) USING BTREE,
  INDEX `idx_fvd_patient_type`(`patient_type` ASC) USING BTREE,
  INDEX `idx_fvd_main_dep`(`main_dep` ASC) USING BTREE,
  INDEX `idx_fvd_rights_group`(`rights_group` ASC) USING BTREE,
  INDEX `idx_fvd_chronic`(`is_chronic_target` ASC) USING BTREE,
  INDEX `idx_fvd_vn`(`vn` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 636874 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for fact_visit_service
-- ----------------------------
DROP TABLE IF EXISTS `fact_visit_service`;
CREATE TABLE `fact_visit_service`  (
  `vn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `hn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `an` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `visit_date` date NULL DEFAULT NULL,
  `visit_time` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `admit_date` date NULL DEFAULT NULL,
  `service_date` date NOT NULL,
  `main_dep` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `department_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `pttype` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `patient_nationality` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `age_years` int NULL DEFAULT NULL,
  `pt_walk_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `is_auth_exempt` tinyint(1) NOT NULL DEFAULT 0,
  `is_auth_required` tinyint(1) NOT NULL DEFAULT 0,
  `rights_group` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'OTHERS',
  `auth_code` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `is_identity_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_identity_not_verified` tinyint(1) NOT NULL DEFAULT 1,
  `patient_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_opd_doctor_screen` tinyint(1) NOT NULL DEFAULT 0,
  `service_charge_opd` decimal(14, 2) NOT NULL DEFAULT 0.00,
  `is_cancelled` tinyint(1) NOT NULL DEFAULT 0,
  `is_test_patient` tinyint(1) NOT NULL DEFAULT 0,
  `refreshed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `idx_fvs_service_date`(`service_date` ASC) USING BTREE,
  INDEX `idx_fvs_visit_date`(`visit_date` ASC) USING BTREE,
  INDEX `idx_fvs_admit_date`(`admit_date` ASC) USING BTREE,
  INDEX `idx_fvs_main_dep`(`main_dep` ASC) USING BTREE,
  INDEX `idx_fvs_patient_type`(`patient_type` ASC) USING BTREE,
  INDEX `idx_fvs_rights_group`(`rights_group` ASC) USING BTREE,
  INDEX `idx_fvs_date_dep`(`service_date` ASC, `main_dep` ASC) USING BTREE,
  INDEX `idx_fvs_date_rights`(`service_date` ASC, `rights_group` ASC) USING BTREE,
  INDEX `idx_fvs_date_type`(`service_date` ASC, `patient_type` ASC) USING BTREE,
  INDEX `idx_fvs_hn`(`hn` ASC) USING BTREE,
  INDEX `idx_fvs_an`(`an` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for population_master
-- ----------------------------
DROP TABLE IF EXISTS `population_master`;
CREATE TABLE `population_master`  (
  `population_master_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `reference_date` date NOT NULL,
  `population_total` int NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`population_master_id`) USING BTREE,
  INDEX `idx_pm_active_date`(`is_active` ASC, `reference_date` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sp_claim_audit_3y
-- ----------------------------
DROP TABLE IF EXISTS `sp_claim_audit_3y`;
CREATE TABLE `sp_claim_audit_3y`  (
  `service_date` date NOT NULL,
  `month_key` char(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `year_key` smallint NOT NULL,
  `vn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `hn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `an` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `is_admit` tinyint(1) NOT NULL DEFAULT 0,
  `claim_status_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'no_claim_record',
  `claim_status_group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ยังไม่มีข้อมูลเคลม',
  `total_charge` decimal(14, 2) NOT NULL DEFAULT 0.00,
  `patient_paid_total` decimal(14, 2) NOT NULL DEFAULT 0.00,
  `claim_amount` decimal(14, 2) NOT NULL DEFAULT 0.00,
  `hospital_burden_before_claim` decimal(14, 2) NOT NULL DEFAULT 0.00,
  `hospital_burden_after_claim` decimal(14, 2) NOT NULL DEFAULT 0.00,
  `sent_claim_amount` decimal(14, 2) NOT NULL DEFAULT 0.00,
  `wait_pay_claim_amount` decimal(14, 2) NOT NULL DEFAULT 0.00,
  `settled_claim_amount` decimal(14, 2) NOT NULL DEFAULT 0.00,
  `unclaimed_service_amount` decimal(14, 2) NOT NULL DEFAULT 0.00,
  `other_review_amount` decimal(14, 2) NOT NULL DEFAULT 0.00,
  `no_claim_record_amount` decimal(14, 2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `idx_service_date`(`service_date` ASC) USING BTREE,
  INDEX `idx_month_key`(`month_key` ASC) USING BTREE,
  INDEX `idx_year_key`(`year_key` ASC) USING BTREE,
  INDEX `idx_hn`(`hn` ASC) USING BTREE,
  INDEX `idx_an`(`an` ASC) USING BTREE,
  INDEX `idx_is_admit`(`is_admit` ASC) USING BTREE,
  INDEX `idx_claim_status_code`(`claim_status_code` ASC) USING BTREE,
  INDEX `idx_claim_status_group`(`claim_status_group` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- View structure for vw_disease_top10_base
-- ----------------------------
DROP VIEW IF EXISTS `vw_disease_top10_base`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_disease_top10_base` AS select `d`.`service_date` AS `service_date`,`d`.`admit_date` AS `admit_date`,`d`.`discharge_date` AS `discharge_date`,`d`.`icd10` AS `icd10`,`d`.`icd_name` AS `icd_name`,`d`.`main_dep` AS `main_dep`,`d`.`patient_type` AS `patient_type`,`d`.`rights_group` AS `rights_group`,`d`.`is_chronic_target` AS `is_chronic_target`,count(0) AS `diagnosis_count` from `fact_visit_diag` `d` where `d`.`icd10` is not null and trim(`d`.`icd10`) <> '' and `d`.`icd10`  not like 'Z%' and `d`.`icd_name` is not null and trim(`d`.`icd_name`) <> '' group by `d`.`service_date`,`d`.`admit_date`,`d`.`discharge_date`,`d`.`icd10`,`d`.`icd_name`,`d`.`main_dep`,`d`.`patient_type`,`d`.`rights_group`,`d`.`is_chronic_target`;

-- ----------------------------
-- View structure for vw_phase2_common_disease_top10
-- ----------------------------
DROP VIEW IF EXISTS `vw_phase2_common_disease_top10`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_phase2_common_disease_top10` AS select `d`.`icd10` AS `icd10`,`d`.`icd_name` AS `icd_name`,count(0) AS `diagnosis_count` from `fact_visit_diag` `d` where `d`.`icd10` is not null and trim(`d`.`icd10`) <> '' and `d`.`icd10`  not like 'Z%' and `d`.`icd_name` is not null and trim(`d`.`icd_name`) <> '' group by `d`.`icd10`,`d`.`icd_name`;

-- ----------------------------
-- View structure for vw_phase2_common_disease_top10_base
-- ----------------------------
DROP VIEW IF EXISTS `vw_phase2_common_disease_top10_base`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_phase2_common_disease_top10_base` AS select `d`.`service_date` AS `service_date`,`d`.`icd10` AS `icd10`,`d`.`icd_name` AS `icd_name`,`d`.`main_dep` AS `main_dep`,`d`.`patient_type` AS `patient_type`,`d`.`rights_group` AS `rights_group`,`d`.`is_chronic_target` AS `is_chronic_target`,count(0) AS `diagnosis_count` from `fact_visit_diag` `d` where `d`.`icd10` is not null and trim(`d`.`icd10`) <> '' and `d`.`icd10`  not like 'Z%' and `d`.`icd_name` is not null and trim(`d`.`icd_name`) <> '' group by `d`.`service_date`,`d`.`icd10`,`d`.`icd_name`,`d`.`main_dep`,`d`.`patient_type`,`d`.`rights_group`,`d`.`is_chronic_target`;

-- ----------------------------
-- View structure for vw_phase2_village_typearea_pivot
-- ----------------------------
DROP VIEW IF EXISTS `vw_phase2_village_typearea_pivot`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_phase2_village_typearea_pivot` AS select `x`.`village_id` AS `village_id`,`x`.`village_code` AS `village_code`,`x`.`village_moo` AS `village_moo`,`x`.`village_name` AS `village_name`,sum(case when `x`.`typearea` = 1 then `x`.`population_count` else 0 end) AS `typearea_1`,sum(case when `x`.`typearea` = 2 then `x`.`population_count` else 0 end) AS `typearea_2`,sum(case when `x`.`typearea` = 3 then `x`.`population_count` else 0 end) AS `typearea_3`,sum(case when `x`.`typearea` in (1,3) then `x`.`population_count` else 0 end) AS `typearea_1_3` from (`fact_population_village_typearea_snapshot` `x` join (select max(`fact_population_village_typearea_snapshot`.`snapshot_date`) AS `snapshot_date` from `fact_population_village_typearea_snapshot`) `m` on(`m`.`snapshot_date` = `x`.`snapshot_date`)) where `x`.`village_id` <> 1 group by `x`.`village_id`,`x`.`village_code`,`x`.`village_moo`,`x`.`village_name` order by cast(coalesce(nullif(`x`.`village_moo`,''),'0') as unsigned),`x`.`village_name`;

-- ----------------------------
-- View structure for vw_sp_claim_dashboard_totals
-- ----------------------------
DROP VIEW IF EXISTS `vw_sp_claim_dashboard_totals`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_sp_claim_dashboard_totals` AS select count(0) AS `total_visit`,sum(`sp_claim_audit_3y`.`total_charge`) AS `total_charge`,sum(`sp_claim_audit_3y`.`patient_paid_total`) AS `patient_paid_total`,sum(`sp_claim_audit_3y`.`sent_claim_amount`) AS `sent_claim_amount`,sum(`sp_claim_audit_3y`.`wait_pay_claim_amount`) AS `wait_pay_claim_amount`,sum(`sp_claim_audit_3y`.`settled_claim_amount`) AS `settled_claim_amount`,sum(`sp_claim_audit_3y`.`unclaimed_service_amount`) AS `unclaimed_service_amount`,sum(`sp_claim_audit_3y`.`other_review_amount` + `sp_claim_audit_3y`.`no_claim_record_amount`) AS `pending_claim_amount`,sum(`sp_claim_audit_3y`.`hospital_burden_before_claim`) AS `hospital_burden_before_claim`,sum(`sp_claim_audit_3y`.`hospital_burden_after_claim`) AS `hospital_burden_after_claim`,sum(case when `sp_claim_audit_3y`.`claim_status_code` = 'settled' then `sp_claim_audit_3y`.`total_charge` else 0 end) AS `settled_total_charge`,sum(case when `sp_claim_audit_3y`.`claim_status_code` = 'settled' then `sp_claim_audit_3y`.`claim_amount` else 0 end) AS `settled_claim_received`,sum(case when `sp_claim_audit_3y`.`claim_status_code` = 'settled' then `sp_claim_audit_3y`.`hospital_burden_after_claim` else 0 end) AS `settled_balance_after_claim` from `sp_claim_audit_3y`;

-- ----------------------------
-- View structure for vw_sp_claim_monthly_summary
-- ----------------------------
DROP VIEW IF EXISTS `vw_sp_claim_monthly_summary`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_sp_claim_monthly_summary` AS select `sp_claim_audit_3y`.`month_key` AS `month_key`,count(0) AS `total_visit`,sum(`sp_claim_audit_3y`.`total_charge`) AS `total_charge`,sum(`sp_claim_audit_3y`.`patient_paid_total`) AS `patient_paid_total`,sum(`sp_claim_audit_3y`.`sent_claim_amount`) AS `sent_claim_amount`,sum(`sp_claim_audit_3y`.`wait_pay_claim_amount`) AS `wait_pay_claim_amount`,sum(`sp_claim_audit_3y`.`settled_claim_amount`) AS `settled_claim_amount`,sum(`sp_claim_audit_3y`.`unclaimed_service_amount`) AS `unclaimed_service_amount`,sum(`sp_claim_audit_3y`.`other_review_amount`) AS `other_review_amount`,sum(`sp_claim_audit_3y`.`no_claim_record_amount`) AS `no_claim_record_amount`,sum(`sp_claim_audit_3y`.`hospital_burden_before_claim`) AS `hospital_burden_before_claim`,sum(`sp_claim_audit_3y`.`hospital_burden_after_claim`) AS `hospital_burden_after_claim` from `sp_claim_audit_3y` group by `sp_claim_audit_3y`.`month_key` order by `sp_claim_audit_3y`.`month_key`;

-- ----------------------------
-- View structure for vw_sp_claim_settled_finance
-- ----------------------------
DROP VIEW IF EXISTS `vw_sp_claim_settled_finance`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_sp_claim_settled_finance` AS select `sp_claim_audit_3y`.`month_key` AS `month_key`,count(0) AS `settled_visit_count`,sum(`sp_claim_audit_3y`.`total_charge`) AS `settled_total_charge`,sum(`sp_claim_audit_3y`.`patient_paid_total`) AS `settled_patient_paid_total`,sum(`sp_claim_audit_3y`.`claim_amount`) AS `settled_claim_received`,sum(`sp_claim_audit_3y`.`hospital_burden_before_claim`) AS `settled_hospital_burden_before_claim`,sum(`sp_claim_audit_3y`.`hospital_burden_after_claim`) AS `settled_balance_after_claim` from `sp_claim_audit_3y` where `sp_claim_audit_3y`.`claim_status_code` = 'settled' group by `sp_claim_audit_3y`.`month_key` order by `sp_claim_audit_3y`.`month_key`;

-- ----------------------------
-- View structure for vw_sp_claim_status_summary
-- ----------------------------
DROP VIEW IF EXISTS `vw_sp_claim_status_summary`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_sp_claim_status_summary` AS select `sp_claim_audit_3y`.`claim_status_code` AS `claim_status_code`,`sp_claim_audit_3y`.`claim_status_group` AS `claim_status_group`,count(0) AS `visit_count`,sum(`sp_claim_audit_3y`.`total_charge`) AS `total_charge`,sum(`sp_claim_audit_3y`.`patient_paid_total`) AS `patient_paid_total`,sum(`sp_claim_audit_3y`.`claim_amount`) AS `claim_amount`,sum(`sp_claim_audit_3y`.`hospital_burden_before_claim`) AS `hospital_burden_before_claim`,sum(`sp_claim_audit_3y`.`hospital_burden_after_claim`) AS `hospital_burden_after_claim`,sum(`sp_claim_audit_3y`.`sent_claim_amount`) AS `sent_claim_amount`,sum(`sp_claim_audit_3y`.`wait_pay_claim_amount`) AS `wait_pay_claim_amount`,sum(`sp_claim_audit_3y`.`settled_claim_amount`) AS `settled_claim_amount`,sum(`sp_claim_audit_3y`.`unclaimed_service_amount`) AS `unclaimed_service_amount`,sum(`sp_claim_audit_3y`.`other_review_amount`) AS `other_review_amount`,sum(`sp_claim_audit_3y`.`no_claim_record_amount`) AS `no_claim_record_amount` from `sp_claim_audit_3y` group by `sp_claim_audit_3y`.`claim_status_code`,`sp_claim_audit_3y`.`claim_status_group` order by count(0) desc,`sp_claim_audit_3y`.`claim_status_code`;

-- ----------------------------
-- View structure for vw_village_typearea_pivot
-- ----------------------------
DROP VIEW IF EXISTS `vw_village_typearea_pivot`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_village_typearea_pivot` AS select `x`.`village_id` AS `village_id`,`x`.`village_code` AS `village_code`,`x`.`village_moo` AS `village_moo`,`x`.`village_name` AS `village_name`,sum(case when `x`.`typearea` = 1 then `x`.`population_count` else 0 end) AS `typearea_1`,sum(case when `x`.`typearea` = 2 then `x`.`population_count` else 0 end) AS `typearea_2`,sum(case when `x`.`typearea` = 3 then `x`.`population_count` else 0 end) AS `typearea_3`,sum(case when `x`.`typearea` in (1,3) then `x`.`population_count` else 0 end) AS `typearea_1_3` from (`fact_population_village_typearea_snapshot` `x` join (select max(`fact_population_village_typearea_snapshot`.`snapshot_date`) AS `snapshot_date` from `fact_population_village_typearea_snapshot`) `m` on(`m`.`snapshot_date` = `x`.`snapshot_date`)) where `x`.`village_id` <> 1 group by `x`.`village_id`,`x`.`village_code`,`x`.`village_moo`,`x`.`village_name` order by cast(coalesce(nullif(`x`.`village_moo`,''),'0') as unsigned),`x`.`village_name`;

-- ----------------------------
-- Procedure structure for sp_rebuild_last_5_years
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_rebuild_last_5_years`;
delimiter ;;
CREATE PROCEDURE `sp_rebuild_last_5_years`()
BEGIN
    TRUNCATE TABLE `fact_dashboard_daily`;
    TRUNCATE TABLE `fact_visit_diag`;
    TRUNCATE TABLE `fact_visit_service`;
    TRUNCATE TABLE `fact_ipd_stay`;
    TRUNCATE TABLE `fact_population_snapshot`;
    TRUNCATE TABLE `fact_population_village_typearea_snapshot`;

    CALL `sp_run_dashboard_refresh`(DATE_SUB(CURDATE(), INTERVAL 5 YEAR), CURDATE());
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for sp_refresh_claim_audit_3y
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_refresh_claim_audit_3y`;
delimiter ;;
CREATE PROCEDURE `sp_refresh_claim_audit_3y`()
BEGIN
    TRUNCATE TABLE sp_claim_audit_3y;

    INSERT INTO sp_claim_audit_3y (
        service_date,
        month_key,
        year_key,
        vn,
        hn,
        an,
        is_admit,
        claim_status_code,
        claim_status_group,
        total_charge,
        patient_paid_total,
        claim_amount,
        hospital_burden_before_claim,
        hospital_burden_after_claim,
        sent_claim_amount,
        wait_pay_claim_amount,
        settled_claim_amount,
        unclaimed_service_amount,
        other_review_amount,
        no_claim_record_amount
    )
    WITH
    cp_raw AS (
        SELECT
            c.vn,
            TRIM(c.fdh_claim_status_message) AS claim_status_raw,
            COALESCE(c.fdh_act_amt, 0) AS claim_amount,
            CASE
                WHEN c.fdh_claim_status_message IS NULL
                     OR TRIM(c.fdh_claim_status_message) = '' THEN 'no_claim_record'
                WHEN LOWER(TRIM(c.fdh_claim_status_message)) LIKE 'approved%' THEN 'approved'
                WHEN LOWER(TRIM(c.fdh_claim_status_message)) LIKE 'cut_off_batch%' THEN 'cut_off_batch'
                WHEN LOWER(TRIM(c.fdh_claim_status_message)) LIKE 'settled%' THEN 'settled'
                WHEN LOWER(TRIM(c.fdh_claim_status_message)) LIKE 'unclaimed%' THEN 'unclaimed'
                WHEN LOWER(TRIM(c.fdh_claim_status_message)) LIKE 'received%' THEN 'received'
                WHEN LOWER(TRIM(c.fdh_claim_status_message)) LIKE 'waited%' THEN 'waited'
                WHEN LOWER(TRIM(c.fdh_claim_status_message)) LIKE 'rejected%' THEN 'rejected'
                WHEN LOWER(TRIM(c.fdh_claim_status_message)) LIKE 'reclaimed%' THEN 'reclaimed'
                WHEN LOWER(TRIM(c.fdh_claim_status_message)) LIKE 'nhso_appealed%' THEN 'nhso_appealed'
                WHEN LOWER(TRIM(c.fdh_claim_status_message)) LIKE 'nhso_canceled%' THEN 'nhso_canceled'
                WHEN LOWER(TRIM(c.fdh_claim_status_message)) LIKE 'incompleted%' THEN 'incompleted'
                WHEN LOWER(TRIM(c.fdh_claim_status_message)) LIKE 'dupplicated%' THEN 'dupplicated'
                WHEN TRIM(c.fdh_claim_status_message) = 'ไม่พบข้อมูลรหัสเคลมในระบบ' THEN 'claim_not_found'
                WHEN TRIM(c.fdh_claim_status_message) = 'ไม่มีรายการนี้ส่งเข้ามาในระบบ' THEN 'not_sent_to_system'
                WHEN TRIM(c.fdh_claim_status_message) = 'ไม่สามารถเชื่อมต่อระบบ สปสช. ได้' THEN 'connect_error'
                ELSE 'other'
            END AS claim_status_code
        FROM hosxp.fdh_claim_status c
    ),
    cp_agg AS (
        SELECT
            r.vn,
            MAX(
                CASE r.claim_status_code
                    WHEN 'settled'            THEN 100
                    WHEN 'cut_off_batch'      THEN 90
                    WHEN 'approved'           THEN 80
                    WHEN 'received'           THEN 70
                    WHEN 'waited'             THEN 60
                    WHEN 'unclaimed'          THEN 50
                    WHEN 'rejected'           THEN 40
                    WHEN 'reclaimed'          THEN 39
                    WHEN 'nhso_appealed'      THEN 38
                    WHEN 'nhso_canceled'      THEN 37
                    WHEN 'incompleted'        THEN 36
                    WHEN 'dupplicated'        THEN 35
                    WHEN 'claim_not_found'    THEN 20
                    WHEN 'not_sent_to_system' THEN 19
                    WHEN 'connect_error'      THEN 18
                    WHEN 'other'              THEN 10
                    ELSE 0
                END
            ) AS status_rank,
            MAX(r.claim_amount) AS claim_amount
        FROM cp_raw r
        GROUP BY r.vn
    ),
    cp_final AS (
        SELECT
            a.vn,
            a.claim_amount,
            CASE a.status_rank
                WHEN 100 THEN 'settled'
                WHEN 90  THEN 'cut_off_batch'
                WHEN 80  THEN 'approved'
                WHEN 70  THEN 'received'
                WHEN 60  THEN 'waited'
                WHEN 50  THEN 'unclaimed'
                WHEN 40  THEN 'rejected'
                WHEN 39  THEN 'reclaimed'
                WHEN 38  THEN 'nhso_appealed'
                WHEN 37  THEN 'nhso_canceled'
                WHEN 36  THEN 'incompleted'
                WHEN 35  THEN 'dupplicated'
                WHEN 20  THEN 'claim_not_found'
                WHEN 19  THEN 'not_sent_to_system'
                WHEN 18  THEN 'connect_error'
                WHEN 10  THEN 'other'
                ELSE 'no_claim_record'
            END AS claim_status_code,
            CASE a.status_rank
                WHEN 100 THEN 'โอนแล้ว'
                WHEN 90  THEN 'รอจ่าย'
                WHEN 80  THEN 'ส่งแล้ว'
                WHEN 50  THEN 'ไม่ประสงค์เบิก'
                WHEN 0   THEN 'ยังไม่มีข้อมูลเคลม'
                ELSE 'ต้องทบทวน'
            END AS claim_status_group
        FROM cp_agg a
    )
    SELECT
        o.vstdate AS service_date,
        DATE_FORMAT(o.vstdate, '%Y-%m') AS month_key,
        YEAR(o.vstdate) AS year_key,
        o.vn,
        o.hn,
        o.an,
        CASE WHEN i.an IS NOT NULL AND i.an <> '' THEN 1 ELSE 0 END AS is_admit,
        COALESCE(cp.claim_status_code, 'no_claim_record') AS claim_status_code,
        COALESCE(cp.claim_status_group, 'ยังไม่มีข้อมูลเคลม') AS claim_status_group,
        ROUND(COALESCE(vs.income, 0) + COALESCE(ast.income, 0), 2) AS total_charge,
        ROUND(COALESCE(vs.paid_money, 0) + COALESCE(ast.paid_money, 0), 2) AS patient_paid_total,
        ROUND(COALESCE(cp.claim_amount, 0), 2) AS claim_amount,
        ROUND(
            (COALESCE(vs.income, 0) + COALESCE(ast.income, 0))
            - (COALESCE(vs.paid_money, 0) + COALESCE(ast.paid_money, 0)),
            2
        ) AS hospital_burden_before_claim,
        ROUND(
            (COALESCE(vs.income, 0) + COALESCE(ast.income, 0))
            - (
                (COALESCE(vs.paid_money, 0) + COALESCE(ast.paid_money, 0))
                + COALESCE(cp.claim_amount, 0)
            ),
            2
        ) AS hospital_burden_after_claim,
        ROUND(CASE WHEN COALESCE(cp.claim_status_code, 'no_claim_record') = 'approved' THEN COALESCE(cp.claim_amount, 0) ELSE 0 END, 2) AS sent_claim_amount,
        ROUND(CASE WHEN COALESCE(cp.claim_status_code, 'no_claim_record') = 'cut_off_batch' THEN COALESCE(cp.claim_amount, 0) ELSE 0 END, 2) AS wait_pay_claim_amount,
        ROUND(CASE WHEN COALESCE(cp.claim_status_code, 'no_claim_record') = 'settled' THEN COALESCE(cp.claim_amount, 0) ELSE 0 END, 2) AS settled_claim_amount,
        ROUND(
            CASE WHEN COALESCE(cp.claim_status_code, 'no_claim_record') = 'unclaimed'
                 THEN ((COALESCE(vs.income, 0) + COALESCE(ast.income, 0)) - (COALESCE(vs.paid_money, 0) + COALESCE(ast.paid_money, 0)))
                 ELSE 0 END,
            2
        ) AS unclaimed_service_amount,
        ROUND(
            CASE WHEN COALESCE(cp.claim_status_code, 'no_claim_record') = 'received'
                 THEN ((COALESCE(vs.income, 0) + COALESCE(ast.income, 0)) - (COALESCE(vs.paid_money, 0) + COALESCE(ast.paid_money, 0)))
                 ELSE 0 END,
            2
        ) AS other_review_amount,
        ROUND(
            CASE WHEN cp.vn IS NULL
                 THEN ((COALESCE(vs.income, 0) + COALESCE(ast.income, 0)) - (COALESCE(vs.paid_money, 0) + COALESCE(ast.paid_money, 0)))
                 ELSE 0 END,
            2
        ) AS no_claim_record_amount
    FROM hosxp.ovst o
    LEFT JOIN hosxp.ipt i ON i.an = o.an
    LEFT JOIN hosxp.vn_stat vs ON vs.vn = o.vn
    LEFT JOIN hosxp.an_stat ast ON ast.an = i.an
    LEFT JOIN cp_final cp ON cp.vn = o.vn
    WHERE o.vstdate >= MAKEDATE(YEAR(CURDATE()) - 2, 1);
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for sp_refresh_fact_dashboard_daily
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_refresh_fact_dashboard_daily`;
delimiter ;;
CREATE PROCEDURE `sp_refresh_fact_dashboard_daily`(IN p_start DATE, IN p_end DATE)
BEGIN
    DECLARE v_date DATE;

    DELETE FROM `fact_dashboard_daily`
    WHERE `service_date` BETWEEN p_start AND p_end;

    SET v_date = p_start;

    WHILE v_date <= p_end DO
        INSERT INTO `fact_dashboard_daily` (
            `service_date`, `total_service_count`, `opd_all_hn`, `opd_doctor_screen_vn`,
            `ipd_an_count`, `identity_verified_vn`, `identity_not_verified_vn`,
            `total_service_charges_opd`, `appointment_total_hn`, `appointment_attended_hn`,
            `appointment_missed_hn`, `er_visit_vn`, `referout_vn`, `ipd_admit_today_an`,
            `ipd_open_an`, `last_refreshed_at`
        )
        SELECT
            v_date,
            COALESCE((
                SELECT COUNT(DISTINCT fs.vn)
                FROM `fact_visit_service` fs
                WHERE fs.service_date = v_date
                  AND fs.is_cancelled = 0
                  AND fs.is_test_patient = 0
            ), 0),
            COALESCE((
                SELECT COUNT(DISTINCT fs.hn)
                FROM `fact_visit_service` fs
                WHERE fs.service_date = v_date
                  AND fs.patient_type = 'OPD'
                  AND fs.is_cancelled = 0
                  AND fs.is_test_patient = 0
            ), 0),
            COALESCE((
                SELECT COUNT(DISTINCT fs.vn)
                FROM `fact_visit_service` fs
                WHERE fs.service_date = v_date
                  AND fs.patient_type = 'OPD'
                  AND fs.is_opd_doctor_screen = 1
                  AND fs.is_cancelled = 0
                  AND fs.is_test_patient = 0
            ), 0),
            COALESCE((
                SELECT COUNT(DISTINCT fs.an)
                FROM `fact_visit_service` fs
                WHERE fs.service_date = v_date
                  AND fs.patient_type = 'IPD'
                  AND fs.is_cancelled = 0
                  AND fs.is_test_patient = 0
            ), 0),
            COALESCE((
                SELECT COUNT(DISTINCT fs.vn)
                FROM `fact_visit_service` fs
                WHERE fs.service_date = v_date
                  AND fs.is_identity_verified = 1
                  AND fs.is_cancelled = 0
                  AND fs.is_test_patient = 0
            ), 0),
            COALESCE((
                SELECT COUNT(DISTINCT fs.vn)
                FROM `fact_visit_service` fs
                WHERE fs.service_date = v_date
                  AND fs.is_identity_not_verified = 1
                  AND fs.is_cancelled = 0
                  AND fs.is_test_patient = 0
            ), 0),
            COALESCE((
                SELECT SUM(COALESCE(fs.service_charge_opd, 0))
                FROM `fact_visit_service` fs
                WHERE fs.service_date = v_date
                  AND fs.patient_type IN ('OPD','ER')
                  AND fs.is_cancelled = 0
                  AND fs.is_test_patient = 0
            ), 0),
            COALESCE((
                SELECT COUNT(DISTINCT oa.hn)
                FROM `hosxp`.`oapp` oa
                WHERE oa.nextdate = v_date
            ), 0),
            COALESCE((
                SELECT COUNT(DISTINCT oa.hn)
                FROM `hosxp`.`oapp` oa
                INNER JOIN `hosxp`.`ovst` o
                    ON o.hn = oa.hn
                   AND o.vstdate = oa.nextdate
                WHERE oa.nextdate = v_date
            ), 0),
            COALESCE((
                SELECT COUNT(DISTINCT oa.hn)
                FROM `hosxp`.`oapp` oa
                LEFT JOIN `hosxp`.`ovst` o
                    ON o.hn = oa.hn
                   AND o.vstdate = oa.nextdate
                WHERE oa.nextdate = v_date
                  AND o.vn IS NULL
            ), 0),
            COALESCE((
                SELECT COUNT(DISTINCT er.vn)
                FROM `hosxp`.`er_regist` er
                WHERE er.vstdate = v_date
            ), 0),
            COALESCE((
                SELECT COUNT(DISTINCT ro.vn)
                FROM `hosxp`.`referout` ro
                WHERE ro.refer_date = v_date
            ), 0),
            COALESCE((
                SELECT COUNT(DISTINCT ip.an)
                FROM `hosxp`.`ipt` ip
                WHERE ip.regdate = v_date
                  AND ip.dchdate IS NULL
            ), 0),
            CASE
                WHEN v_date = CURDATE() THEN COALESCE((
                    SELECT COUNT(DISTINCT ip.an)
                    FROM `hosxp`.`ipt` ip
                    WHERE ip.dchdate IS NULL
                ), 0)
                ELSE 0
            END,
            NOW();

        SET v_date = DATE_ADD(v_date, INTERVAL 1 DAY);
    END WHILE;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for sp_refresh_fact_ipd_stay
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_refresh_fact_ipd_stay`;
delimiter ;;
CREATE PROCEDURE `sp_refresh_fact_ipd_stay`(IN p_start DATE, IN p_end DATE)
BEGIN
    DELETE FROM `fact_ipd_stay`
    WHERE `dchdate` BETWEEN p_start AND p_end;

    INSERT INTO `fact_ipd_stay` (
        `an`, `hn`, `regdate`, `dchdate`, `ot`, `rw`, `adjrw`, `rights_group`, `refreshed_at`
    )
    SELECT
        ip.an,
        ip.hn,
        ip.regdate,
        ip.dchdate,
        COALESCE(ip.ot, 0) AS ot,
        COALESCE(ip.rw, 0) AS rw,
        COALESCE(ip.adjrw, 0) AS adjrw,
        COALESCE(fs.rights_group, 'OTHERS') AS rights_group,
        NOW() AS refreshed_at
    FROM `hosxp`.`ipt` ip
    LEFT JOIN `fact_visit_service` fs
        ON fs.an = ip.an
    WHERE ip.dchdate BETWEEN p_start AND p_end
      AND ip.dchdate IS NOT NULL
    ON DUPLICATE KEY UPDATE
        `hn` = VALUES(`hn`),
        `regdate` = VALUES(`regdate`),
        `dchdate` = VALUES(`dchdate`),
        `ot` = VALUES(`ot`),
        `rw` = VALUES(`rw`),
        `adjrw` = VALUES(`adjrw`),
        `rights_group` = VALUES(`rights_group`),
        `refreshed_at` = VALUES(`refreshed_at`);
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for sp_refresh_fact_visit_diag
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_refresh_fact_visit_diag`;
delimiter ;;
CREATE PROCEDURE `sp_refresh_fact_visit_diag`(IN p_start DATE, IN p_end DATE)
BEGIN
    DELETE FROM `fact_visit_diag`
    WHERE (
        `patient_type` = 'OPD'
        AND `service_date` BETWEEN p_start AND p_end
    )
    OR (
        `patient_type` = 'IPD'
        AND (
            `service_date` BETWEEN p_start AND p_end
            OR `admit_date` BETWEEN p_start AND p_end
            OR `discharge_date` BETWEEN p_start AND p_end
        )
    );

    INSERT INTO `fact_visit_diag` (
        `vn`, `an`, `hn`, `service_date`, `visit_date`, `admit_date`, `discharge_date`,
        `main_dep`, `department_name`, `rights_group`, `patient_type`,
        `icd10`, `icd_name`, `diag_type`, `is_chronic_target`, `refreshed_at`
    )
    SELECT
        fs.vn,
        fs.an,
        fs.hn,
        fs.service_date,
        fs.visit_date,
        fs.admit_date,
        NULL AS discharge_date,
        fs.main_dep,
        fs.department_name,
        fs.rights_group,
        'OPD' AS patient_type,
        od.icd10,
        icd.`name` AS icd_name,
        od.diagtype AS diag_type,
        CASE
            WHEN od.icd10 REGEXP '^(E1[0-4])'
              OR od.icd10 REGEXP '^(I1[0-5])'
              OR od.icd10 REGEXP '^(I2[0-5])'
              OR od.icd10 REGEXP '^(J4[1-4])'
              OR od.icd10 REGEXP '^(I6[0-4])'
            THEN 1 ELSE 0
        END AS is_chronic_target,
        NOW() AS refreshed_at
    FROM `fact_visit_service` fs
    INNER JOIN `hosxp`.`ovstdiag` od
        ON od.vn = fs.vn
    INNER JOIN `hosxp`.`icd101` icd
        ON icd.code = od.icd10
    WHERE fs.patient_type = 'OPD'
      AND fs.service_date BETWEEN p_start AND p_end
      AND fs.is_cancelled = 0
      AND fs.is_test_patient = 0
      AND od.icd10 IS NOT NULL
      AND TRIM(od.icd10) <> ''
      AND od.icd10 NOT LIKE 'Z%'

    UNION ALL

    SELECT
        fs.vn,
        ip.an,
        COALESCE(fs.hn, ip.hn) AS hn,
        ip.regdate AS service_date,
        fs.visit_date,
        ip.regdate AS admit_date,
        ip.dchdate AS discharge_date,
        fs.main_dep,
        fs.department_name,
        COALESCE(fs.rights_group, 'OTHERS') AS rights_group,
        'IPD' AS patient_type,
        idg.icd10,
        icd.`name` AS icd_name,
        idg.diagtype AS diag_type,
        0 AS is_chronic_target,
        NOW() AS refreshed_at
    FROM `hosxp`.`ipt` ip
    INNER JOIN `hosxp`.`iptdiag` idg
        ON idg.an = ip.an
    INNER JOIN `hosxp`.`icd101` icd
        ON icd.code = idg.icd10
    LEFT JOIN `fact_visit_service` fs
        ON fs.an = ip.an
    WHERE (ip.regdate BETWEEN p_start AND p_end OR ip.dchdate BETWEEN p_start AND p_end)
      AND idg.icd10 IS NOT NULL
      AND TRIM(idg.icd10) <> ''
      AND idg.icd10 NOT LIKE 'Z%';
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for sp_refresh_fact_visit_service
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_refresh_fact_visit_service`;
delimiter ;;
CREATE PROCEDURE `sp_refresh_fact_visit_service`(IN p_start DATE, IN p_end DATE)
BEGIN
    DELETE FROM `fact_visit_service`
    WHERE `service_date` BETWEEN p_start AND p_end
       OR `visit_date` BETWEEN p_start AND p_end
       OR `admit_date` BETWEEN p_start AND p_end;

    INSERT INTO `fact_visit_service` (
        `vn`, `hn`, `an`, `visit_date`, `visit_time`, `admit_date`, `service_date`,
        `main_dep`, `department_name`, `pttype`, `hipdata_code`, `rights_group`,
        `auth_code`, `pt_walk_name`, `patient_nationality`, `age_years`,
        `is_identity_verified`, `is_identity_not_verified`, `is_auth_exempt`, `is_auth_required`,
        `patient_type`, `is_opd_doctor_screen`, `service_charge_opd`, `is_cancelled`,
        `is_test_patient`, `refreshed_at`
    )
    SELECT
        b.`vn`, b.`hn`, b.`an`, b.`visit_date`, b.`visit_time`, b.`admit_date`, b.`service_date`,
        b.`main_dep`, b.`department_name`, b.`pttype`, b.`hipdata_code`, b.`rights_group`,
        b.`auth_code`, b.`pt_walk_name`, b.`patient_nationality`, b.`age_years`,
        CASE
            WHEN b.`auth_required` = 0 THEN 1
            WHEN TRIM(COALESCE(b.`auth_code`, '')) <> '' THEN 1
            ELSE 0
        END AS `is_identity_verified`,
        CASE
            WHEN b.`auth_required` = 1 AND TRIM(COALESCE(b.`auth_code`, '')) = '' THEN 1
            ELSE 0
        END AS `is_identity_not_verified`,
        CASE WHEN b.`auth_required` = 0 THEN 1 ELSE 0 END AS `is_auth_exempt`,
        b.`auth_required` AS `is_auth_required`,
        b.`patient_type`, b.`is_opd_doctor_screen`, b.`service_charge_opd`, b.`is_cancelled`,
        b.`is_test_patient`, NOW() AS `refreshed_at`
    FROM (
        SELECT
            o.vn,
            o.hn,
            ip.an,
            o.vstdate AS visit_date,
            o.vsttime AS visit_time,
            ip.regdate AS admit_date,
            CASE WHEN ip.an IS NOT NULL THEN ip.regdate ELSE o.vstdate END AS service_date,
            o.main_dep,
            kd.department AS department_name,
            o.pttype,
            pt.hipdata_code,
            CASE
                WHEN pt.hipdata_code IN ('UCS', 'WEL') THEN 'UCS'
                WHEN pt.hipdata_code IN ('OFC', 'LGO') THEN 'OFC'
                WHEN pt.hipdata_code = 'SSS' THEN 'SSS'
                ELSE 'OTHERS'
            END AS rights_group,
            auth.auth_code,
            pw.`name` AS pt_walk_name,
            pr.nationality AS patient_nationality,
            CASE
                WHEN p.birthday IS NOT NULL THEN TIMESTAMPDIFF(YEAR, p.birthday, o.vstdate)
                ELSE NULL
            END AS age_years,
            CASE
                WHEN ip.an IS NOT NULL THEN 'IPD'
                WHEN o.main_dep IN ('002','003','087') THEN 'ER'
                ELSE 'OPD'
            END AS patient_type,
            CASE WHEN o.main_dep IN ('001','004','057','060') THEN 1 ELSE 0 END AS is_opd_doctor_screen,
            COALESCE(oi.total_service_charges, 0) AS service_charge_opd,
            CASE WHEN oc.vn IS NOT NULL THEN 1 ELSE 0 END AS is_cancelled,
            CASE
                WHEN (
                    LOWER(COALESCE(p.fname, '')) LIKE '%test%'
                    OR LOWER(COALESCE(p.lname, '')) LIKE '%test%'
                    OR LOWER(COALESCE(p.fname, '')) LIKE '%demo%'
                    OR LOWER(COALESCE(p.lname, '')) LIKE '%demo%'
                    OR COALESCE(p.fname, '') LIKE '%ทดสอบ%'
                    OR COALESCE(p.lname, '') LIKE '%ทดสอบ%'
                    OR COALESCE(o.hn, '') IN ('111111111','999999999')
                    OR COALESCE(p.cid, '') IN ('111111111','999999999')
                ) THEN 1 ELSE 0
            END AS is_test_patient,
            CASE
                WHEN COALESCE(pr.nationality, 0) <> 99 THEN 0
                WHEN COALESCE(TIMESTAMPDIFF(YEAR, p.birthday, o.vstdate), 999) < 7 THEN 0
                WHEN pw.`name` IN ('ญาติมารับยาแทน', 'แลป รพ.สต.') THEN 0
                WHEN COALESCE(pw.`name`, '') LIKE 'อื่น%' THEN 0
                ELSE 1
            END AS auth_required
        FROM `hosxp`.`ovst` o
        INNER JOIN `hosxp`.`patient` p
            ON p.hn = o.hn
        LEFT JOIN `hosxp`.`pttype` pt
            ON pt.pttype = o.pttype
        LEFT JOIN `hosxp`.`kskdepartment` kd
            ON kd.depcode = o.main_dep
        LEFT JOIN `hosxp`.`opdscreen` os
            ON os.vn = o.vn
        LEFT JOIN `hosxp`.`pt_walk` pw
            ON pw.walk_id = os.walk_id
        LEFT JOIN (
            SELECT i.vn, MAX(i.an) AS an, MAX(i.regdate) AS regdate
            FROM `hosxp`.`ipt` i
            GROUP BY i.vn
        ) ip
            ON ip.vn = o.vn
        LEFT JOIN (
            SELECT v.vn, MAX(NULLIF(TRIM(v.auth_code), '')) AS auth_code
            FROM `hosxp`.`visit_pttype` v
            GROUP BY v.vn
        ) auth
            ON auth.vn = o.vn
        LEFT JOIN (
            SELECT oi.vn, SUM(COALESCE(oi.sum_price, 0)) AS total_service_charges
            FROM `hosxp`.`opitemrece` oi
            GROUP BY oi.vn
        ) oi
            ON oi.vn = o.vn
        LEFT JOIN (
            SELECT vn
            FROM `hosxp`.`ovst_cancel`
            GROUP BY vn
        ) oc
            ON oc.vn = o.vn
        LEFT JOIN (
            SELECT pr.patient_hn, MAX(pr.nationality) AS nationality
            FROM `hosxp`.`person` pr
            GROUP BY pr.patient_hn
        ) pr
            ON pr.patient_hn = o.hn
        WHERE (
            o.vstdate BETWEEN p_start AND p_end
            OR ip.regdate BETWEEN p_start AND p_end
        )
        GROUP BY o.vn
    ) b
    ON DUPLICATE KEY UPDATE
        `hn` = VALUES(`hn`),
        `an` = VALUES(`an`),
        `visit_date` = VALUES(`visit_date`),
        `visit_time` = VALUES(`visit_time`),
        `admit_date` = VALUES(`admit_date`),
        `service_date` = VALUES(`service_date`),
        `main_dep` = VALUES(`main_dep`),
        `department_name` = VALUES(`department_name`),
        `pttype` = VALUES(`pttype`),
        `hipdata_code` = VALUES(`hipdata_code`),
        `rights_group` = VALUES(`rights_group`),
        `auth_code` = VALUES(`auth_code`),
        `pt_walk_name` = VALUES(`pt_walk_name`),
        `patient_nationality` = VALUES(`patient_nationality`),
        `age_years` = VALUES(`age_years`),
        `is_identity_verified` = VALUES(`is_identity_verified`),
        `is_identity_not_verified` = VALUES(`is_identity_not_verified`),
        `is_auth_exempt` = VALUES(`is_auth_exempt`),
        `is_auth_required` = VALUES(`is_auth_required`),
        `patient_type` = VALUES(`patient_type`),
        `is_opd_doctor_screen` = VALUES(`is_opd_doctor_screen`),
        `service_charge_opd` = VALUES(`service_charge_opd`),
        `is_cancelled` = VALUES(`is_cancelled`),
        `is_test_patient` = VALUES(`is_test_patient`),
        `refreshed_at` = VALUES(`refreshed_at`);
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for sp_refresh_population_snapshot
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_refresh_population_snapshot`;
delimiter ;;
CREATE PROCEDURE `sp_refresh_population_snapshot`()
BEGIN
    DECLARE v_registry_population_total INT DEFAULT 0;

    SELECT COALESCE(pm.population_total, 0)
      INTO v_registry_population_total
    FROM `population_master` pm
    WHERE pm.is_active = 1
    ORDER BY pm.reference_date DESC, pm.population_master_id DESC
    LIMIT 1;

    DELETE FROM `fact_population_snapshot`
    WHERE `snapshot_date` = CURDATE();

    INSERT INTO `fact_population_snapshot` (
        `snapshot_date`, `registry_population_total`, `population_in_area`, `population_in_area_thai`,
        `population_in_district`, `population_in_district_thai`, `source_note`, `last_refreshed_at`
    )
    SELECT
        CURDATE(),
        v_registry_population_total,
        COUNT(DISTINCT CASE
            WHEN p.house_regist_type_id IN (1,3)
             AND COALESCE(p.death, '') <> 'Y'
             AND p.person_discharge_id = '9'
            THEN p.cid END) AS population_in_area,
        COUNT(DISTINCT CASE
            WHEN p.house_regist_type_id IN (1,3)
             AND COALESCE(p.death, '') <> 'Y'
             AND p.person_discharge_id = '9'
             AND p.nationality IN (99)
            THEN p.cid END) AS population_in_area_thai,
        COUNT(DISTINCT CASE
            WHEN p.house_regist_type_id IN (1,2,3)
             AND COALESCE(p.death, '') <> 'Y'
             AND p.person_discharge_id = '9'
            THEN p.cid END) AS population_in_district,
        COUNT(DISTINCT CASE
            WHEN p.house_regist_type_id IN (1,2,3)
             AND COALESCE(p.death, '') <> 'Y'
             AND p.person_discharge_id = '9'
             AND p.nationality IN (99)
            THEN p.cid END) AS population_in_district_thai,
        'ประชากรจริงทั้งอำเภออ้างอิงสำนักทะเบียน; ประชากรในเขต/อำเภออ้างอิง hosxp.person',
        NOW()
    FROM `hosxp`.`person` p;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for sp_refresh_population_village_typearea_snapshot
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_refresh_population_village_typearea_snapshot`;
delimiter ;;
CREATE PROCEDURE `sp_refresh_population_village_typearea_snapshot`()
BEGIN
    DELETE FROM `fact_population_village_typearea_snapshot`
    WHERE `snapshot_date` = CURDATE();

    INSERT INTO `fact_population_village_typearea_snapshot` (
        `snapshot_date`, `village_id`, `village_code`, `village_moo`, `village_name`,
        `home`, `moo`, `chwat`, `aumpur`, `tumbon`, `addr_full`,
        `typearea`, `in_area_flag`, `in_district_flag`, `population_count`, `population_thai_count`
    )
    SELECT
        CURDATE() AS snapshot_date,
        v.village_id,
        CONVERT(v.village_code USING utf8mb4) COLLATE utf8mb4_general_ci AS village_code,
        CONVERT(CAST(v.village_moo AS CHAR) USING utf8mb4) COLLATE utf8mb4_general_ci AS village_moo,
        COALESCE(
            NULLIF(TRIM(CONVERT(v.village_name USING utf8mb4) COLLATE utf8mb4_general_ci), _utf8mb4''),
            CONCAT(_utf8mb4'หมู่ที่ ', CONVERT(CAST(v.village_moo AS CHAR) USING utf8mb4) COLLATE utf8mb4_general_ci)
        ) AS village_name,
        _utf8mb4'' AS home,
        CONVERT(CAST(v.village_moo AS CHAR) USING utf8mb4) COLLATE utf8mb4_general_ci AS moo,
        COALESCE(CONVERT(MAX(t1.`name`) USING utf8mb4) COLLATE utf8mb4_general_ci, _utf8mb4'') AS chwat,
        COALESCE(CONVERT(MAX(t2.`name`) USING utf8mb4) COLLATE utf8mb4_general_ci, _utf8mb4'') AS aumpur,
        COALESCE(CONVERT(MAX(t.`name`) USING utf8mb4) COLLATE utf8mb4_general_ci, _utf8mb4'') AS tumbon,
        CONCAT(
            _utf8mb4'หมู่ที่ ',
            CONVERT(CAST(v.village_moo AS CHAR) USING utf8mb4) COLLATE utf8mb4_general_ci,
            _utf8mb4' ',
            COALESCE(NULLIF(TRIM(CONVERT(v.village_name USING utf8mb4) COLLATE utf8mb4_general_ci), _utf8mb4''), _utf8mb4'')
        ) AS addr_full,
        p.house_regist_type_id AS typearea,
        CASE WHEN p.house_regist_type_id IN (1,3) THEN 1 ELSE 0 END AS in_area_flag,
        CASE WHEN p.house_regist_type_id IN (1,2,3) THEN 1 ELSE 0 END AS in_district_flag,
        COUNT(DISTINCT p.patient_hn) AS population_count,
        COUNT(DISTINCT CASE WHEN p.nationality IN (99) THEN p.patient_hn END) AS population_thai_count
    FROM `hosxp`.`person` p
    INNER JOIN `hosxp`.`house` h
        ON h.house_id = p.house_id
    INNER JOIN `hosxp`.`village` v
        ON v.village_id = h.village_id
    LEFT JOIN `hosxp`.`thaiaddress` t
        ON t.addressid = LEFT(p.vid, 6)
    LEFT JOIN `hosxp`.`thaiaddress` t1
        ON t1.addressid = CONCAT(LEFT(p.vid, 2), '0000')
    LEFT JOIN `hosxp`.`thaiaddress` t2
        ON t2.addressid = CONCAT(LEFT(p.vid, 4), '00')
    WHERE p.house_regist_type_id IN (1,2,3)
      AND COALESCE(p.death, '') <> 'Y'
      AND p.person_discharge_id = '9'
      AND v.village_id <> 1
    GROUP BY
        v.village_id,
        v.village_code,
        CAST(v.village_moo AS CHAR),
        v.village_name,
        p.house_regist_type_id;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for sp_run_dashboard_refresh
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_run_dashboard_refresh`;
delimiter ;;
CREATE PROCEDURE `sp_run_dashboard_refresh`(IN p_start DATE, IN p_end DATE)
BEGIN
    DECLARE v_started_at DATETIME DEFAULT NOW();
    DECLARE v_log_id BIGINT UNSIGNED DEFAULT NULL;

    INSERT INTO `etl_job_log` (`job_name`, `started_at`, `status`, `message`)
    VALUES ('dashboard_refresh', v_started_at, 'RUNNING', CONCAT('start=', p_start, ', end=', p_end));

    SET v_log_id = LAST_INSERT_ID();

    CALL `sp_refresh_fact_visit_service`(p_start, p_end);
    CALL `sp_refresh_fact_visit_diag`(p_start, p_end);
    CALL `sp_refresh_fact_ipd_stay`(p_start, p_end);
    CALL `sp_refresh_fact_dashboard_daily`(p_start, p_end);
    CALL `sp_refresh_population_snapshot`();
    CALL `sp_refresh_population_village_typearea_snapshot`();

    UPDATE `etl_job_log`
    SET `finished_at` = NOW(),
        `status` = 'SUCCESS',
        `rows_affected` = (
            SELECT COUNT(*)
            FROM `fact_visit_service`
            WHERE `service_date` BETWEEN p_start AND p_end
        ) + (
            SELECT COUNT(*)
            FROM `fact_ipd_stay`
            WHERE `dchdate` BETWEEN p_start AND p_end
        ),
        `message` = CONCAT(
            'completed start=', p_start,
            ', end=', p_end,
            ', fact_visit_service=', (
                SELECT COUNT(*) FROM `fact_visit_service`
                WHERE `service_date` BETWEEN p_start AND p_end
            ),
            ', fact_visit_diag=', (
                SELECT COUNT(*) FROM `fact_visit_diag`
                WHERE (`service_date` BETWEEN p_start AND p_end)
                   OR (`discharge_date` BETWEEN p_start AND p_end)
            ),
            ', fact_ipd_stay=', (
                SELECT COUNT(*) FROM `fact_ipd_stay`
                WHERE `dchdate` BETWEEN p_start AND p_end
            )
        )
    WHERE `etl_job_log_id` = v_log_id;
END
;;
delimiter ;

-- ----------------------------
-- Event structure for ev_dashboard_hourly_refresh
-- ----------------------------
DROP EVENT IF EXISTS `ev_dashboard_hourly_refresh`;
delimiter ;;
CREATE EVENT `ev_dashboard_hourly_refresh`
ON SCHEDULE
EVERY '1' HOUR STARTS '2026-03-23 01:00:00'
DO BEGIN
    CALL `sp_run_dashboard_refresh`(DATE_SUB(CURDATE(), INTERVAL 3 DAY), CURDATE());
END
;;
delimiter ;

-- ----------------------------
-- Event structure for ev_dashboard_nightly_repair
-- ----------------------------
DROP EVENT IF EXISTS `ev_dashboard_nightly_repair`;
delimiter ;;
CREATE EVENT `ev_dashboard_nightly_repair`
ON SCHEDULE
EVERY '1' DAY STARTS '2026-03-23 01:30:00'
DO BEGIN
    CALL `sp_run_dashboard_refresh`(DATE_SUB(CURDATE(), INTERVAL 180 DAY), CURDATE());
    CALL sp_refresh_claim_audit_3y();
END
;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;
