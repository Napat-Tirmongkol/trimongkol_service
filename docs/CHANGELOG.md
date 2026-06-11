# Changelog

ฟีเจอร์ทั้งหมดที่เพิ่มในสาขา `claude/lucid-wozniak-Ogl8h` เรียงตามลำดับเวลา (ใหม่สุดอยู่บน)

---

## 🛒 POS — เอกสารออกแบบ + แผนการสร้าง (ยังไม่เริ่มเขียนโค้ด)

วางพิมพ์เขียวระบบขายหน้าร้าน เป็น product ตัวที่ 5 — ใช้ขายของเองได้ และขายเป็นแพ็กเกจให้ลูกค้า SME ต่อ

- **`docs/POS.md`** — รองรับ **2 โหมดในตัวเดียว**: ร้านค้า (retail — คิดเงินจบในจอเดียว) และ **ร้านอาหาร** (เปิดโต๊ะ → สั่งเพิ่มหลายรอบ → ส่งครัว → เก็บเงินตอนลูกค้ากลับ) โดยร้านอาหารเป็น "ชั้นออเดอร์แก้ไขได้" วางทับ engine ขาย/ใบเสร็จ/รายงานชุดเดียวกัน (บิลปิดแล้ว immutable เหมือนเดิม)
- **พนักงานเสิร์ฟรับออเดอร์ผ่านมือถือตัวเองได้** — เว็บ responsive ไม่ต้องลงแอป + **จุดพิมพ์กลาง** (`pos_print_jobs` queue + หน้า `/pos/print-station` poll แล้วพิมพ์อัตโนมัติ) ทำให้สั่งจากมือถือแล้วใบครัวออกที่เครื่องพิมพ์หน้าครัว · รายการสั่งติด `added_by` รายจาน รู้ว่าใครรับออเดอร์
- ครอบคลุม: การตัดสินใจหลัก 11 ข้อ (ตาราง `pos_*` แยกจากบัญชี, auth แบบ queue, เงินผ่าน `Money`, ราคารวม VAT/ใบกำกับภาษีอย่างย่อ, service charge, modifier เป็น JSON snapshot, งานพิมพ์ผ่านคิวกลาง) · data model 14 ตาราง (+ตารางชำระค่าแพ็กเกจ) · ผังโต๊ะ/ใบส่งครัว/ตัวเลือกความเผ็ด-ท็อปปิ้ง · routes+สิทธิ์ · แพ็กเกจ Free (5 โต๊ะ)/฿299/฿699 + QR สั่งเองที่โต๊ะเป็นฟีเจอร์ Pro ในอนาคต
- **reuse ของเดิมล้วน** — workspace, ProductGate, pattern แพ็กเกจ queue/accounting, `PromptPay`+`SlipVerifier`, qrcode CDN, public token แบบ `/q/{token}` — **ไม่มี dependency ใหม่**
- แผนสร้าง 6 เฟส ตัดจบใช้ได้จริงทุกเฟส: คลังสินค้า/เมนู → engine ขาย+เงินสด+ใบเสร็จ → **โต๊ะ+ออเดอร์+ส่งครัว (dogfood กับร้านนำร่อง)** → กะ+PromptPay+รายงาน → แพ็กเกจ+เปิดขาย → เชื่อมบัญชี/QR สั่งเอง
- ยังไม่มี migration/โค้ดในรายการนี้ — เริ่มเขียนจริงที่ Phase 1 ตามเอกสาร

---

## 📊 ระบบบัญชี — แดชบอร์ดภาพรวมการเงิน

ยกเครื่องหน้า `/accounting` จากการ์ดบาง ๆ ให้เป็นภาพรวมที่ front-office ใช้ได้จริงทุกวัน

- **4 การ์ดหลัก** — เงินสด/เงินฝาก (จาก ledger บัญชี 1111/1113) · ลูกหนี้คงค้าง (AR) · เจ้าหนี้คงค้าง (AP) · กำไร(ขาดทุน)เดือนนี้ (สีตามบวก/ลบ)
- **ภาษีต้องนำส่งเดือนนี้** — VAT สุทธิ (ขาย−ซื้อ) · หัก ณ ที่จ่าย ภ.ง.ด.3 / 53 + ลิงก์ไปรายงานภาษี
- **รายการที่ต้องจัดการ** — ใบแจ้งหนี้เกินกำหนด (จำนวน+ยอด) · ใบแจ้งหนี้ร่าง · บิลร่างรอลงบัญชี (กดเข้าหน้าได้เลย) · ถ้าไม่มีค้างขึ้น "ไม่มีรายการค้าง"
- ต่อยอดจาก service เดิมล้วน ๆ (`Receipts`/`SupplierPayments`/`TaxReporting`/`Reporting`) — **ไม่มี migration** · คีย์ `app.accounting.*` (TH/EN)
- **เทสต์ใหม่ 2** (`DashboardTest`) — พิสูจน์ยอดเงินสด/AR/AP/กำไร/VAT/WHT และการตั้งธงเอกสารร่าง/เกินกำหนด

---

## 📥 ระบบบัญชี — ยอดยกมา (Opening balances)

ป้อนงบทดลองยกมาจากสำนักงานบัญชี ให้ยอดตั้งต้นในระบบตรงกับบัญชีจริงตั้งแต่วันแรก

- **`OpeningBalances` service** — รับงบทดลอง (debit/credit ต่อบัญชี) แล้วลงเป็น **journal เดียวชนิด `opening`** ผ่าน `LedgerPosting` (บังคับดุล, สตางค์ integer) · ลงได้ **ครั้งเดียว** (ถ้าจะแก้ต้องกลับรายการเดิมก่อน) · ช่องว่าง = 0, บัญชีเดียวใส่ได้ด้านเดียว
- หน้า **`/accounting/opening-balances`** — ตารางตามหมวด (สินทรัพย์/หนี้สิน/ทุน/รายได้/ค่าใช้จ่าย) ช่องเดบิต-เครดิต พร้อม **ยอดรวม + ผลต่างสด ๆ (Alpine)** · ปุ่มบันทึกเปิดเฉพาะเมื่อดุล · ยืนยันด้วย SweetAlert · กันกดซ้ำ · ลงเมื่อโพสต์แล้วล็อกหน้าเป็นสถานะ "บันทึกแล้ว"
- ลงยอด ณ **วันเริ่มงวดบัญชีที่เปิดอยู่** · เฉพาะ owner/admin (maker-checker เดิม) · เข้าได้จากลิงก์บนหน้ารายงานการเงิน · คีย์ `app.accounting.*` (TH/EN)
- **เทสต์ใหม่ 9** (`OpeningBalanceTest`) — ดุล/ไม่ดุล, ลงครั้งเดียว, ข้ามช่องว่าง, สองด้านไม่ได้, สิทธิ์, และ flow ผ่าน HTTP

---

## 🧾 ระบบบัญชี — รายงานภาษี + ส่งออกสมุดรายวัน

ชุดข้อมูลภาษีสำหรับยื่น/ส่งสำนักงานบัญชี — ดึงจากเอกสารที่ออก/ลงบัญชีแล้ว

- **`TaxReporting` service** — **ภาษีขาย** (จากใบกำกับที่ออกแล้ว) · **ภาษีซื้อ** (จากบิลที่ลงบัญชีแล้ว) · **สรุปหัก ณ ที่จ่าย** แยก ภ.ง.ด.3/53 (จากหนังสือ 50 ทวิ) — รวมยอดด้วย integer สตางค์ ตามช่วงวันที่ (ดีฟอลต์ = เดือนปัจจุบัน เพราะ VAT ยื่นรายเดือน)
- หน้า **`/accounting/reports/tax`** — ตารางภาษีขาย/ซื้อ (เลขที่ · คู่ค้า · เลขผู้เสียภาษี · มูลค่า · ภาษี) + สรุป WHT พร้อมยอด ภ.ง.ด.3/53 · เข้าได้จากลิงก์บนหน้ารายงานการเงิน
- **ส่งออกสมุดรายวัน (CSV)** `/accounting/reports/export` — บรรทัด journal ที่ post แล้วทั้งงวด พร้อม UTF-8 BOM (Excel อ่านภาษาไทยได้) สำหรับส่งให้สำนักงานบัญชี
- เอกสารร่าง/ยกเลิก ไม่ถูกนับ · ทุกอย่าง scope ตาม workspace · คีย์ `app.accounting.*` (TH/EN)
- **เทสต์ใหม่ 5** (`TaxReportingTest`) — พิสูจน์ยอดรวม VAT/WHT, การตัดช่วงวันที่, ตัดเอกสารร่าง, และ CSV export

---

## 💳 ระบบบัญชี — แพ็กเกจ/บิล (ขายเป็นสินค้าได้)

ทำให้ระบบบัญชีคิดเงินเป็นแพ็กเกจรายเดือนได้ แยกบิลจาก Scanner/คิว (เลียนแบบ pattern ของ queue)

- **`config/accounting-plans.php`** — 3 ระดับ: Free (20 ใบ/เดือน) · Starter ฿249 (200 ใบ/เดือน + ส่งออก CSV) · Pro ฿599 (ไม่จำกัด)
- **`AccountingPlan` service** + คอลัมน์ `workspaces.accounting_plan` / `accounting_plan_until` — แพ็กเกจหมดอายุแล้ว drop กลับ Free อัตโนมัติ (ไม่ต้องมี scheduler)
- **บังคับ limit จริง:** ออกเอกสารเกินโควตา/เดือน → เด้งข้อความให้อัปเกรด (กันที่หน้า create + ตอน submit) · ส่งออกสมุดรายวัน CSV เป็นฟีเจอร์ Starter ขึ้นไป
- **แอดมินตั้ง/ขายแพ็กเกจ** ได้ที่หน้า workspace (เลือกแพ็กเกจ + จำนวนเดือน → ตั้งวันหมดอายุให้) · log ผ่าน AuditLog
- **product toggle เดิมใช้ได้อยู่แล้ว** (เปิด/ปิดทั้งระบบบัญชีที่ `/admin/site`)
- **เทสต์ใหม่ 6** (`AccountingPlanTest`) — โควตาเอกสาร, ปลดล็อกแพ็กเกจจ่ายเงิน, gate CSV, หมดอายุ→Free · รวมโมดูลบัญชี 92 เทสต์ผ่าน

---

## 🏢 ระบบบัญชี — onboarding + ข้อมูลบริษัทรายกิจการ + แก้ผังบัญชีเอง (ขายลูกค้าได้)

ทำให้ลูกค้าภายนอกสมัครแล้วเริ่มใช้เองได้บน trimongkol.com โดยข้อมูลแยกขาดต่อกิจการ

- **Onboarding wizard** (`/accounting/onboarding`) — แทนปุ่ม "ตั้งค่า" คลิกเดียวเดิม: กรอกข้อมูลกิจการ (ชื่อ/เลขภาษี/สาขา/ที่อยู่) + เลือกผังบัญชี → ระบบ seed ผัง/รหัสภาษี/งวด แล้วลง `onboarded_at` · workspace ใหม่ถูกพาเข้า wizard อัตโนมัติ
- **ข้อมูลบริษัทย้ายมาเก็บราย workspace** (migration เพิ่มคอลัมน์ใน `workspaces`) — เอกสาร (ใบกำกับ/บิล/50 ทวิ) ดึงชื่อ/เลขภาษี/ที่อยู่จาก workspace ของเอกสารนั้น แทน setting กลางตัวเดียว → ลูกค้าแต่ละเจ้าได้หัวเอกสารของตัวเอง
- **หน้าแก้ผังบัญชีเอง** (`/accounting/accounts`) — เพิ่ม/แก้/ปิด/ลบบัญชี + ใส่บัญชีธนาคารตัวเอง · กัน 2 ชั้น (บัญชีระบบเปลี่ยนประเภท/ลบไม่ได้ · บัญชีที่มีรายการแล้วลบไม่ได้)
- **เทสต์ใหม่ 18** (`OnboardingTest` 9 + `AccountManagementTest` 9) · ปรับ `AccountingUiTest`/`ProductToggleTest` ให้รับ flow onboarding · รวมโมดูลบัญชี 86 เทสต์ผ่าน
- ข้อมูล workspace เดิมที่ตั้งค่าไปแล้วไม่กระทบ (เข้า dashboard ได้ปกติ ไม่ถูกบังคับ onboarding)

---

## 🔐 ระบบบัญชี — เปลี่ยนผังบัญชี default เป็นผังกลาง (เตรียมขายลูกค้า)

เพื่อเปิดให้ลูกค้าภายนอกสมัครใช้ระบบบัญชีบน trimongkol.com ได้อย่างปลอดภัย

- เปลี่ยนผังบัญชี default จาก `TIRMONGKOL_SERVICE` (มีเลขบัญชีธนาคารจริงของบริษัท) → **`STANDARD_TH` ผังบัญชี SME ไทยกลาง** — workspace ที่สมัครใหม่จะ**ไม่ได้เลขบัญชีธนาคารของเราติดไป**อีกต่อไป
- คงครบทั้ง 15 `system_role` ที่ engine ผูก + รหัสบัญชีที่เทสต์อ้างทั้งหมด → ลงบัญชี/ภาษีทำงานเหมือนเดิม (68 เทสต์บัญชีผ่าน)
- เพิ่ม `templates()` (default `standard_th`) เผื่อหน้าเลือกผังตอน onboarding
- ข้อมูลเดิมของ workspace ที่ตั้งค่าไปแล้วไม่กระทบ (เปลี่ยนแค่ค่าตั้งต้นของ workspace ใหม่)

---

## 🔌 เปิด/ปิดแต่ละระบบ (Product on/off)

แอดมินเลือกได้ว่าจะเปิดระบบไหนให้ผู้ใช้เห็น — soft-launch ได้ (เช่น ตอนนี้โชว์แค่ "ส่งการบ้าน")

- ตั้งค่าที่ **`/admin → ตั้งค่าเว็บไซต์`** หมวด **"ระบบที่เปิดใช้งาน (Products)"** — **สวิตช์ toggle** เปิด/ปิด ระบบเรียกคิว / ระบบบัญชี (เบื้องหลังเก็บ `products.<key>.enabled` = 1/0)
- ปิดแล้ว: **ซ่อนเมนูจากผู้ใช้** (เดสก์ท็อป + มือถือ) + **กันเข้า URL ตรง ๆ (404)** ผ่าน middleware `product:`
- ค่าเริ่มต้น = เปิดทุกระบบ (ของเดิมไม่เปลี่ยน) · มีผลทันที (Setting cache busts on save)
- `App\Services\ProductGate` + เทสต์ใหม่ 6 (`ProductToggleTest`)
- หมายเหตุ: "ส่งการบ้าน" (สแกนเนอร์) เป็นแอปหลัก ไม่มีสวิตช์ปิด · admin ยังเข้าจัดการ product ที่ปิดได้

---

## 📊 ระบบบัญชี (Phase 3) — รายงานการเงิน

ดูงบการเงินจากหน้าจอ — คำนวณจาก ledger ทั้งหมด (ดุลเป๊ะ)

- **`Reporting` service** — **งบทดลอง / งบกำไรขาดทุน / งบดุล** derive จาก `journal_lines` ของ journal ที่ post แล้ว, คำนวณด้วย integer สตางค์
- หน้า **`/accounting/reports`** — เลือกช่วงวันที่ → เห็น 3 งบพร้อม badge "ดุล/ไม่ดุล" + ปุ่ม "รายงาน" บน dashboard
- งบทดลองตัดบัญชีที่ยอดสุทธิ = 0 ออก · งบดุลแสดงกำไรสะสมงวดปัจจุบัน · คีย์ `app.accounting.*` (TH/EN)
- **เทสต์ใหม่ 4** (`ReportingTest`) พิสูจน์ TB/P&L/BS ดุล

---

## 🧾 ระบบบัญชี (เฟส 2.5) — ฝั่งซื้อ (AP) + หัก ณ ที่จ่าย + 50 ทวิ

- **3 ตาราง** (`accounting_bills`, `_bill_lines`, `_wht_certificates`) — index ตั้งชื่อสั้นปลอดภัย MySQL ตั้งแต่แรก
- **`Purchasing`** — บิลซื้อ: `create()` ร่าง · `post()` ลงบัญชี **DR ค่าใช้จ่าย + DR ภาษีซื้อ / CR เจ้าหนี้การค้า**
- **`SupplierPayments`** — จ่ายเจ้าหนี้ + **หัก ณ ที่จ่ายจริง**: **DR เจ้าหนี้ / CR ธนาคาร + CR ภาษีหัก ณ ที่จ่าย** แล้วออก **หนังสือรับรอง 50 ทวิ** (`WhtCertificate` แยก ภงด.3/53 ตามบัญชี) + ตัดชำระบิล (allocations polymorphic ตัวเดิม)
- **เทสต์ใหม่ 5** (`AccountsPayableTest`) — ยังไม่มี UI ฝั่ง AP (เป็น engine ต่อ UI ภายหลังแบบเดียวกับ AR)
- 🔢 รวมโมดูลบัญชี **47 เทสต์ผ่าน** (ทั้งโปรเจกต์ 127/129 อีก 2 เป็นเทสต์เดิม)

---

## 🖥️ ระบบบัญชี (เฟส 2.6) — หน้าจอฝั่งผู้ใช้ (AR front-office)

ใช้งานบัญชีผ่านหน้าจอได้จริงแล้ว — ตั้งค่า → เพิ่มคู่ค้า → ออกใบกำกับ → รับเงิน

- เมนู **"บัญชี"** ในแถบนำทาง (เดสก์ท็อป + มือถือ) ที่ `/accounting`
- **ตั้งค่าในคลิกเดียว** — seed ผังบัญชี + รหัสภาษี + งวดบัญชีปีปัจจุบัน จากหน้า dashboard
- **คู่ค้า** (รายการ + ฟอร์มเพิ่ม) · **ใบกำกับขาย** (รายการ + ฟอร์มสร้างที่เพิ่ม/ลบรายการสด คำนวณ VAT/ยอดรวมเรียลไทม์ด้วย Alpine + หน้ารายละเอียด)
- **ออกใบกำกับ** (ลงบัญชี) + **บันทึกรับเงิน** (พร้อม WHT) จากหน้ารายละเอียด พร้อมแสดง **รายการบัญชี (journal)** ที่ลงให้
- **Maker-checker**: สมาชิกทั่วไปสร้างฉบับร่างได้ แต่ "ออกเอกสาร/รับเงิน" ต้องเป็นเจ้าของ/ผู้ดูแล workspace
- กันเข้าถึงข้าม workspace (404) ทุก action + SweetAlert ยืนยันตอนออกใบ + คีย์ `app.accounting.*` (TH/EN) ครบ
- **เทสต์ใหม่ 7** (`AccountingUiTest`, HTTP) → โมดูลบัญชีรวม **37 เทสต์ผ่าน** (ทั้งโปรเจกต์ 117/119 อีก 2 เป็นเทสต์เดิมที่ไม่เกี่ยว)
- ⚠️ หลัง deploy: **Run migrations + Clear caches** (มี view/route ใหม่)

---

## 💰 ระบบบัญชี (เฟส 2.4) — รับเงิน/ใบเสร็จ + หัก ณ ที่จ่าย

รับชำระจากลูกค้า → ตัดลูกหนี้ + ลงบัญชีอัตโนมัติ พร้อมจัดการ WHT จริง

- **2 ตาราง** (`accounting_payments`, `_payment_allocations` แบบ polymorphic เผื่อ AP ในเฟส 2.5) — เงิน `DECIMAL`, scope `workspace_id`, `down()` ครบ
- **`Receipts::record()`** — รับเงิน 1 ครั้งตัดได้หลายใบ (allocations) แล้ว post อะตอมมิก:
  - **เดบิต เงินสด/ธนาคาร** (เงินที่ได้รับจริง) + **เดบิต ภาษีจ่ายล่วงหน้า** (WHT ที่ลูกค้าหัก = เครดิตภาษีของเรา) / **เครดิต ลูกหนี้การค้า**
  - บังคับ: เงินสดที่รับ + WHT = ยอดตัดลูกหนี้รวม
- WHT ลงบัญชี `1151-02` (เพิ่ม tag role `wht_receivable` ในผังบัญชี)
- อัปเดตสถานะใบกำกับอัตโนมัติ: ตัดบางส่วน → `partial`, ครบ → `paid`
- กันสลิปซ้ำด้วย `slip_ref` (unique ต่อ workspace) + เก็บ `slip_path` แนบหลักฐาน — auto-verify ด้วย `SlipVerifier` เป็น hook ไว้ตอนทำ UI (2.6)
- เลขที่รับเงิน `RV2569-xxxxx`
- **เทสต์ใหม่ 7** (`ReceiptsTest`) → รวมโมดูลบัญชี **30 เทสต์ผ่าน**
- ⚠️ หลัง deploy: **Run migrations**

---

## 🧾 ระบบบัญชี (เฟส 2.1–2.3) — คู่ค้า + ภาษี + ใบกำกับขาย (AR)

ต่อจากเฟส 1 — เริ่มงาน front-office: ออกใบกำกับภาษีขายแล้ว **ลงบัญชีอัตโนมัติ**

- **4 ตาราง** (`accounting_partners`, `_tax_codes`, `_invoices`, `_invoice_lines`) — เงิน `DECIMAL`, scope ด้วย `workspace_id`, มี `down()` ครบ
- **คู่ค้า** (`Partner`) — ลูกค้า/ผู้ขาย, เลขภาษี 13 หลัก + สาขา, credit term (คำนวณ due date ให้), ผูกบัญชีคุม AR/AP
- **ภาษี** (`TaxCode` + `TaxCodes::seedDefault`) — VAT7 / VAT7P / WHT3 / WHT5 ผูกบัญชีที่ tag ไว้ตั้งแต่เฟส 1 (ภาษีขาย/ซื้อ/หัก ณ ที่จ่าย) seed แบบ idempotent
- **ใบกำกับขาย** (`SalesInvoicing`) — `create()` ร่าง + คำนวณ subtotal/VAT/WHT/total · `issue()` post ผ่าน `LedgerPosting`: **เดบิตลูกหนี้ / เครดิตรายได้ + ภาษีขาย** แล้วผูก journal กลับเข้า invoice (polymorphic source) · เลขที่รันตามปี พ.ศ. (`INV2569-xxxxx`)
- **WHT บนใบขาย = ข้อมูลประกอบ** (ลูกค้าหักตอนจ่าย) — ยังไม่ลงบัญชีตอนออกบิล รอเฟสรับเงิน (2.4)
- **`Money`** — สกัดตัวคำนวณเลขสตางค์ (`toMinor`/`fromMinor`/`percentage`/`lineAmount`) ใช้ร่วมกับ `LedgerPosting` (เลิกโค้ดซ้ำ) ปัด % ภาษีครึ่งขึ้นด้วย integer ล้วน
- **เทสต์ใหม่ 9** (`SalesInvoicingTest` + `MoneyTest`) → รวมทั้งโมดูลบัญชี **23 เทสต์ผ่าน**
- ยังไม่มี UI ฝั่ง tenant (เฟส 2.6) — ใบที่ issue แล้วจะโผล่เป็น journal ที่ `/admin → บัญชี`
- ⚠️ หลัง deploy: **Run migrations**

---

## 📒 ระบบบัญชี (เฟส 1) — แกนบัญชีคู่ + ผังบัญชีของบริษัท

วางรากฐาน product ใหม่ **"ระบบบัญชี"** (multi-tenant, scope ด้วย `workspace_id`) — เฟส 1 เน้นแกน double-entry ที่ถูกต้องและทดสอบได้ ยังไม่มี UI ฝั่ง tenant (เฟส 2)

- **6 ตาราง** (`accounting_accounts`, `_periods`, `_journals`, `_journal_lines`, `_departments`, `_activity_log`) — เงินเป็น `DECIMAL(18,2)` ทุกช่อง, มี `down()` ครบ, scope ทุกตารางด้วย `workspace_id`
- **`App\Services\Accounting\LedgerPosting`** = ทางเดียวที่เขียน ledger ได้: ห่อ `DB::transaction` (ACID), บังคับ **Debit = Credit** ด้วยเลขจำนวนเต็มสตางค์ (ไม่มี float / ไม่พึ่ง bcmath), post ได้เฉพาะงวดที่เปิด, กันบัญชีข้าม workspace
- **Immutable ledger**: journal ที่ post แล้วแก้/ลบไม่ได้ (guard ใน model) — แก้ด้วย `reverse()` ที่สร้างรายการกลับด้านอ้างกลับ; activity log เป็น append-only
- **ผังบัญชีจริงของบริษัท 54 บัญชี** (`ChartOfAccounts`) ถอดจากงบทดลอง 31-12-2568 — tag บทบาทให้ engine (ar/ap control, VAT ซื้อ-ขาย + deferred, WHT ภงด.3/53, กำไรสะสม ฯลฯ) + flag ค่าใช้จ่ายต้องห้าม; seed แบบ idempotent
- **`departments`** เป็น dimension เผื่อ cost center (nullable — ไม่บังคับกรอก)
- Admin oversight ที่ **/admin → บัญชี** (ลงทะเบียนใน `config/admin-products.php`, ใช้สิทธิ์ `products.moderate` เดิม) + คีย์ `app.admin.products.accounting.*` (TH/EN)
- **2 ชุดเทสต์** (`tests/Feature/Accounting`): พิสูจน์ดุล / กันไม่ดุล + rollback, immutability, ปิดงวด, isolation ข้าม workspace, reverse และ seed ผังบัญชี
- ℹ️ ต้องเปิด `ext-bcmath` ไหม? **ไม่ต้อง** — คำนวณเงินเป็น integer สตางค์ล้วน
- ⚠️ หลัง deploy: **Run migrations** ที่ /admin → ระบบ (เฟส 2 ค่อยทำ UI ออกเอกสาร + AR/AP + ภาษี)

---

## 💵 ชำระเงินอัปเกรดแพ็กเกจคิวด้วย PromptPay + ตรวจสลิป SlipOK

ลูกค้าอัปเกรดแพ็กเกจคิวเองได้ที่ **`/queues/billing`** — เลือกแพ็กเกจ → สแกน PromptPay QR (ระบุยอด) → อัปโหลดสลิป → ระบบเปิดแพ็กเกจ +30 วันอัตโนมัติ

- **PromptPay QR ระบุยอด** สร้าง payload ฝั่ง server (`App\Support\PromptPay`, EMVCo + CRC16) แล้ว render เป็น QR ด้วย `qrcode` เดิม — ไม่เพิ่ม dependency
- **ตรวจสลิปอัตโนมัติด้วย SlipOK** (`App\Services\SlipVerifier`, REST ฝั่ง server): เช็กยอด + กันสลิปซ้ำ (transRef unique) → ผ่านแล้วเปิดแพ็กเกจทันที
- ถ้ายังไม่ตั้งค่า SlipOK → เก็บสลิปเป็น "รอตรวจสอบ" ให้แอดมินกดอนุมัติเองที่ **/admin → คิว → การชำระเงิน**
- แพ็กเกจหมดอายุ (`queue_plan_until`) เด้งกลับ Free อัตโนมัติ; ต่ออายุก่อนหมดจะต่อท้ายวันให้
- ตั้งค่าใน **/admin → คิว**: เปิด/ปิดการขาย, เลขพร้อมเพย์รับเงิน, ชื่อบัญชี, SlipOK key/branch (เข้ารหัสเก็บ)
- โมเดล `QueuePayment` + 2 migration (`queue_plan_until`, `queue_payments`) — มี `down()` ครบ
- คีย์ `app.queue.billing.*` + `app.admin.products.queue.{billing_*,pay_*,tab_payments}` (TH/EN)
- ⚠️ หลัง deploy: **Run migrations + Clear caches** แล้วตั้งค่าที่ /admin → คิว

---

## 💳 แพ็กเกจระบบคิว + Control Panel จัดการ plan/billing

แยกแพ็กเกจเฉพาะระบบคิว (free / starter / pro / enterprise) ออกจากแพ็กเกจ Scanner — บังคับลิมิตทันที (ไม่ขึ้นกับ free-launch)

- **ลิมิต Phase 1:** Free = 1 จุดบริการ, 2 ช่องบริการ, 80 คิว/วัน + ลายน้ำบนหน้าลูกค้า; แพ็กเกจสูงขึ้นปลดล็อกตามตั้งค่า
- บังคับใน: สร้างจุดบริการ, เพิ่มช่องบริการ, ออกบัตรคิว (ลูกค้าเห็นข้อความ "เต็มโควตา/อัปเกรด")
- **Control Panel** ที่ `/admin` → **แพ็กเกจ & บิล**: ภาพรวมแพ็กเกจคิว/Scanner, รายได้ประมาณการ, สวิตช์ free-launch
- ตั้งแพ็กเกจคิวราย workspace ได้ที่หน้า workspace (อัปเกรด/ลดด้วยตนเอง — ยังไม่มีระบบจ่ายเงินอัตโนมัติ)
- `config/queue-plans.php`, `App\Services\QueuePlan`, คอลัมน์ `workspaces.queue_plan` (migration มี down())
- เพิ่มคีย์ `app.queue.plan_limit_*`, `app.admin.billing.*`, `app.admin.workspaces.queue_plan_*` (TH/EN)
- ⚠️ หลัง deploy: **Run migrations + Clear caches**

---

## 🔊 เสียงเรียกคิวคุณภาพสูงด้วย Google Cloud TTS (ตัวเลือก)

เพิ่มทางเลือกใช้ Google Cloud Text-to-Speech ให้เสียงเรียกคิวเป็นเสียงไทยธรรมชาติทุกอุปกรณ์ (ไม่ต้องพึ่งเสียงที่ติดตั้งในเครื่องผู้ใช้) — ปิดไว้โดยปริยาย ใช้เสียงเบราว์เซอร์เดิมจนกว่าจะตั้งค่า

- ตั้งค่าใน **/admin → ระบบเรียกคิว**: เลือกแหล่งเสียง (เบราว์เซอร์ / Google), ใส่ API key (เข้ารหัสเก็บใน DB), เลือกเสียง + ปุ่ม **ทดสอบ key**
- เรียกผ่าน **REST** (ไม่เพิ่ม composer), **proxy ฝั่ง server** กัน key หลุด, **cache ไฟล์เสียง** ที่ `storage` (เรียกซ้ำไม่เปลืองโควตา)
- เล่นเสียงทั้งหน้าควบคุมและหน้าลูกค้า — ถ้าโหลด/เรียกพลาดจะ **fallback กลับเสียงเบราว์เซอร์** อัตโนมัติ
- คีย์อ่านจาก site_settings (เข้ารหัส) หรือ `.env` (`GOOGLE_TTS_KEY`) ก็ได้
- เพิ่ม `App\Services\Tts`, route `queues.tts` / `queue.public.tts` (throttle) + คีย์ `app.admin.products.queue.tts_*`
- ⚠️ ไม่มี migration ใหม่ — ถ้าจะเปิด Google: หลัง deploy **Clear caches** แล้วไปตั้งค่าที่ /admin

---

## 🎫 ระบบเรียกคิว (Queue System)

product ใหม่ (ผูกกับ workspace เหมือน Scanner) — สร้างจุดบริการ เรียกคิวทีละหมายเลข มีเสียงเรียก และให้ลูกค้าสแกน QR รับบัตรคิวเอง

- **หน้าควบคุมเจ้าหน้าที่** (`/queues/{queue}`): เลือกช่องบริการ → ปุ่ม **เรียกคิวถัดไป / เรียกซ้ำ / ข้าม** + บอร์ด "กำลังให้บริการ" ของทุกช่อง + สถิติ (รอ / ให้บริการแล้ววันนี้) — อัปเดตสดด้วย `wire:poll`
- **หน้าลูกค้า (สาธารณะ)** ที่ `/q/{token}`: กดรับบัตรคิว ดูว่าเหลืออีกกี่คิว และเด้งแจ้ง "ถึงคิวคุณแล้ว" พร้อมเสียง — ไม่ต้องล็อกอิน
- **เสียงเรียกคิวภาษาไทย** ผ่าน Web Speech API ของ browser + เสียงติ๊งต่อง (WebAudio) — ไม่เพิ่ม dependency
- **QR / ลิงก์รับบัตร** + หน้า **พิมพ์ป้าย QR** (ใช้ `qrcode` ที่มีอยู่แล้ว) ไว้ติดหน้าร้าน
- รองรับ **หลายช่องบริการ** ต่อจุดบริการ, ตั้ง prefix หมายเลข (เช่น A001), เปิด/ปิดรับคิว, ปุ่ม **เริ่มรอบใหม่**
- โมเดล `Queue` / `QueueCounter` / `QueueTicket` (3 migration มี `down()` ครบ) — ออกบัตรด้วย transaction + `lockForUpdate` กันหมายเลขชนกัน
- ลงทะเบียนเป็น product ใน `/admin` (ภาพรวม + รายการคิว + ลบ ผ่านสิทธิ์ `products.moderate`) + ลิงก์ในเมนูแอป
- เพิ่มคีย์ `app.queue.*` + `app.admin.products.queue.*` (TH/EN ครบคู่)
- ⚠️ หลัง deploy: **Pull → Run migrations → Clear caches** ที่ `/admin/system` (มี 3 ตารางใหม่ + route/view/lang/Tailwind class ใหม่)

---

## 🔤 Smart Clipboard OCR — แปลงรูปเป็นข้อความ (ไทย/อังกฤษ)

เครื่องมือฟรีสาธารณะที่ **`/ocr`** (marketing layout, ไม่ต้องล็อกอิน) ดึงข้อความจากรูปภาพ รองรับไทย + อังกฤษ

- **ทำงานฝั่ง browser ทั้งหมด** ด้วย **Tesseract.js ผ่าน CDN** (jsDelivr) — ไม่เพิ่ม npm/composer, ไม่อัปโหลดรูปขึ้น server, รูป/ข้อความไม่ถูกเก็บที่ไหน
- **รับรูปได้ 4 ทาง**: วาง (Ctrl+V), ลากวาง, เลือกไฟล์, ถ่ายจากกล้องมือถือ
- เลือกภาษา **ไทย+อังกฤษ / ไทย / อังกฤษ**, มี progress bar ตอนอ่าน, ผลลัพธ์แก้ไขได้ + **คัดลอก / บันทึก .txt / ล้าง** (นับตัวอักษร/คำ)
- โหลดชุดภาษาครั้งแรกจาก CDN แล้ว cache ใน browser (IndexedDB) — ครั้งต่อไปเร็วขึ้น
- UI เป็น Alpine component (`ocrTool`) ใน `resources/views/pages/ocr.blade.php` + คีย์ `site.ocr.*` (TH/EN)
- ลิงก์เข้าจาก **navbar + footer** เว็บ marketing และการ์ด CTA บน **หน้าแรก + หน้าบริการ**
- ⚠️ หลัง deploy: **pull + clear cache** (มี route/view/lang/Tailwind class ใหม่)

---

## 🛡️ Security hardening (จาก security review)

- **กัน privilege escalation ผ่าน impersonation**: สวมสิทธิ์ได้เฉพาะผู้ใช้ทั่วไป (กัน Admin สวมเป็น Super Admin แล้วได้สิทธิ์เต็ม) + `session()->regenerate()` ตอนเริ่ม/หยุดสวมสิทธิ์ (กัน session fixation)
- **rate limit หน้า 2FA challenge** (`throttle:6,1`) — กัน brute-force รหัส TOTP / recovery code
- **rate limit `/auth/identify`** (`throttle:20,1`) — ลดการ enumerate ว่าอีเมลไหนสมัครแล้ว
- เพิ่ม key `app.admin.cannot_impersonate_staff` (TH/EN)

---

## 🎓 เพิ่มพาทัวร์สอนใช้ (guided tour)

ทัวร์ไฮไลต์ทีละจุดบน UI จริง (Driver.js ผ่าน CDN — ไม่เพิ่ม npm) สอนครูใหม่ตาม flow

- **3 หน้า**: dashboard (สร้างห้อง) → ห้องเรียน (เพิ่มนักเรียน → พิมพ์บาร์โค้ด → สร้างการบ้าน → สมุดคะแนน) → หน้าสแกน (เปิดกล้อง)
- **เด้งอัตโนมัติครั้งแรก** (จำผ่าน localStorage ต่อเครื่อง) + ปุ่ม **"วิธีใช้"** กดซ้ำได้ทุกหน้า
- ขั้นที่ element ไม่มีในหน้าจะถูกข้ามอัตโนมัติ (ทัวร์ปรับตามสถานะหน้า เช่น ห้องว่าง/ห้องมีนักเรียน)
- partial `partials/product-tour.blade.php` (`window.startTour` / `maybeAutoTour`) + key `app.tour.*` (TH/EN)
- มี Tailwind class ใหม่เล็กน้อย → ตอน deploy ต้อง **pull + clear cache**

---

## 🔒 เพิ่มช่อง "ยืนยันรหัสผ่าน" ตอนสมัคร

- ฟอร์มสมัคร (email-first ใน `auth/login.blade.php`) เพิ่มช่อง **ยืนยันรหัสผ่าน** + ปุ่มดู/ซ่อน
- เพิ่ม validate `confirmed` ใน `RegisteredUserController` (เดิมจงใจตัดออก ใช้รหัสเดียว)
- ปรับ hint เป็น "ขั้นต่ำ 8 ตัวอักษร" + เพิ่ม key `app.auth.confirm_password` (TH/EN)
- กระทบเฉพาะฟอร์มสมัคร — ฟอร์ม sign in ไม่เปลี่ยน

---

## 🙏 เพิ่มช่องทางบริจาค (PromptPay QR)

- หน้า **`/donate`** (marketing layout) โชว์ QR + ชื่อบัญชี/พร้อมเพย์ + ข้อความขอบคุณ
- **จัดการผ่าน `/admin/site`** → section "Donation / บริจาค": อัปโหลดรูป QR + ชื่อ/เลขบัญชี + ข้อความ (TH/EN) + สวิตช์เปิด/ปิด
- ลิงก์ **"สนับสนุนเรา"** โผล่ที่ footer เว็บ marketing + เมนูในแอป (โชว์เฉพาะเมื่อ `donate.enabled = 1`)
- เพิ่ม key `site.donate.*` (TH/EN) + `app.nav.support`
- ⚠️ หลัง deploy: ไปที่ `/admin/site` → อัปโหลดรูป QR + ตั้ง `donate.enabled = 1`

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
