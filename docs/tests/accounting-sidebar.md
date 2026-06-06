# Test Plan — Accounting Sidebar Redesign

Branch: `claude/gracious-galileo-NR4YV`
Scope: ทุกหน้าใต้ `/accounting/*` (layout: `layouts/accounting.blade.php`)

## Pre-conditions
1. Pull branch ผ่าน `/admin/system` → Run migrations → Clear cache (โดยเฉพาะ view cache เพราะแก้ Blade)
2. มี workspace ที่เปิด product `accounting` แล้ว และผ่าน onboarding แล้ว
3. เตรียมผู้ใช้ 3 role: `owner`, `admin`, `staff` ในระบบบัญชี

---

## 1. Layout & Sidebar visibility

| # | ขั้นตอน | คาดหวัง |
|---|---|---|
| 1.1 | เปิด `/accounting/login` ตอนยังไม่ login | ไม่มี sidebar (เพราะ login ไม่ใช้ accounting layout) |
| 1.2 | Login แล้วเข้า `/accounting` บนจอ ≥1024px | Sidebar fixed ติดซ้าย กว้าง 16rem พื้นขาว เส้น border-slate-200 |
| 1.3 | resize browser ลงต่ำกว่า 1024px | Sidebar ซ่อน, ปุ่มแฮมเบอร์เกอร์โผล่บน top bar ฝั่งซ้าย |
| 1.4 | กดปุ่มแฮมเบอร์เกอร์ | Drawer slide จากซ้าย + backdrop เบลอ + กดนอก drawer หรือปุ่ม × ปิดได้ |
| 1.5 | reload ขณะ drawer เปิด | drawer ปิดเอง (state ไม่ persist — ตามดีไซน์) |

## 2. Active state

| # | ขั้นตอน | คาดหวัง |
|---|---|---|
| 2.1 | อยู่หน้า `/accounting` | เมนู "แดชบอร์ด" ใต้กลุ่ม "ภาพรวม" ไฮไลต์ (bg-brand-50, text-brand-700, ไอคอน brand-600) |
| 2.2 | ไปหน้า `/accounting/invoices` | "ใบกำกับขาย" ใต้กลุ่ม "รายรับ" ไฮไลต์ |
| 2.3 | ไป `/accounting/invoices/create` | "ใบกำกับขาย" ยังไฮไลต์ (pattern `accounting.invoices.*`) |
| 2.4 | ไป `/accounting/reports/tax` | "รายงานภาษี" ไฮไลต์ ไม่ใช่ "รายงาน" |
| 2.5 | ไปทุกเมนูทีละหมวด | active state ตรงกับเมนูที่กดเสมอ ไม่ค้างผิดที่ |

## 3. Role-based visibility (กลุ่ม "ระบบ")

| Role | Approvals | Users | Audit log |
|---|---|---|---|
| owner | ✅ | ✅ | ✅ |
| admin | ✅ | ❌ | ✅ |
| staff | ❌ | ❌ | ❌ |

ขั้นตอน: login แต่ละ role แล้วเช็คว่ากลุ่ม "ระบบ" แสดงเฉพาะเมนูที่ควรเห็น (ถ้า staff ไม่มีเมนูเลย ให้ตรวจว่ากลุ่มไม่ render หัวข้อกลุ่มลอย ๆ)

## 4. Top bar

| # | ขั้นตอน | คาดหวัง |
|---|---|---|
| 4.1 | จอใหญ่ | ไม่มีปุ่มแฮมเบอร์เกอร์, ไม่ซ้ำชื่อแบรนด์ (อยู่ใน sidebar แล้ว) |
| 4.2 | จอเล็ก | เห็นปุ่มแฮมเบอร์เกอร์ + ชื่อ "บัญชี" |
| 4.3 | user เป็น demo | ไม่เห็นปุ่ม "เปลี่ยนรหัสผ่าน" |
| 4.4 | กด logout | redirect ไป `/accounting/login` ปกติ |
| 4.5 | role badge (owner/admin/staff) | แสดงข้าง ๆ ชื่อบน ≥sm |

## 5. Dashboard header

| # | ขั้นตอน | คาดหวัง |
|---|---|---|
| 5.1 | dashboard หลัง onboarding | ไม่มี chip row ยาว ๆ ของลิงก์ (reports/partners/accounts/...) แล้ว |
| 5.2 | เห็นปุ่ม "ออกใบกำกับใหม่" สีน้ำเงิน | ยังคงอยู่ทางขวาของ header |
| 5.3 | dashboard ยังไม่ setup | header ไม่มีปุ่ม (เงื่อนไข `$isSetUp`) |

## 6. Onboarding & Auth pages

| # | ขั้นตอน | คาดหวัง |
|---|---|---|
| 6.1 | `/accounting/login` | ใช้ layout ของตัวเอง ไม่มี sidebar (ไม่ regress) |
| 6.2 | `/accounting/onboarding` | ใช้ layout ของตัวเอง ไม่มี sidebar |
| 6.3 | `/accounting/password` (change-password) | ใช้ accounting layout → **มี sidebar** (เป็นหน้า authed) |

## 7. i18n (TH/EN)

| # | ขั้นตอน | คาดหวัง |
|---|---|---|
| 7.1 | locale = `th` | หัวกลุ่ม sidebar: ภาพรวม / รายรับ / รายจ่าย / สมุดบัญชี / ทรัพย์สิน & บุคลากร / รายงาน / ระบบ |
| 7.2 | locale = `en` | Overview / Sales / Purchases / Bookkeeping / Assets & People / Reports / Admin |
| 7.3 | สลับ locale แล้วเคลียร์ view cache | label เปลี่ยนตามภาษา ไม่มี `app.accounting.sidebar_*` หลุดเป็น raw key |

## 8. Accessibility

| # | ขั้นตอน | คาดหวัง |
|---|---|---|
| 8.1 | Tab keyboard ใน sidebar | focus เห็นชัด (default Tailwind ring) ไล่ตามลำดับเมนูจากบนลงล่าง |
| 8.2 | ปุ่มแฮมเบอร์เกอร์ | มี `aria-label="Open menu"` |
| 8.3 | Drawer mobile | มี `role="dialog"` + `aria-modal="true"` |
| 8.4 | Contrast | active link bg-brand-50 + text-brand-700 ผ่าน WCAG AA (≥4.5:1) — text-slate-600 บน white ผ่าน |

## 9. Responsive checks

ทดสอบที่ breakpoint: 360px, 768px, 1024px, 1280px, 1536px
- ≤1023px: sidebar เป็น drawer
- ≥1024px: sidebar fixed, content shift ขวา 16rem
- content area ไม่มี horizontal scroll bug ใด ๆ

## 10. Regression — ฟีเจอร์เดิมต้องไม่พัง

| # | หน้า | เช็ค |
|---|---|---|
| 10.1 | Invoices index/create/show | โหลด, ออก/รับเงิน/ยกเลิกได้ |
| 10.2 | Bills index/create/post | โหลด, ลงบัญชี/จ่ายเงินได้ |
| 10.3 | Reports + Tax reports | ตัวเลขตรง, link ดูข้อมูลเก่าใช้ได้ |
| 10.4 | Bank reconciliation | import CSV + จับคู่ทำได้ |
| 10.5 | Payroll runs | สร้างงวด + post ได้ |
| 10.6 | Manual journals | บันทึก + void ได้ |
| 10.7 | Print views (invoice/bill/payslip) | layout print ไม่เพี้ยน (หน้าเหล่านี้ไม่ได้ใช้ accounting layout) |
| 10.8 | SweetAlert toast | session('status') / session('error') ยังเด้งปกติ |
| 10.9 | Admin `/admin/products/accounting` | ไม่กระทบ (ใช้ admin layout คนละตัว) |

## 11. Automated tests

รัน feature tests (ถ้ามี vendor/):
```bash
php artisan test --filter=Accounting
```

ที่ควรผ่านครบ (ไม่ได้แก้ logic):
- `tests/Feature/Accounting/AccountingUiTest.php`
- `tests/Feature/Accounting/DashboardTest.php`
- `tests/Feature/Accounting/SalesInvoicingTest.php`
- `tests/Feature/Accounting/AccountsPayableTest.php`
- `tests/Feature/Accounting/ReportingTest.php`
- ฯลฯ ทุกตัวใต้ `tests/Feature/Accounting/`

หากเทสต์ใหม่ ๆ มี `assertSee` หา chip link เก่า (เช่น สองปุ่ม "ผังบัญชี" และ "บิลซื้อ" บน dashboard header) จะ fail — ปัจจุบันยังไม่มี assertSee ลักษณะนั้นจาก grep

---

## Acceptance criteria

- ✅ ทุกเมนูเก่าที่เคยอยู่ใน chip row + ทุกเมนูที่มี route เข้าถึงได้ครบจาก sidebar
- ✅ Sidebar fixed บน desktop, drawer บน mobile
- ✅ Active state ถูกต้องบนทุก route
- ✅ Role gating ทำงาน
- ✅ TH/EN ครบคู่ ไม่มี raw key หลุด
- ✅ ฟีเจอร์เดิมไม่ regress
