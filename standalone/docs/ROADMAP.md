# Ledgerly — Roadmap

แผนแยกระบบบัญชีออกมาเป็นผลิตภัณฑ์ SaaS ขายแยก (standalone, host เอง, white-label)

- **โค้ดอยู่ใน:** `standalone/` (ปัจจุบันแชร์ `vendor/` + `node_modules/` กับ repo แม่ผ่าน gitignore/symlink)
- **branch:** `claude/gifted-mendel-wVTeq`
- **สถานะรวม:** เครื่องยนต์บัญชีพร้อม · เปลือก/การขายยังไม่ครบ
- **เทสต์:** 102 passed, 358 assertions (sqlite :memory:)
- **อัปเดตล่าสุด:** 2026-05-31

> ⚠️ กฎ: รัน `php artisan test` ใน `standalone/` ให้เขียวทุกครั้งก่อน commit · ทุก lang key ต้องมีทั้ง TH + EN · migration ต้องมี `down()`

---

## ความพร้อม (ประเมิน)

| ด้าน | % | สถานะ |
|---|---|---|
| เครื่องยนต์บัญชี (ledger, ใบกำกับ, AR/AP, ภาษี, WHT, งบ, dashboard) | 90 | ✅ |
| Auth + multi-tenant (workspace) + เปลือก Ledgerly | 85 | ✅ |
| แยกรัน standalone ได้เอง | 70 | ✅ รันได้ · เหลือลบ dead code, ย้าย repo จริง |
| ผังบัญชีกลาง (ไม่มีข้อมูลบริษัท) | 100 | ✅ |
| ลูกค้าแก้ผังบัญชีเอง | 100 | ✅ |
| Onboarding (เลือกผัง + ข้อมูลบริษัท) | 100 | ✅ |
| White-label (สี/โลโก้/ชื่อ) | 90 | ✅ ปรับผ่าน .env (config/brand.php) · เหลือลบ lang/site.php เก่า |
| Billing เก็บเงินรายเดือน | 60 | ⏳ แพ็คเกจ/เลือกแผน/หน้า billing พร้อม · เหลือต่อ payment gateway |
| Landing + pricing | 10 | ⏳ |
| Infra (domain/hosting/email/backup) | 0 | ⏳ |
| กฎหมาย (PDPA, terms/privacy) | 0 | ⏳ ลิงก์เป็น `#` ชั่วคราว |
| **พร้อมขาย SaaS รวม** | **~72** | |

---

## ✅ ทำเสร็จแล้ว

- ก๊อปแอปเข้า `standalone/` รันได้เอง — engine บัญชีย้ายมาโดยไม่แก้โค้ด
- ตั้งชื่อผลิตภัณฑ์ `Ledgerly` (placeholder · เปลี่ยนที่ `APP_NAME`)
- ตัดเปลือกเหลือบัญชีล้วน — routes/nav/layout/provider ใหม่ · ลบเมนู queue/classroom/admin/plans/feedback
- ผังบัญชีกลาง SME ไทย (`ChartOfAccounts::STANDARD_TH`) — เอาเลขบัญชีธนาคารจริง + ปีภาษีเฉพาะออกหมด
- หน้าแก้ผังบัญชีเอง (`/accounting/accounts`) — เพิ่ม/แก้/ปิด/ลบ + กัน 2 ชั้น (บัญชีระบบ / บัญชีมีรายการแล้ว)
- Onboarding wizard (`/accounting/onboarding`) — กรอกข้อมูลบริษัท (per-workspace) + เลือกผังบัญชี → seed ผัง/ภาษี/งวด → ลง `onboarded_at` · บริษัทขึ้นหัวเอกสารแล้ว (invoice/bill/50ทวิ ดึงจาก workspace)
- White-label — `config/brand.php` (ชื่อ/สี/โลโก้/tagline ผ่าน .env) · `<x-brand-logo>` (โลโก้รูป หรือ monogram) · theme-color + ปุ่ม SweetAlert ใช้สีแบรนด์ · ลบ Tirmongkol ออกจาก live path · auth brand copy เป็นเรื่องบัญชีแล้ว

---

## ⏳ ต้องทำต่อ (เรียงตามลำดับแนะนำ)

### 1. Billing — เก็บเงินรายเดือน (ทำพื้นฐานแล้ว)
- [x] แพ็กเกจ accounting (`config/plans.php`: Free/Pro/Business) + limit `max_members`/`max_invoices_per_month`
- [x] `Subscription` ต่อยอด (trial → active, trial หมดอายุ fallback Free อัตโนมัติ)
- [x] หน้า `/billing` — การ์ดราคา + เลือกแผน (owner เท่านั้น) + badge ทดลองใช้ · `BillingTest` 8 เทสต์
- [ ] ต่อ payment gateway (Stripe/Omise) — checkout redirect + webhook → flip เป็น active (ตอนนี้เลือกแผนแล้ว "ทีมงานติดต่อ")
- [ ] กันใช้งานเมื่อ trial หมด/ค้างจ่าย — บังคับ limit `max_invoices_per_month` ใน SalesInvoicing
- [ ] (option) slip โอนเอง — มี `SlipVerifier`/`PromptPay` ใน `app/Services/Payments/` ให้ต่อ

### 2. Landing + pricing
- [ ] หน้าแลนดิ้งสาธารณะ (ตอนนี้ `/` redirect เข้า login เลย)
- [ ] หน้าราคา · ปุ่มสมัคร

### 3. ลบ dead code (cleanup — ทำต่อก่อน production)
ลบรอบแรกแล้ว (105 ไฟล์: controllers/views/services ที่ไม่มี route) — เหลือ cluster ที่ยังพันกับ service ที่เก็บไว้
- [x] controllers: `Admin/`, `Queue*`, `Classroom`, `Attendance`, `Gradebook`, `Student`, `Submission`, `Assignment`, `Plans`, `Page`, `Contact`, `Feedback`, `PublicQueue`, `SiteSettings`
- [x] views: `admin/`, `queues/`, `classrooms/`, `attendance/`, `gradebook/`, `students/`, `assignments/`, `pages/`, `plans/` + dead partials/components/layouts
- [x] services: `Gradebook`, `ClassInsights`, `AttendanceSummary`, `Tts`, `QueuePlan`, `ProductGate`, `DemoWorkspaceSeeder`, `Notifications/`
- [x] ตัด `classrooms()` relation ออกจาก `User`/`Workspace`
- [ ] models cluster ที่ยังเหลือ: `Classroom/Student/Assignment/Submission/Attendance*/Queue*/Lead/AdminAction` — ยังพันกับ `PlanGate` (Classroom), `AuditLog` (AdminAction), `SlipVerifier` (Queue*) ที่เก็บไว้เผื่อ billing → ตัดสินใจตอนทำ billing ว่าจะ refactor service พวกนี้แล้วลบ model
- [ ] migrations: 35/50 ไม่ใช่บัญชี — เก็บไว้ก่อน (กระทบ schema ตอน fresh install) ลบพร้อม model cluster
- [ ] lang: บล็อกที่ไม่ใช่บัญชี (`queue`, `classrooms`, `admin`, `plans`, `feedback`, ...) ใน `lang/*/app.php` + `lang/*/site.php`
- [x] เทสต์ยังเขียว 110 หลังลบ

### 4. ย้ายเข้า repo จริง + deploy
- [ ] สร้าง GitHub repo ใหม่สำหรับ Ledgerly (ผมสร้างจาก session นี้ไม่ได้ — ถูกล็อกที่ repo เดียว) แล้วย้าย `standalone/` ไปเป็น root
- [ ] `composer install` + `npm install` เป็นของตัวเอง (เลิกแชร์ vendor/node_modules กับ repo แม่)
- [ ] domain / hosting / อีเมล (สมัคร/รีเซ็ตรหัส) / backup ฐานข้อมูล / CI

---

## 📌 หมายเหตุทางเทคนิค

- **system_role 15 ตัวห้ามหาย** — engine ผูกบัญชีผ่าน `system_role`: `ar_control, ap_control, vat_input, vat_input_deferred, vat_input_pending, vat_output, vat_output_deferred, wht_payable_pnd3, wht_payable_pnd53, wht_receivable, capital, retained_earnings, service_revenue, director_loan` ทุกเทมเพลตผังบัญชีต้องมีครบ
- **เงิน = integer สตางค์** ผ่าน `Money` เท่านั้น (ห้าม float/bcmath)
- **ledger immutable** — journal ที่ post แล้วแก้ไม่ได้ ใช้ reverse
- **assets ต้อง rebuild** เมื่อเพิ่ม Tailwind class ใหม่: `npm run build` ใน `standalone/` (Tailwind v4 scan `@source '../views/**/*.blade.php'` — ต้องรันจาก standalone) แล้ว commit `public/build/`
- **vendor/node_modules** ถูก gitignore — standalone symlink ไป repo แม่ (`ln -s ../node_modules standalone/node_modules`) ตอนย้าย repo จริงต้องติดตั้งเอง
