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
| Onboarding (เลือกผัง + ข้อมูลบริษัท) | 10 | ⏳ |
| White-label (สี/โลโก้/ชื่อ) | 20 | ⏳ เปลี่ยนชื่อแล้ว · สียังเป็น Tirmongkol |
| Billing เก็บเงินรายเดือน | 0 | ⏳ |
| Landing + pricing | 10 | ⏳ |
| Infra (domain/hosting/email/backup) | 0 | ⏳ |
| กฎหมาย (PDPA, terms/privacy) | 0 | ⏳ ลิงก์เป็น `#` ชั่วคราว |
| **พร้อมขาย SaaS รวม** | **~55** | |

---

## ✅ ทำเสร็จแล้ว

- ก๊อปแอปเข้า `standalone/` รันได้เอง — engine บัญชีย้ายมาโดยไม่แก้โค้ด
- ตั้งชื่อผลิตภัณฑ์ `Ledgerly` (placeholder · เปลี่ยนที่ `APP_NAME`)
- ตัดเปลือกเหลือบัญชีล้วน — routes/nav/layout/provider ใหม่ · ลบเมนู queue/classroom/admin/plans/feedback
- ผังบัญชีกลาง SME ไทย (`ChartOfAccounts::STANDARD_TH`) — เอาเลขบัญชีธนาคารจริง + ปีภาษีเฉพาะออกหมด
- หน้าแก้ผังบัญชีเอง (`/accounting/accounts`) — เพิ่ม/แก้/ปิด/ลบ + กัน 2 ชั้น (บัญชีระบบ / บัญชีมีรายการแล้ว)

---

## ⏳ ต้องทำต่อ (เรียงตามลำดับแนะนำ)

### 1. Onboarding wizard 🔥 (ด่านถัดไป)
ทำให้ลูกค้าใหม่เริ่มเองได้ตั้งแต่สมัคร
- [ ] หลังสมัคร → wizard: เลือกเทมเพลตผังบัญชี (`ChartOfAccounts::templates()` พร้อมแล้ว)
- [ ] กรอกข้อมูลบริษัท: ชื่อ, เลขผู้เสียภาษี, สาขา, ที่อยู่, โทร (ลงใน workspace/site setting — ใช้บนหัวเอกสาร)
- [ ] (ทางเลือก) ใส่ยอดยกมาเลย — ลิงก์ไปหน้า opening-balances ที่มีอยู่
- [ ] redirect เข้า dashboard เมื่อเสร็จ · ถ้ายังไม่ตั้งค่า ให้เด้ง wizard

### 2. White-label
- [ ] ย้ายสีแบรนด์ออกจาก hard-code → ปรับได้ผ่าน setting (`resources/css/app.css` มี `--color-brand-*`, gradient `#3366ff/#1936b8`)
- [ ] โลโก้อัปโหลดได้ (ตอนนี้ใช้ตัวอักษรแรกของ `APP_NAME`)
- [ ] เก็บกวาด `Tirmongkol` ที่เหลือใน: `config/site.php`, `lang/th/site.php`, `lang/en/site.php`
- [ ] ตัดสินใจชื่อจริง + เช็ค domain/trademark (placeholder = Ledgerly)

### 3. Billing — เก็บเงินรายเดือน
- [ ] เลือกวิธี: Stripe / Omise / โอนเอง+อนุมัติ (มี `SlipVerifier`/`PromptPay` เดิมใน repo แม่ให้ดูเป็นแนว)
- [ ] แพ็กเกจ/ราคา + ผูกกับ workspace (`Subscription` model เดิมยังอยู่ ใช้ต่อหรือเขียนใหม่)
- [ ] กันใช้งานเมื่อหมดอายุ/ยังไม่จ่าย (trial → active → past_due)

### 4. Landing + pricing
- [ ] หน้าแลนดิ้งสาธารณะ (ตอนนี้ `/` redirect เข้า login เลย)
- [ ] หน้าราคา · ปุ่มสมัคร

### 5. ลบ dead code (cleanup — ทำตอนไหนก็ได้ แต่ก่อน production)
โมดูลอื่นยังติดมาเป็นไฟล์ (ไม่มี route แล้ว แต่กินที่/สับสน)
- [ ] controllers: `Admin/`, `LeadController`, `QueueController`, `ClassroomController`, `AttendanceController`, `GradebookController`, `StudentController`, `SubmissionController`, `AssignmentController`, `PlansController`, `PageController`, `ContactController`, `FeedbackController`, `PublicQueueController`, `QueueBillingController`, `SiteSettingsController`
- [ ] models ที่ไม่เกี่ยว: `Assignment`, `Attendance*`, `Classroom`, `Lead`, `Plan`, `Queue*`, `Student`, `Submission`, `AdminAction` (ระวัง `Subscription` ถ้าจะใช้ทำ billing)
- [ ] migrations: 35/50 ไม่ใช่บัญชี — ตัดสินใจว่าจะลบ หรือเก็บไว้ (กระทบ schema ตอน fresh install)
- [ ] views: `admin/`, `queues/`, `classrooms/`, `attendance/`, `gradebook/`, `students/`, `assignments/`, `pages/`, `plans/`, `partials/product-tour`, components `queue-control`/`scan-dashboard`/`trial-banner`/`impersonation-banner`
- [ ] services: `Gradebook`, `ClassInsights`, `AttendanceSummary`, `Tts`, `QueuePlan`, `ProductGate`, `PlanGate`, `DemoWorkspaceSeeder`, `Notifications/`, `Payments/` (เก็บ `Payments/` ถ้าทำ billing)
- [ ] middleware: `EnsureAdmin`, `EnsureProductEnabled`, `MaintenanceMode`, `BlockSuspendedUser` + `app/Support/Permissions`, `config/admin-products.php`, `config/permissions.php`, `config/plans.php`, `config/queue-plans.php`
- [ ] lang: บล็อกที่ไม่ใช่บัญชี (`queue`, `classrooms`, `admin`, `plans`, `feedback`, ...) ใน `lang/*/app.php` + `lang/*/site.php`
- [ ] เทสต์: ตรวจ `tests/Feature/Auth/*` ว่ายังเขียวหลังลบ

### 6. ย้ายเข้า repo จริง + deploy
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
