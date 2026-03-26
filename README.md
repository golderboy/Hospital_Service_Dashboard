# Hospital Service Dashboard

เว็บ Dashboard สำหรับสรุปข้อมูลบริการโรงพยาบาลจาก HOSxP/HOSxP V4 โดยใช้ PHP 7.4, Apache และ MariaDB/MySQL

## โครงสร้างหลัก
- `index.php` หน้า dashboard หลัก
- `api/` endpoint สำหรับ AJAX / export
- `app/` business logic, config และ support classes
- `sql/01_hos_dashboard.sql` schema + stored procedure + event
- `sql/02_VERIFY.sql` ชุดคำสั่งตรวจสอบหลังติดตั้ง
- `storage/` cache และ log runtime
- `assets/vendor/` library ที่ commit ไปพร้อม source code

## Quick start
1. clone หรือแตกไฟล์ project ลง web root
2. copy `.env.example` เป็น `.env`
3. แก้ค่าฐานข้อมูลและ `APP_BASE_URL`
4. import `sql/01_hos_dashboard.sql` ไปยังฐาน `hos_dashboard`
5. รัน `CALL sp_rebuild_last_5_years();` ครั้งแรก
6. ตรวจสิทธิ์เขียน `storage/cache`, `storage/logs`, `assets/tmp`
7. เปิดเว็บและตรวจ `sql/02_VERIFY.sql`

## GitHub push ครั้งแรก
ใช้ `scripts/git_push_main.sh` หรือรันคำสั่ง git เองตามคู่มือ

## หมายเหตุ
- ต้องไม่ commit `.env` จริงขึ้น GitHub
- แนะนำให้ user ที่เชื่อม `hosxp` เป็น read-only
- ควรเปิด HTTPS ใน production
