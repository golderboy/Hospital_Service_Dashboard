# SECURITY NOTES

เอกสารสั้นสำหรับทีม dev / infra

## เป้าหมาย
ให้เว็บ dashboard อ่านข้อมูลได้จากฐาน `hos_dashboard` โดยไม่แตะ schema ของ `hosxp`

## Minimum baseline
- บังคับใช้ HTTPS ใน production
- Web user ใช้สิทธิ์อ่านอย่างเดียว
- Web app ต้องไม่เชื่อมต่อด้วย root
- จำกัด network access เฉพาะ internal zone ถ้าทำได้
- Backup เฉพาะ `hos_dashboard` scripts/config และ source code
- ห้ามวางไฟล์ config ไว้ใน path ที่เสิร์ฟตรงได้

## Suggested deployment
- Nginx/Apache reverse proxy + PHP-FPM
- MariaDB local socket หรือ localhost only
- Firewall เปิดแค่ 80/443/22 ตามจำเป็น
- Fail2ban/EDR ตามนโยบายหน่วยงาน

## Header baseline
- CSP
- HSTS
- X-Frame-Options DENY
- X-Content-Type-Options nosniff
- Referrer-Policy strict-origin-when-cross-origin

## DB baseline
- `hos_dashboard_web`: SELECT on `hos_dashboard` only
- `hos_dashboard_admin`: SELECT on `hosxp`, CREATE/ALTER/DROP on `hos_dashboard`

