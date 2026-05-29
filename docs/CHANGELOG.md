# Changelog

ฟีเจอร์ทั้งหมดที่เพิ่มในสาขา `claude/lucid-wozniak-Ogl8h` เรียงตามลำดับเวลา (ใหม่สุดอยู่บน)

---

## 🐛 แก้ปุ่ม Delete Account — modal ยืนยันไม่เด้งกลางจอ

modal ยืนยันลบบัญชี (Breeze Alpine `<x-modal>`) render ผิดที่ — เกาะด้านบน ทับฟอร์ม ไม่ลอยอยู่บนสุดอย่างที่ควร

- เปลี่ยนเป็น **SweetAlert2** (เด้งกลางจอ z-index สูง ตรงกับ confirm อื่น ๆ ทั้งแอป) + ช่องกรอกรหัสผ่านในตัว → ส่งผ่าน hidden field ไป `profile.destroy`
- กรอกรหัสผิด → เด้ง dialog ใหม่พร้อมข้อความ error อัตโนมัติ
- localize เป็น TH/EN (เดิมเป็นอังกฤษล้วน) — เพิ่ม key `app.profile.delete_*`
- ไม่ได้แตะ backend (`ProfileController::destroy`) — ลบได้อยู่แล้ว (FK cascade/null ครบ); ปัญหาคือฝั่ง modal ล้วน ๆ

---

## ✉️ เปิดใช้การยืนยันอีเมล (email verification)

SMTP พร้อมแล้ว เลยเปิด email verification — `User implements MustVerifyEmail` (โครง Breeze + หน้า `verify-email` แบบ branded มีอยู่แล้ว แค่เปิดสวิตช์)

- สมัครใหม่ → ส่งลิงก์ยืนยันอัตโนมัติ → ผู้ที่ยังไม่ยืนยันถูกพาไปหน้า "ยืนยันอีเมล" (กดส่งใหม่ได้)
- **ยกเว้น `/admin` จาก middleware `verified`** — แอดมินไม่ถูกกั้น และตอน deploy ยังเข้า `/admin/system` ไป migrate ได้ ไม่ล็อกตัวเอง
- Backfill `email_verified_at = now()` ให้ user เดิมทั้งหมด — ครู/ผู้ใช้เดิมไม่โดนล็อก
- เปลี่ยนอีเมลในโปรไฟล์ → ต้องยืนยันใหม่ (พฤติกรรมมาตรฐาน Breeze ที่เพิ่งมีผล)
- **ต้องรัน migrate** (backfill) ผ่าน `/admin/system`: **Pull → Migrate → Clear cache** (รีบ migrate หลัง pull เพื่อปลดล็อกผู้ใช้เดิม)

---

## 🧭 แยก dashboard: /admin = ภาพรวม platform, สถิติสินค้าไปอยู่กับ product hub

`/admin` เดิมโชว์สถิติของ Homework Scanner (ห้องเรียน / การส่ง / ส่งงานวันนี้ / ครูที่มีห้องเยอะสุด) ซึ่ง **ซ้ำกับหน้า product hub** `/admin/products/scanner` ที่มีอยู่แล้ว และผิดหลัก Platform vs Products ใน `docs/ADMIN.md`

- เอา 4 การ์ดเฉพาะ Scanner ออกจาก `/admin` — เหลือเฉพาะ **platform overview** (ผู้ใช้ / แอดมิน / workspaces / สมัครใหม่ / active / ระงับ / leads / กราฟสมัครใหม่ / ผู้ใช้ใหม่ล่าสุด)
- เพิ่มแถบ **"Products"** เป็นการ์ดลิงก์ไป hub ของแต่ละสินค้า (อ่านจาก `config/admin-products.php` → สินค้าใหม่โผล่อัตโนมัติ) แทนที่พาเนล "ครูที่มีห้องเยอะสุด"
- สถิติสินค้ายังดูครบที่ product hub เหมือนเดิม (ไม่ได้ลบข้อมูล แค่ย้ายที่แสดง) + ลด query บนหน้า platform
- เพิ่ม key `app.admin.products_empty` (TH + EN)

---

## 🔐 ระบบ Role & สิทธิ์ (RBAC) ที่ /admin

ยกระดับสิทธิ์แอดมินจาก `is_admin` (จริง/เท็จ) เป็น **RBAC เต็ม** — role ถือ permission รายฟีเจอร์ และสร้าง role เองได้

- **Permission catalog** `config/permissions.php` (14 สิทธิ์ จัดกลุ่ม users / roles / leads / workspaces / content / platform) + helper `App\Support\Permissions`
- **Role** (ตาราง `roles` แก้ได้ใน DB): seed 3 ตัว — **Super Admin** (ทุกสิทธิ์ `'*'`, ลบ/แก้สิทธิ์ไม่ได้), **Admin**, **Support** (เน้นอ่าน)
- ผู้ใช้มีคอลัมน์ `role_id` (null = ผู้ใช้ทั่วไป). **`is_admin` ยังอยู่** = "มี role หลังบ้าน" ของเดิมจึงไม่พัง; migration ย้ายแอดมินเดิมทั้งหมด → Super Admin
- **บังคับสิทธิ์จริง**: gate ต่อ permission (`AppServiceProvider`) + `can:` middleware ทุกกลุ่ม route + ซ่อนเมนู/ปุ่มตามสิทธิ์ของผู้ใช้
- **`/admin/users`**: เปลี่ยนปุ่ม "ตั้ง/ถอดแอดมิน" เป็น **เลือก role**, filter ตาม role, badge ชื่อ role
- **`/admin/roles`** (ใหม่): สร้าง / แก้ / ลบ role + ติ๊ก permission รายกลุ่ม
- กันล็อกเอาต์: เปลี่ยน role ตัวเองไม่ได้, ถอด Super Admin คนสุดท้ายไม่ได้, ลบ role ระบบไม่ได้, Super Admin สิทธิ์ครบเสมอ
- `ADMIN_EMAILS` และ `php artisan app:make-admin` กำหนด Super Admin ให้อัตโนมัติ
- เพิ่ม key `app.roles.*` (TH + EN) + `config/permissions.php`
- **ต้องรัน migrate**: 3 migrations (roles / role_id / seed+migrate) ผ่านปุ่มใน `/admin/system` แล้ว pull + clear cache

---

## 🆓 โหมดเปิดตัวฟรี (free launch mode) — ฟรีทุกคน + เพดานกันสแปม

เปิดให้ใช้งานฟรีช่วงแรกด้วยสวิตช์เดียว ปิด-เปิดได้จาก `/admin/site` โดยไม่ต้องรื้อระบบแพ็คเกจ/จ่ายเงินที่ทำไว้ (Phase 4)

- เพิ่ม `App\Services\Billing` + `config/billing.php` — flag `billing.free_mode` (ค่าเริ่มต้น = เปิด) และเพดานช่วงเปิดตัว (config + override ได้จาก `/admin/site`)
- **โหมดฟรี**: `PlanGate` ใช้ "เพดานช่วงเปิดตัว" ร่วมกันทุก workspace แทนลิมิตของแพ็คเกจ — ค่าเริ่มต้น **15 ห้อง / 5 สมาชิก / 80 นักเรียนต่อห้อง** (ปรับได้ที่ `/admin/site`)
- ซ่อนแถบ trial นับถอยหลัง + ปุ่มอัปเกรดที่ยังกดไม่ได้, หน้า `/plans` เปลี่ยนเป็น "ฟรีช่วงเปิดตัว" (โชว์เพดาน ซ่อน CTA จ่ายเงิน), หน้า workspace settings โชว์ badge "ฟรีช่วงเปิดตัว"
- ผู้ใช้ใหม่ช่วงโหมดฟรี: subscription เป็น Free/active (ไม่มี trial countdown)
- พอจะเริ่มเก็บเงิน: ตั้ง `billing.free_mode = 0` ที่ `/admin/site` → ลิมิต/แพ็คเกจ/trial กลับมาทำงานทันที (reversible)
- เพิ่ม key `app.plans.launch_limit_* / free_launch_* / unlimited` (TH + EN)
- มี Tailwind class ใหม่ + config ใหม่ → ตอน deploy ต้อง **pull + clear cache**

---

## 🎨 เปลี่ยน hero หน้า /scanner เป็นโทนสว่าง

- หน้า `/scanner` (dashboard) เดิม hero เป็นพื้นดำไล่เฉดน้ำเงิน (`slate-900 → brand-900`) — เป็นพื้นที่ "มืด" จุดเดียวในแอปที่เหลือเป็นโทนสว่างทั้งหมด
- เปลี่ยนเป็น **hero โทนสว่าง**: พื้นไล่เฉด `brand-50 → white` + เส้นกริดบาง ๆ (`bg-grid`) + วงกลมเบลอสีแบรนด์นุ่ม ๆ, eyebrow/ปุ่มใช้น้ำเงินแบรนด์, หัวข้อเป็น `slate-900`
- การ์ดสถิติใน hero เปลี่ยนจากกระจกใส (`bg-white/10` บนพื้นดำ) เป็นการ์ดขาวมีขอบ+เงา ให้อ่านได้บนพื้นสว่าง
- การ์ดห้องเรียนด้านล่างเป็นโทนสว่าง/สีแบรนด์อยู่แล้ว เลยคงไว้ — ตอนนี้ทั้งหน้ากลมกลืนเป็นโทนสว่างชุดเดียว
- ไม่แตะ logic/translation — เป็นการปรับ CSS ล้วน ๆ; มี Tailwind class ใหม่ → ตอน deploy ต้อง **pull + clear cache**

---

## 🏠 เพิ่มลิงก์กลับหน้าแรกเว็บไซต์ในเมนูแอดมิน

- หน้า `/admin` เดิมไม่มีทางกลับไปหน้าเว็บไซต์หลัก (`/`) — โลโก้ลิงก์ไป dashboard อย่างเดียว
- เพิ่มลิงก์ **"หน้าแรกเว็บไซต์"** ใน admin nav: เดสก์ท็อปเป็นปุ่มไอคอนบ้าน (มี tooltip + sr-only) ข้าง ๆ ชื่อผู้ใช้, มือถือเป็นลิงก์มีข้อความเต็มด้านบนสุดของเมนู — เปิดในแท็บเดิม (`route('home')`)
- ใช้ key เดิม `app.nav.website_home` (มีอยู่แล้วทั้ง TH + EN) ไม่ต้องเพิ่ม key ใหม่
- ทำไอคอนบ้านชุดเดียวกับ app nav (`layouts/navigation.blade.php`) เพื่อความสม่ำเสมอ
- มี Tailwind class ใหม่เล็กน้อย → ตอน deploy ต้อง **pull + clear cache**

---

## 🔎 ปรับหน้าแก้ไขเนื้อหาเว็บ (/admin/site) ให้หาฟิลด์ง่ายขึ้น

หน้า CMS เดิมเป็นฟอร์มยาวสกรอลล์เดียว ~12 หัวข้อ 80+ ฟิลด์ — หาอะไรทีต้องเลื่อนยาว

- เพิ่ม **เมนูหัวข้อแบบ sticky** ด้านซ้าย (เดสก์ท็อป) / ชิปเลื่อนแนวนอน (มือถือ) กดกระโดดไปแต่ละหัวข้อได้ พร้อม scrollspy ไฮไลต์หัวข้อที่กำลังดู
- เพิ่ม **ช่องค้นหาสด** — พิมพ์แล้วกรองทั้งฟิลด์และหัวข้อทันที (ค้นจาก label หรือ key เช่น `brand.phone`) มีข้อความ "ไม่พบ" เมื่อไม่ตรง
- ฟิลด์ที่ถูกกรองซ่อนด้วย `x-show` (display:none) — **ยังส่งค่าครบตอนกดบันทึก** ไม่หาย
- ขยาย layout เป็น 2 คอลัมน์ (max-w-6xl) คงแถบบันทึก sticky + flash เดิมไว้
- เพิ่ม key `app.cms.searchPlaceholder` / `sections` / `noResults` (TH + EN)
- มี Tailwind class ใหม่ → ตอน deploy ต้อง **pull + clear cache**

---

## 🧭 จัดระเบียบเมนูแอดมินด้านบน (ลดความแออัด)

- เมนูบนของ `/admin` เดิมยัด 8 แท็บไว้แถวเดียว ทำให้ตัวอักษรไทยตัดบรรทัดกลางคำ (เช่น "ภาพ/รวม", "ออกจาก/ระบบ")
- แยกเป็น **แท็บหลัก 5 อัน** (ภาพรวม · ข้อความติดต่อ · ผู้ใช้ · Workspaces · แก้ไขเว็บ) + เมนู **"เพิ่มเติม"** สำหรับเครื่องมือที่ใช้ไม่บ่อย (ความปลอดภัย · Audit Log · System) — ปุ่ม "เพิ่มเติม" ไฮไลต์เมื่ออยู่ในหน้าที่ซ่อนอยู่
- ใส่ `whitespace-nowrap` ทุก pill/ชื่อ/ปุ่ม กันตัดคำกลางคำ และเลื่อน breakpoint ของแถบเต็มเป็น `xl` (จอแคบกว่านั้นใช้เมนู hamburger เดิมที่โชว์ครบทุกแท็บ)
- เพิ่ม key `app.admin.nav_more` (TH "เพิ่มเติม" / EN "More")
- มี Tailwind class ใหม่ → ตอน deploy ต้อง **pull + clear cache**

---

## ⚙️ Pull เคลียร์ cache อัตโนมัติ + เสริมความแกร่งการอัปโหลดรูป

- ปุ่ม **Pull** ใน `/admin/system` รัน `optimize:clear` ให้อัตโนมัติหลัง pull สำเร็จ — โค้ด/วิว/lang ใหม่ live ทันทีไม่ต้องกดเคลียร์ cache แยก
- การอัปโหลดรูป (`/admin/site`): ตั้งชื่อ field แบบไม่มีจุด (validation `upload.*` ทำงานจริง), whitelist key จาก schema, และ **โชว์ error เป็น toast** ถ้าเขียนไฟล์ไม่ได้ (เช่นสิทธิ์โฟลเดอร์) แทนที่จะเงียบ/500

---

## 🖼️ อัปโหลดรูปพื้นหลัง (login + hero) จาก /admin/site

- เพิ่ม **อัปโหลดไฟล์รูป** ในส่วน "Hero / background images" ของ `/admin/site` (เดิมวาง URL ได้อย่างเดียว) — รองรับ JPG/PNG/WebP ไม่เกิน 5MB (ไม่รับ SVG), เก็บไฟล์ที่ `public/images/backgrounds/`
- เพิ่มช่อง **`hero_image.login`** สำหรับรูปพื้นหลังหน้า login โดยเฉพาะ — `guest.blade.php` อ่านจาก `setting('hero_image.login', config(...))` (DB override → config)
- มี preview รูปปัจจุบันข้างช่องอัปโหลด, log การแก้ผ่าน `AuditLog` (`site_settings.update`)
- `public/images/backgrounds/` ใส่ `.gitignore` (เป็นไฟล์ที่ผู้ใช้อัปโหลด ไม่ commit)

---

## 🔐 หน้า Login ดีไซน์ใหม่ (split-screen)

- `layouts/guest.blade.php` → **split-screen**: ซ้าย = พาเนลไล่เฉดสีน้ำเงินแบรนด์ + กริด/วงกลมตกแต่ง + หัวข้อ "ยินดีต้อนรับ" และจุดเด่นของระบบ, ขวา = ฟอร์ม (มีผลกับหน้า auth ทุกหน้าที่ใช้ layout นี้)
- `auth/login.blade.php` → หัวข้อชิดซ้าย + ช่องกรอกมีไอคอนนำหน้า (อีเมล/ชื่อ/รหัสผ่าน) — **คงโฟลว์ email-first 3 สเต็ปเดิมครบ** (identify → signin/signup) ไม่ได้เพิ่ม social login / username ปลอมตามรูปต้นแบบ
- เพิ่ม key `app.auth.brand_*` (TH + EN) สำหรับข้อความพาเนลซ้าย
- rebuild Vite assets (มี Tailwind class ใหม่) → ตอน deploy ต้อง **pull + clear cache**

---

## 🔙 ลิงก์กลับหน้าแรกเว็บไซต์จากในแอป

- เพิ่มลิงก์ **"หน้าแรกเว็บไซต์"** ในเมนูนำทางของแอป (`layouts/navigation.blade.php`) — มีทั้งในเมนูบัญชีผู้ใช้ (desktop) และเมนูมือถือ
- แก้ปัญหาหน้า `/scanner` (และหน้าอื่น ๆ ในแอป) ไม่มีทางกลับไปหน้าแรกของเว็บไซต์ (`/`)
- เพิ่ม key `app.nav.website_home` ใน `lang/th/app.php` + `lang/en/app.php`

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
