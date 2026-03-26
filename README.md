# SobmoeiHospital Service Dashboard

ระบบเว็บแดชบอร์ดสำหรับสรุปข้อมูลบริการโรงพยาบาล ใช้ติดตามภาพรวมผู้รับบริการ ตัวชี้วัดสำคัญ โรคที่พบบ่อย ข้อมูลประชากร และงานวิเคราะห์การเคลมในหน้าเดียว
##### หมายเหตุ1 พัฒนาบนฐาน HOSXP XE หากใน HOSXP V3 อาจต้องแก้ไข 01_hos_dashboard.sql เพิ่ม

### ภาพรวม Dashboard
![Dashboard Overview](https://github.com/golderboy/Hospital_Service_Dashboard/blob/main/docs/001.png)

### ตาราง Top 10 และข้อมูลวิเคราะห์
![Dashboard Top 10](https://github.com/golderboy/Hospital_Service_Dashboard/blob/main/docs/002.png)

### หน้าวิเคราะห์การเคลม
![Claim Analysis](https://github.com/golderboy/Hospital_Service_Dashboard/blob/main/docs/003.png)

> คู่มือฉบับเต็มอยู่ในโฟลเดอร์ `/doc`

---

## วัตถุประสงค์

โครงการนี้พัฒนาขึ้นเพื่อให้โรงพยาบาลสามารถดูข้อมูลสำคัญจากระบบบริการได้รวดเร็ว อ่านง่าย และใช้ประกอบการตัดสินใจได้จริง โดยมีเป้าหมายหลักดังนี้

- สรุปภาพรวมบริการ OPD / IPD / ER ในหน้าเดียว
- ลดเวลาการรวบรวมข้อมูลแบบ manual
- ช่วยติดตามสิทธิการรักษา การยืนยันตัวตน การนัด และการส่งต่อ
- แสดงโรคที่พบบ่อยและข้อมูลเชิงวิเคราะห์ในรูปแบบที่เข้าใจง่าย
- วางโครงสร้างระบบให้ดูแลและพัฒนาต่อได้

---

## ความสามารถหลักของระบบ

- Dashboard สรุปภาพรวมบริการรายวันและตามช่วงวันที่
- ตัวกรองข้อมูลตามวันที่ คลินิก กลุ่มสิทธิ และประเภทบริการ
- KPI Cards สำหรับตัวเลขสำคัญ
- ตาราง Top 10 โรค OPD / IPD / โรคเรื้อรัง
- ตารางวิเคราะห์ค่าใช้จ่ายและการยืนยันตัวตน
- หน้าวิเคราะห์การเคลมแบบสรุป
- Export ข้อมูลเป็น CSV / Excel

---

## เทคโนโลยีที่ใช้

- PHP 7.4
- Apache + mod_php
- MariaDB / MySQL
- Bootstrap
- jQuery / AJAX
- Highcharts JS

---

## คู่มือการติดตั้งอย่างย่อ

### 1) Clone โปรเจกต์จาก GitHub

```bash
git clone https://github.com/golderboy/Hospital_Service_Dashboard.git
cd Hospital_Service_Dashboard
```

### 2) สร้างไฟล์ .env
คัดลอกไฟล์ตัวอย่างแล้วแก้ไขค่าตามเครื่องจริง
```bash
cp .env.example .env
```
จากนั้นแก้ไขค่าใน .env ให้ตรงกับระบบของหน่วยงาน เช่น
- ค่าระบบแอปพลิเคชัน
- ข้อมูลเชื่อมต่อฐาน Dashboard
- ข้อมูลเชื่อมต่อฐาน HOSxP
- ค่าการ debug สำหรับเครื่องพัฒนา/เครื่องใช้งานจริง

### 3) เตรียมฐานข้อมูล
- สร้างฐานข้อมูลสำหรับระบบ Dashboard
- Import โครงสร้างฐานข้อมูล/ไฟล์ SQL ที่จำเป็นตามเอกสารใน /doc
- ตั้งค่าการเชื่อมต่อฐานข้อมูล HOSxP และฐาน Dashboard ให้ถูกต้อง
##### หมายเหตุ1 กรุณาทดสอบในฐานสำรอง Hosxp
##### หมายเหตุ2 ใน 01_hos_dashboard.sql เปลี่ยน hosxp.* เป็น ชื่อฐานข้อมูลท่าน.* และ hos_dashboard ในอยู่ในฐานเดียวกับ Hosxp
##### หมายเหตุ3 กำหนดสิทธิ ให้เข้าถึงฐานข้อมูล Hosxp แค่ SELECT

```bash
-- แก้ชื่อ user และรหัสผ่านเองก่อนรัน

CREATE USER IF NOT EXISTS 'HOS_DASHBOARD_WEB_USER'@'localhost'
IDENTIFIED BY 'HOS_DASHBOARD_WEB_PASSWORD';
GRANT SELECT ON `hos_dashboard`.* TO 'HOS_DASHBOARD_WEB_USER'@'localhost';

CREATE USER IF NOT EXISTS 'HOS_DASHBOARD_ADMIN_USER'@'localhost'
IDENTIFIED BY 'HOS_DASHBOARD_ADMIN_PASSWORD';
GRANT SELECT ON `hosxp`.* TO 'HOS_DASHBOARD_ADMIN_USER'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, CREATE VIEW, SHOW VIEW, EVENT, EXECUTE
ON `hos_dashboard`.* TO 'HOS_DASHBOARD_ADMIN_USER'@'localhost';

FLUSH PRIVILEGES;
```

### ภาพรวม DATA FLOW
![database_context](https://github.com/golderboy/Hospital_Service_Dashboard/blob/main/docs/004.png)
![relational_model](https://github.com/golderboy/Hospital_Service_Dashboard/blob/main/docs/005.png)
![data_flow](https://github.com/golderboy/Hospital_Service_Dashboard/blob/main/docs/006.png)

### 4) ตั้งค่า Apache
ใช้งานบนดีบน Apache + mod_php 7.4 บน Linux
ตรวจสอบให้เครื่องมีอย่างน้อย
- Apache
- PHP 7.4+
- MariaDB / MySQL extension สำหรับ PHP
- mod_rewrite
- สิทธิ์อ่านไฟล์โปรเจกต์ครบถ้วน

จากนั้นชี้ DocumentRoot ไปยังโฟลเดอร์เว็บของโปรเจกต์ตามโครงสร้างที่ติดตั้งจริง และเปิดใช้งาน .htaccess

### 5) ทดสอบการใช้งาน
เปิดผ่านเว็บเบราว์เซอร์ แล้วตรวจสอบว่า
- หน้า Dashboard เปิดได้
- ตัวกรองใช้งานได้
- ข้อมูลแสดงผลได้ถูกต้อง
- Export CSV / Excel ใช้งานได้
- เวลาอัปเดตข้อมูลแสดงผลตามปกติ
- โครงสร้างเอกสาร

## เอกสารฉบับเต็มและรายละเอียดเพิ่มเติมอยู่ในโฟลเดอร์ /doc เช่น
- คู่มือการติดตั้งแบบละเอียด
- สเปคแนะนำของระบบ
- คู่มือการตั้งค่า .env
- คู่มือการใช้งานสำหรับผู้ดูแลระบบ

## หมายเหตุสำคัญ
โปรเจกต์นี้ตั้งใจให้ใช้งานภายในหน่วยงาน
ก่อนใช้งานจริงควรทดสอบกับฐานข้อมูลสำเนาหรือเครื่องพัฒนาก่อนเสมอ
