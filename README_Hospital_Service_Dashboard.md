# Hospital Service Dashboard

เอกสารนี้เป็น README สำหรับทีมพัฒนา เพื่อใช้เป็นฐานในการสร้างระบบ **Hospital Service Dashboard** จากฐานข้อมูล HOSxP / MariaDB ตามกติกาธุรกิจที่ตกลงร่วมกัน

---

## 1) เป้าหมายระบบ

พัฒนาเว็บ Dashboard สำหรับสรุปข้อมูลบริการโรงพยาบาล เพื่อให้ผู้บริหารและหน่วยงานที่เกี่ยวข้องใช้ติดตามภาพรวมบริการได้จากหน้าเดียว โดยเน้นข้อมูลต่อไปนี้

- ภาพรวมบริการ OPD / IPD / ER
- สิทธิการรักษา
- การยืนยันตัวตนสิทธิ
- รายได้นอก IPD
- นัดหมายและการมาตรงนัด
- ผู้ป่วยส่งต่อ Refer Out
- โรคที่พบบ่อย
- โรคเรื้อรังตาม ICD10
- ข้อมูลประชากรในเขตรับผิดชอบ

ระบบต้องรองรับการใช้งานภายในองค์กร, โหลดไว, ขยายต่อได้ และแก้ logic ได้ง่ายภายหลัง

---

## 2) เทคโนโลยีที่ใช้

### Server
- OS: AlmaLinux 9.7
- Database: MariaDB 10.x
- Backend: PHP 8+
- Web Server: Apache หรือ Nginx

### Frontend
- Bootstrap 5
- jQuery
- Highcharts JS

### แนวทางที่แนะนำ
- ใช้ PHP + PDO
- แยก API endpoint คืนค่า JSON
- หน้าเว็บเรียกข้อมูลผ่าน AJAX
- แยก business logic ออกจาก SQL ดิบ
- แยก mapping ไว้ใน config

---

## 3) โครงสร้างโปรเจกต์ที่แนะนำ

```text
/project-root
│
├── public/
│   ├── index.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│
├── app/
│   ├── config/
│   │   ├── database.php
│   │   ├── app.php
│   │   ├── clinic_map.php
│   │   ├── rights_map.php
│   │   └── chronic_icd10_map.php
│   │
│   ├── controllers/
│   │   ├── DashboardController.php
│   │   └── PopulationController.php
│   │
│   ├── services/
│   │   ├── DashboardService.php
│   │   ├── DiseaseService.php
│   │   └── PopulationService.php
│   │
│   ├── repositories/
│   │   ├── VisitRepository.php
│   │   ├── ChargeRepository.php
│   │   ├── DiseaseRepository.php
│   │   ├── AppointmentRepository.php
│   │   ├── ReferRepository.php
│   │   └── PopulationRepository.php
│   │
│   └── helpers/
│       ├── FilterHelper.php
│       ├── DateHelper.php
│       └── ResponseHelper.php
│
├── api/
│   ├── dashboard_summary.php
│   ├── dashboard_charts.php
│   ├── dashboard_tables.php
│   └── population.php
│
├── storage/
│   ├── logs/
│   └── cache/
│
└── README.md
```

---

## 4) แนวคิดสถาปัตยกรรม

ระบบควรแยกเป็น 4 ชั้น

### 4.1 Presentation Layer
รับผิดชอบการแสดงผลหน้า Dashboard
- Filter bar
- KPI cards
- Charts
- Tables
- Last updated

### 4.2 API Layer
PHP endpoint สำหรับส่ง JSON ให้หน้าเว็บ เช่น
- `/api/dashboard_summary.php`
- `/api/dashboard_charts.php`
- `/api/dashboard_tables.php`
- `/api/population.php`

### 4.3 Service / Business Logic Layer
รวมกติกาธุรกิจทั้งหมด เช่น
- การนิยาม OPD/IPD/ER
- การ map สิทธิการรักษา
- การคำนวณ verified / not verified
- การกรองข้อมูลที่ไม่ต้องการ
- การคำนวณ Today cards

### 4.4 Data Access Layer
รวม SQL query / repository สำหรับเข้าถึงฐานข้อมูล
- Summary query
- Chart query
- Disease query
- Population query

---

## 5) หลักข้อมูลกลางที่ควรมี

ไม่ควรให้ทุก widget query ตารางดิบกระจัดกระจายเอง เพราะจะเกิดปัญหา
- นับซ้ำ
- ใช้ date field คนละตัว
- logic ไม่ตรงกัน
- แก้ยาก

### ข้อแนะนำ
ควรมีข้อมูลกลางระดับ **1 แถวต่อ 1 visit** เช่น view หรือ query กลางชื่อประมาณ
- `vw_dashboard_visit_base`

### ฟิลด์หลักที่ควรมี
- `vn`
- `hn`
- `vstdate`
- `vsttime`
- `an`
- `patient_type` (OPD / IPD / ER)
- `main_dep`
- `department_name`
- `pttype`
- `hipdata_code`
- `auth_code`
- `has_referout`
- `income_opd`
- `main_pdx`
- `sex`
- `age_y`

### กติกาหลัก
- OPD = visit ที่ไม่มี AN
- IPD = visit/admission ที่มี AN
- ER = visit ที่ `main_dep IN er`
- ถ้า visit OPD แล้ว admit วันเดียวกัน ให้ถือเป็น IPD ทันที
- IPD ใช้ `ipt.regdate` เป็น date field
- สิทธิใช้ `pttype.hipdata_code`
- auth code ใช้ `visit_pttype.auth_code`

---

## 6) Filter ของระบบ

### มี filter หลัก 4 ตัว
- Start Date
- End Date
- Clinic
- Treatment Rights / Insurance Scheme
- Patient Type

### 6.1 Date Filter
ใช้กับข้อมูลทั่วไปทั้งหมด ยกเว้น
- Today cards
- Population cards

### 6.2 Clinic
- ใช้ค่า `ovst.main_dep`
- แสดงชื่อจาก `kskdepartment.department`

### 6.3 Treatment Rights / Insurance Scheme
- ใช้ `pttype.hipdata_code`

### 6.4 Patient Type
รองรับ 3 ค่า
- OPD
- IPD
- ER

### 6.5 Today Cards
ไม่โดน date filter
ใช้ `CURDATE()` เท่านั้น

### 6.6 Population Cards
ไม่โดน filter ใด ๆ

---

## 7) KPI ทั้งหมด

### 7.1 Total Patients
- หน่วยนับ: visit
- ใช้ช่วงวันที่ตาม filter

### 7.2 OPD Patients
- หน่วยนับ: จำนวนครั้ง
- เฉพาะ visit ที่ไม่มี AN
- ถ้า admit วันเดียวกัน ให้ถือเป็น IPD

### 7.3 IPD Patients
- หน่วยนับ: AN
- ใช้ `ipt.regdate` เป็น field วันที่

### 7.4 Identity-Verified
- นับ `distinct vn`
- จาก `visit_pttype.auth_code <> ''`

### 7.5 Identity-NOT-Verified
- นับ `distinct vn`
- จาก `visit_pttype.auth_code = '' OR is null`

### 7.6 Total Service Charges
- ใช้ OPD เท่านั้น
- ใช้ `sum(opitemrece.sum_price)`
- อิงช่วงวันที่จาก `ovst.vstdate`

### 7.7 Patients with Appointments
- นัดวันนี้เท่านั้น
- ไม่นับตามตัวกรอง
- นับจำนวนรายการนัด
- จาก `oapp.nextdate = CURDATE()`

### 7.8 Patients with Appointments Verified
- นัดวันนี้เท่านั้น
- ไม่นับตามตัวกรอง
- นับจำนวนรายการนัด
- เงื่อนไข `oapp.vn = oapp.visit_vn`

### 7.9 Emergency Patients Today
- ใช้ `main_dep IN er`
- วันที่ = `CURDATE()`

### 7.10 Referred Patients Today
- refer out เท่านั้น
- ใช้ตาราง `referout`
- เงื่อนไข `refer_date = CURDATE()`

### 7.11 IPD Admissions Today
- `count(an)`
- จาก `ipt`
- เงื่อนไข `dchdate IS NULL AND regdate = CURDATE()`

### 7.12 IPD Admissions
- `count(an)`
- จาก `ipt`
- เงื่อนไข `dchdate IS NULL`

---

## 8) Population Cards

### 8.1 ประชากรในเขต
```sql
SELECT COUNT(DISTINCT cid)
FROM person
WHERE house_regist_type_id IN (1,3)
  AND death != 'Y'
  AND person_discharge_id = '9';
```

### 8.2 ประชากรในเขต คนไทย
```sql
SELECT COUNT(DISTINCT cid)
FROM person
WHERE house_regist_type_id IN (1,3)
  AND death != 'Y'
  AND person_discharge_id = '9'
  AND nationality IN (99);
```

### 8.3 ประชากรทั้งหมด
ใช้จากไฟล์สถิติภายนอก
- ไม่ดึงจาก query หลัก
- ควรเก็บเป็น table import หรือ config แยก

---

## 9) สิทธิการรักษา

ใช้ `pttype.hipdata_code`

### mapping ที่แสดงบน dashboard
- `UCS + WEL => UCS`
- `OFC + LGO => OFC`
- `SSS => SSS`
- อื่น ๆ ทั้งหมด => Others

### รายการ hipdata_code ที่พบ
- BKK
- XXX
- NRH
- NRD
- PVT
- WEL
- BFC
- LGO
- OFC
- UCS
- DIS
- PTY
- CSH
- A2
- INS
- AH
- STP
- A8
- NHS
- SSS
- BMT
- SSI
- KKT
- SRT

### หลักใช้งาน
บน dashboard แสดงเพียง 4 กลุ่ม
- UCS
- OFC
- SSS
- Others

---

## 10) Clinic และ Depcode พิเศษ

### Clinic หลัก
ใช้ `ovst.main_dep` สำหรับ
- Filter
- Chart Patients by Clinic

### ชุด depcode พิเศษ
ใช้กับ logic เฉพาะบาง KPI

```php
$depcode = ['057'];
$screen = ['057','060'];
$doctor = ['001','004'];
$er = ['002','003','087'];
$dent = ['013'];
$thaimed = ['031'];
$DMHT = ['032','033','008','010','070','071','089'];
```

---

## 11) Disease Logic

### 11.1 Top 10 Common Diseases
ใช้ตาราง `ovstdiag`

กติกา
- ใช้ทุก `diag_type`
- หน่วยนับ = จำนวนแถว diagnosis
- กรองตามช่วงวันที่ที่กำหนด
- วันที่อ้างอิงจาก visit หลัก เช่น `ovst.vstdate`

ผลลัพธ์ที่ต้องแสดง
- ICD10
- ชื่อโรคจาก `icd101` ถ้ามี
- จำนวนครั้ง
- ร้อยละ

### 11.2 Top 10 Chronic Diseases
ใช้ `ovstdiag.icd10`

กติกา
- หน่วยนับ = จำนวนแถว diagnosis
- แสดงผลเป็นราย ICD10
- จำกัดเฉพาะรหัสดังนี้

#### กลุ่มโรคที่ใช้คัดกรอง
- เบาหวาน = `E10-E14`
- ความดัน = `I10-I15`
- หัวใจขาดเลือด = `I20-I25`
- COPD = `J41-J44`
- Stroke = `I60-I64`

หมายเหตุ: ตารางผลลัพธ์ให้แสดงเป็นรหัสโรครายตัว ไม่ใช่ชื่อกลุ่มโรค

---

## 12) Exclusion Rule

### 12.1 Test Patient
ตัดข้อมูลที่เข้าลักษณะทดสอบ เช่น
- ชื่อมีคำว่า `test`
- ชื่อมีคำว่า `ทดสอบ`
- ชื่อมีคำว่า `demo`
- HN/CID dummy เช่น `111111111`, `999999999`

### 12.2 Visit ยกเลิก
ตัด visit ที่มีใน `ovst_cancel`
- เงื่อนไข `ovst.vn = ovst_cancel.vn`

### 12.3 Duplicate / Merge HN
- ยังไม่มี flag ใช้งานจริง
- version แรกยังไม่ใช้ logic พิเศษ

### 12.4 HN ไม่สมบูรณ์
- ยังไม่มี flag ใช้งานจริง
- version แรกยังไม่ใช้ logic พิเศษ

### 12.5 dead / จำหน่าย / ย้าย
- ใช้เฉพาะ population
- ไม่ใช้ตัด visit dashboard

---

## 13) Chart / Table ที่ควรมี

### Charts
1. OPD vs IPD Distribution
2. Patients by Treatment Rights / Insurance Scheme
3. Patients by Clinic
4. Visit Trend รายวันหรือรายเดือน
5. Verified vs Not Verified

### Tables
1. Top 10 Common Diseases
2. Top 10 Chronic Diseases
3. Clinic Summary
4. Rights Summary

---

## 14) กติกาการเทียบค่า

### Today Cards
- เทียบกับวันก่อนหน้า

### Non-Today Cards
- ไม่ต้องเทียบช่วงก่อนหน้า

---

## 15) การอัปเดตข้อมูล

- Refresh ทุก 30 นาที
- `Data Last Updated` ใช้เวลารัน query ล่าสุด

### คำแนะนำด้าน performance
ไม่ควร query ทุก widget จากตารางดิบแบบ realtime ตลอด

แนวทางที่ควรเลือกอย่างใดอย่างหนึ่ง
- cache JSON 30 นาที
- pre-aggregate table
- summary table + cron refresh

### ข้อแนะนำเชิงปฏิบัติ
ระยะเริ่มต้นทำแบบ realtime + cache ก่อน
ถ้าข้อมูลเยอะขึ้นค่อยเปลี่ยนเป็น summary table

---

## 16) API ที่ควรมี

### `/api/dashboard_summary.php`
ส่งข้อมูล KPI cards ทั้งหมด

### `/api/dashboard_charts.php`
ส่งข้อมูล chart
- opd_ipd_distribution
- rights_distribution
- clinic_distribution
- visit_trend
- identity_distribution

### `/api/dashboard_tables.php`
ส่งข้อมูลตาราง
- common_disease_top10
- chronic_disease_top10
- clinic_summary
- rights_summary

### `/api/population.php`
ส่งข้อมูลประชากร

---

## 17) ตัวอย่าง Response JSON

```json
{
  "status": true,
  "data": {
    "total_patients": 12450,
    "opd_patients": 11800,
    "ipd_patients": 650,
    "identity_verified": 11200,
    "identity_not_verified": 1250,
    "service_charges": 2450000,
    "appointments_today": 180,
    "appointments_verified_today": 121,
    "er_today": 56,
    "referred_today": 8,
    "ipd_admit_today": 23,
    "ipd_admit_open": 112,
    "last_updated": "2026-03-23 10:30:00"
  }
}
```

---

## 18) Index ที่ควรตรวจสอบใน DB

อย่างน้อยควรมีหรือควรเช็ค index ตามนี้
- `ovst.vn`
- `ovst.hn`
- `ovst.vstdate`
- `ovst.main_dep`
- `ipt.vn`
- `ipt.an`
- `ipt.regdate`
- `ipt.dchdate`
- `opitemrece.vn`
- `oapp.nextdate`
- `visit_pttype.vn`
- `referout.refer_date`
- `ovstdiag.vn`
- `ovstdiag.icd10`

ก่อนแก้ performance ให้ดู execution plan ก่อน

---

## 19) ความเสี่ยงที่ต้องระวัง

1. Join one-to-many แล้วนับซ้ำ
2. ใช้ date field ผิดระหว่าง OPD กับ IPD
3. Today cards โดน filter โดยไม่ตั้งใจ
4. Disease count ซ้ำเพราะ join หลายชั้น
5. สิทธิรักษาและ auth code ปน logic กัน
6. Total charges เผลอรวม IPD
7. Refer out ใช้วันผิด field
8. Population cards โดน filter ทั้งที่ไม่ควร

---

## 20) ลำดับการพัฒนา

### Phase 1
- วางโครงสร้างโปรเจกต์
- ทำ database config
- ทำ query กลาง / repository
- ทำ summary API
- ทำหน้า dashboard เบื้องต้น
- ทำ KPI cards และ population cards

### Phase 2
- ทำ charts
- ทำ tables
- ทำ filter logic
- ทำ mapping rights / chronic ICD10

### Phase 3
- ทำ cache 30 นาที
- ปรับ performance
- ทำ export CSV / Excel
- เตรียม drill-down
- เก็บ log การใช้งาน

---

## 21) ข้อเสนอแนะสำหรับการเริ่มเขียนโปรแกรม

เริ่มจากลำดับนี้
1. ตั้งค่า DB connection และ helper กลาง
2. ทำ config mapping
3. ทำ repository สำหรับ summary KPI ก่อน
4. ทำ API summary ให้เลขออกตรง
5. ทำหน้า dashboard card ให้ครบ
6. ค่อยทำกราฟและตาราง
7. สุดท้ายค่อยทำ cache และ optimization

### สิ่งที่ไม่ควรทำ
- อย่าเขียน SQL ซ้ำหลายที่
- อย่า hard-code mapping กระจายหลายไฟล์
- อย่าเอา logic Today ปนกับ filter ปกติ
- อย่า join ตารางมากเกินจำเป็นใน query เดียว

---

## 22) ไฟล์ config ที่ควรมี

### `rights_map.php`
ใช้ map `hipdata_code` ไปเป็นกลุ่ม dashboard

### `clinic_map.php`
ใช้เก็บ depcode พิเศษ เช่น ER, DMHT, ThaiMed

### `chronic_icd10_map.php`
ใช้เก็บช่วงรหัสโรคเรื้อรัง

---

## 23) งานที่พร้อมเริ่มได้ทันที

- สร้างโครงโปรเจกต์ PHP
- สร้าง config database
- สร้าง rights_map / clinic_map / chronic_icd10_map
- สร้าง repository summary
- สร้าง API `/api/dashboard_summary.php`
- สร้างหน้า `public/index.php`
- วาง Bootstrap layout ตาม mockup

---

## 24) หมายเหตุสำคัญ

version แรกควรเน้นให้
- ตัวเลขถูกก่อน
- logic ตรงก่อน
- query ไม่หนักเกินก่อน

เรื่องสวยงาม, animation, export, drill-down, permission ค่อยต่อยอดภายหลังได้

> หลักที่ต้องยึด: **เลขต้องตรงก่อน แล้วค่อยแต่งหน้าเว็บ**

