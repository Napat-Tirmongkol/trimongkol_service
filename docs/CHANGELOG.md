# Changelog

ฟีเจอร์ทั้งหมดที่เพิ่มในสาขา `claude/lucid-wozniak-Ogl8h` เรียงตามลำดับเวลา

---

## 🎨 SweetAlert2 — replaced native confirm/alert

ทุก `window.confirm()` ในระบบถูกแทนด้วย SweetAlert2 modal และ flash message ของ Laravel
(`session('status')`, `session('error')`) แสดงเป็น toast มุมขวาบนอัตโนมัติ

**Usage:**

```html
<!-- confirm dialog on form submit -->
<form method="POST" action="..." data-confirm="คุณแน่ใจ?" data-confirm-danger="1">
    @csrf @method('DELETE')
    <button type="submit">Delete</button>
</form>

<!-- toast from controller -->
return back()->with('status', 'บันทึกแล้ว');
return back()->with('error', 'เกิดข้อผิดพลาด');
```

**ไฟล์เกี่ยวข้อง:**
- `resources/views/partials/sweetalert.blade.php` — script + form interceptor + toast helper
- โหลดผ่าน CDN ใน `layouts/{app,admin,guest,marketing}.blade.php`
- ไม่ต้องรัน `npm install` / `npm run build`

---

## 🎭 Admin — Impersonate + Maintenance Mode

- **Impersonate**: admin login เป็น user อื่น เพื่อ debug — มี sticky banner สีเหลืองเตือน + ปุ่ม "กลับเป็น admin"
- **Maintenance mode**: เปิดผ่าน `/admin/site` → `maintenance.enabled` = `1` — non-admin เห็นหน้า 503, admin ผ่านได้
- ทั้ง start/stop ของ impersonate ถูก log ลง audit

---

## 🏫 Admin — Content Moderation

- `/admin/products/scanner/classrooms` — list ห้องเรียนทุกห้องในระบบ (admin only)
- หน้า detail แสดง assignments + Danger Zone ลบได้
- ค้นหาด้วยชื่อห้อง / รายละเอียด / อีเมลเจ้าของ

---

## 📝 Admin — Audit Log

- ตาราง `admin_actions` เก็บประวัติทุก privileged action
- `/admin/logs` — filterable table (who/what/when/where/IP/metadata)
- Actions ที่ log: `user.promote`, `user.demote`, `user.suspend`, `user.activate`, `user.delete`,
  `user.password_reset`, `user.impersonate_start`, `user.impersonate_stop`, `classroom.delete`

**Service:**
```php
use App\Services\AuditLog;
AuditLog::record('action.name', $targetModel, 'human-readable-label', ['extra' => 'metadata']);
```

---

## 📊 Admin — Dashboard Stats + Trend

หน้า `/admin` แสดง:
- กล่องสรุป 4 ตัว (users / admins / classrooms / submissions)
- กิจกรรม: สมัครใหม่ 7 วัน, active 30 วัน, ส่งงานวันนี้, บัญชีถูกระงับ
- Sparkline สมัครใหม่ราย 30 วัน (SVG inline)
- Top 5 ครูที่มีห้องเยอะสุด

---

## 👥 Admin — User Management

- `/admin/users` พร้อม filter: role / status / sort 4 แบบ
- `/admin/users/{id}` — detail page แสดงห้องเรียนที่ user มี + actions:
  - Suspend / Activate (toggle `is_active`)
  - Delete user (cascade ลบห้องทั้งหมดของ user นั้น)
  - Send password reset (Laravel `Password::sendResetLink`)
  - Login as user (impersonate)
- Export users → CSV

---

## 🔐 Admin Foundation

- คอลัมน์ `is_active`, `last_login_at` บนตาราง `users`
- ตาราง `admin_actions` สำหรับ audit log
- Middleware `BlockSuspendedUser` — kick user ที่ถูก suspend ออกทุก request
- Middleware `MaintenanceMode` — เปิดผ่าน site_settings
- Event listener `Login` → stamp `last_login_at` ทุกครั้งที่ user login

---

## 🗂️ Admin — Multi-Product Navigation

โครงสร้าง admin nav แบ่ง **Platform** vs **Products** เพื่อรองรับการขยายระบบในอนาคต

- Top bar: Platform tabs (ภาพรวม · ผู้ใช้ · Audit · CMS) + Products dropdown
- Second bar: product-specific tabs โผล่เมื่ออยู่ในหน้า product
- Config-driven: เพิ่ม product ใหม่ผ่าน `config/admin-products.php`

ดูรายละเอียดในการเพิ่ม product ใหม่ที่ `docs/ADMIN.md`

---

## 🖨️ Per-Student QR Print

- `/classrooms/{classroom}/students/{student}/qr` — หน้าพิมพ์ QR ใบใหญ่ใบเดียว
- ลิงก์เข้าจาก: หน้า detail นักเรียน, ตาราง roster ในห้อง
- Use case: นักเรียนทำบัตรหาย → ครูปริ้นใหม่เฉพาะคน ไม่ต้องสั่งพิมพ์ทั้งห้อง

---

## 📂 Assignment Category

- คอลัมน์ `category` บนตาราง `assignments` (6 หมวด: homework / classwork / quiz / exam / project / other)
- แสดงเป็น badge สีในรายการการบ้านและหน้า detail
- Blade component `<x-assignment-category-badge :category="..." />`
- เผื่อใช้ filter/group ในอนาคต (มี index `(classroom_id, category)`)
