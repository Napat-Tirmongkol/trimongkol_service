# Changelog

ฟีเจอร์ทั้งหมดที่เพิ่มในสาขา `claude/lucid-wozniak-Ogl8h` เรียงตามลำดับเวลา (ใหม่สุดอยู่บน)

---

## 💳 Subscription & Plan Foundation (Phase 4)

**Phase 4 ของ business model C** — โครงสร้าง subscription แบบ per-workspace

- ตาราง `subscriptions` (per workspace, 1:1)
- 3 plans ใน `config/plans.php`: **Free** (฿0) / **Basic** (฿199/เดือน) / **Pro** (฿499/เดือน)
- Limits:
  - **Free**: 3 ห้องเรียน, 1 สมาชิก, 50 นักเรียน/ห้อง
  - **Basic**: 20 ห้องเรียน, 5 สมาชิก, 200 นักเรียน/ห้อง
  - **Pro**: ไม่จำกัด
- **14-day Basic Trial** ทุก workspace ใหม่ → ถ้าหมด trial → effective plan = Free อัตโนมัติ (lazy check, ไม่ต้องรอ scheduler)
- Backfill migration: workspace ที่มีอยู่ → Free / active
- `App\Services\PlanGate` — return reason string ถ้าทำไม่ได้ (กดสร้างห้อง/เชิญสมาชิก/เพิ่มนักเรียนเกิน limit → redirect ไป `/plans` พร้อม toast แดง)
- หน้า `/plans` แสดง 3 plans เปรียบเทียบ + ปุ่ม "ติดต่อขออัปเกรด" (ไปหน้า `/contact` — บันทึกเป็นข้อความติดต่อ → admin เห็นใน `/admin/leads`)
- Trial banner แสดงทุกหน้า (สีเหลือง) เมื่ออยู่ใน trial
- Workspace settings แสดง plan ปัจจุบัน + trial days left
- Admin `/admin/workspaces/{id}` มี section "Plan / Subscription" — admin set plan ของ workspace ใดก็ได้ด้วยมือ (audit log `workspace.update_plan`)

**ยังไม่ทำใน Phase 4 (เก็บไว้ Phase 5):**
- Self-serve checkout
- Payment gateway integration (PromptPay / Omise / Stripe)
- Auto-billing / renewal
- Pro-rated upgrades / downgrades

---

## 🏢 Workspaces — Multi-Tenant Phase 3c (transfer + email invite + admin)

**Phase 3c — completing Phase 3:**
- **Transfer ownership**: owner เลือกสมาชิกแล้วโอน ownership ให้ ตัวเองลดลงเป็น admin (ใส่ password ยืนยัน)
- **Email invitations**: invite อีเมลที่ยังไม่มี user ในระบบ → สร้าง `workspace_invitations` token + ส่ง email อัตโนมัติ (ถ้า mailer ตั้งไว้ใน .env) หรือคัดลอกลิงก์ส่งเอง — token หมดอายุ 7 วัน
- Accept flow รองรับทั้ง guest (ไป login/register แล้วกลับมา auto) และ logged-in user
- **Admin Platform Workspaces view** ใน `/admin/workspaces` — list + detail (members, classrooms, pending invites) + delete (admin moderation)
- เพิ่ม Workspaces badge ใน admin user detail แสดงว่า user เป็นสมาชิก workspace ไหนบ้าง + role
- เพิ่ม "Workspaces" stat ใน admin platform dashboard

## 🏢 Workspaces — Multi-Tenant Foundation (Phase 3a + 3b)

**Phase 3 ของ business model C** — โครงสร้าง Workspace ใหม่เพื่อรองรับ SaaS

**Phase 3a — Foundation:**
- ตาราง `workspaces` + `workspace_members` (roles: owner/admin/member)
- เพิ่ม `workspace_id` ใน `classrooms`
- Backfill migration: user ทุกคนได้ workspace ของตัวเอง + classrooms ผูกเข้าไป
- Auto-create workspace สำหรับ user ใหม่ที่สมัคร (Registered event)
- `Classroom::canBeAccessedBy()` ตรวจสมาชิกใน workspace แทนการเช็ก `user_id`
- Service `CurrentWorkspace` (session-backed) + workspace switcher dropdown ใน nav
- หน้า `/workspaces` ดู workspaces ทั้งหมดของ user

**Phase 3b — Member management:**
- สร้าง workspace ใหม่ (`/workspaces/create`)
- ตั้งค่า workspace (`/workspaces/{id}/settings`): rename, ดู members, invite, delete
- Invite member ด้วย email (ต้องเป็น user ในระบบอยู่แล้ว — email-invite ทีหลัง)
- Remove member (owner/admin ทำได้ ยกเว้น owner)
- Leave workspace (member/admin ออกได้ owner ออกไม่ได้)
- Delete workspace (owner เท่านั้น ต้องใส่ password)
- ทุก action log ลง audit

**ยังไม่ได้ทำใน 3:**
- Transfer ownership
- Email invite สำหรับคนที่ยังไม่มีบัญชี
- Workspace-level admin (ตอนนี้ admin role ทำเหมือน owner ยกเว้น delete)

---

## 🔐 Two-Factor Authentication (TOTP)

**Phase 2.5 ของ business model C** — security ก่อนเปิดให้คนสมัครเอง

- คอลัมน์ `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` บน users (เข้ารหัสด้วย Laravel encrypted cast)
- `App\Services\Totp` — RFC 6238 TOTP implementation แบบ pure PHP (ไม่ต้องเพิ่ม composer dep)
- หน้า Profile มี section ใหม่: เปิด/ปิด 2FA, ดู QR, recovery codes
- Login challenge หลังกรอก password → ใส่รหัส 6 หลัก หรือ recovery code
- Middleware `RequireTwoFactor` บังคับ challenge ทุก request จนกว่าจะผ่าน (admin impersonate ผ่านได้โดยไม่ต้อง challenge)
- รองรับ Google Authenticator / Microsoft Authenticator / 1Password / ทุกแอปที่ใช้ otpauth://
- ทุก action ของ 2FA log ลง audit (enable / disable / regenerate / pass / fail / recovery_used)

---

## 🔒 Login Attempt Tracking + Security Dashboard

**Phase 2 ของ business model C** — security baseline ก่อนเปิดให้ user สมัครเอง

- ตาราง `login_attempts` เก็บทุก login (success + fail) พร้อม IP / user-agent
- Event listeners ใน `AppServiceProvider` (`Login` / `Failed`)
- หน้า `/admin/security` แสดง:
  - Failed/success count 24h + 7d
  - Fail/success ratio bar
  - Top 10 failing IPs (น่าจะเป็น brute-force probes)
  - Recent 50 attempts — filter เฉพาะ fail / success
- **ยังไม่มี 2FA** — เก็บไว้เป็น Phase 2.5 ถ้าจะทำต้อง add composer dep

---

## 📨 Lead Management — เก็บ + จัดการ contact form leads

**Phase 1 ของ business model C** — service business (consultancy)

- ตาราง `leads` (name/email/phone/company/message + status + assignee + notes + IP/UA)
- Contact form POST `/contact` เก็บลง DB (เคยแค่ redirect)
- หน้า `/admin/leads` พร้อม status pipeline: new → contacted → qualified → won/lost
- หน้า detail: เปลี่ยน status, assign admin คนหนึ่ง, ใส่ internal notes
- กล่อง "ลีดใหม่" ใน admin dashboard ลิงก์ไปหน้า new leads
- เปลี่ยน status / ลบ → audit log

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
