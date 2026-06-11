# POS — เอกสารออกแบบ + แผนการสร้าง

ระบบขายหน้าร้าน (Point of Sale) — product ตัวที่ 5 ของแพลตฟอร์ม ถัดจาก scanner / queue /
accounting / social ออกแบบให้ **ใช้ขายของเองได้จริง และขายเป็นแพ็กเกจรายเดือนให้ลูกค้า SME ต่อได้**
(ร้านค้า ร้านอาหาร คลินิก ซาลอน — ตามกลุ่มเป้าหมายใน `PRODUCT.md`)

หลักการใหญ่: **reuse ของที่มีอยู่ให้มากที่สุด** — workspace multi-tenancy, ProductGate,
pattern แพ็กเกจของ queue/accounting, PromptPay + SlipVerifier, SweetAlert2, brand UI
**ไม่เพิ่ม composer/npm dependency ใหม่เลย** (QR ใช้ qrcode CDN ตัวเดิมของหน้า `queues/pay`)

---

## 1. ขอบเขต

### v1 ทำอะไรได้ (เมื่อครบ Phase 1–4)

- คลังสินค้า + หมวดหมู่ + บาร์โค้ด + รูปสินค้า + สต๊อกอย่างง่าย
- จอขายแบบแตะ (tablet-first) — ค้นหา/ยิงบาร์โค้ด, ตะกร้า, ส่วนลด
- รับเงิน: เงินสด (คิดเงินทอน) · PromptPay QR ของร้านเอง · โอน/บัตร (บันทึกอย่างเดียว)
- ใบเสร็จรับเงิน / ใบกำกับภาษีอย่างย่อ พิมพ์ผ่าน browser (กระดาษ 58/80 มม.)
- เปิด–ปิดกะ + กระทบยอดเงินสด (X/Z report)
- ยกเลิกบิล (void) พร้อมเหตุผล + คืนสต๊อก
- รายงานขาย รายวัน/ช่วงเวลา แยกตามวิธีจ่าย/สินค้า/พนักงาน + ส่งออก CSV
- แพ็กเกจ Free/Starter/Pro ขายผ่าน PromptPay + แนบสลิป เหมือน queue

### ตั้งใจ *ยังไม่ทำ* ใน v1 (พร้อมเหตุผล)

| เรื่อง | เหตุผล / ทางไปต่อ |
|---|---|
| โหมด offline | service worker + sync ซับซ้อนมาก — v1 กัน double-submit ให้ดีพอ แล้วค่อยประเมิน |
| จ่ายหลายวิธีในบิลเดียว (split) | schema รองรับแล้ว (`pos_sale_payments` แยกตาราง) — เปิดที่ UI ทีหลังได้โดยไม่ต้อง migrate |
| สมาชิก/สะสมแต้ม | Phase 5 — ตาราง `pos_customers` ค่อยเพิ่ม |
| หลายสาขาใน workspace เดียว | ใช้ 1 สาขา = 1 workspace ไปก่อน (สลับ workspace ได้อยู่แล้ว) |
| ใบกำกับภาษีเต็มรูป | เป็นงานของระบบบัญชี (`SalesInvoicing`) — POS ออกอย่างย่อพอ แล้วชี้ลูกค้าไป accounting |
| ฮาร์ดแวร์ลิ้นชักเงิน/เครื่องชั่ง | ผูกกับเครื่องพิมพ์/driver เฉพาะรุ่น — นอกขอบเขต web app |

---

## 2. การตัดสินใจเชิงสถาปัตยกรรม (Decisions)

**D1 — ตาราง POS แยกของตัวเอง (`pos_*`) ไม่ extend `accounting_products`**
POS ต้องใช้ได้โดยลูกค้า*ไม่ต้อง* onboard ระบบบัญชี (accounting product ผูก GL account +
onboarding wizard) จึงมี `pos_products` ของตัวเอง และเว้นคอลัมน์ `accounting_product_id`
(nullable) ไว้เชื่อมตอน Phase 5

**D2 — auth ใช้ platform user + workspace member (แบบ queue) ไม่แยก guard แบบ accounting**
ลด friction: เจ้าของร้านชวนพนักงานเป็นสมาชิก workspace ได้อยู่แล้ว (`WorkspaceInvitation`)
สิทธิ์อิง `WorkspaceMember.role` — PIN พนักงานแบบไม่ต้องมี account ค่อยทำ Phase 5 ถ้ามีคนขอ

**D3 — เงินคำนวณเป็นสตางค์ integer ผ่าน `Accounting\Money` เก็บคอลัมน์ `decimal(18,2)`**
ตาม house style ของโมดูลบัญชี — ห้าม float ทุกจุดที่คิดเงิน

**D4 — ราคาขาย "รวม VAT" (ปกติของหน้าร้านไทย)**
ตั้งค่าใน `pos_settings`: ร้านจด VAT หรือไม่ — ถ้าจด ใบเสร็จเป็น**ใบกำกับภาษีอย่างย่อ**
ถอด VAT จากราคารวม (`vat = total × 7/107` ปัดที่ยอดรวมด้วย Money) ถ้าไม่จด เป็นใบเสร็จรับเงินธรรมดา
หัวเอกสารดึงจากคอลัมน์ที่มีแล้วใน `workspaces` (`company_name, tax_id, branch, phone, company_address`)

**D5 — สต๊อก POS เป็น counter อย่างง่าย ติดลบได้ (มีเตือน)**
หน้าร้านจริงห้ามขายไม่ได้เพราะตัวเลขในระบบเพี้ยน — ดีฟอลต์ขายต่อได้ + โชว์ป้ายเตือนสต๊อกติดลบ
(ปิดได้ใน settings) ส่วนต้นทุนถัวเฉลี่ย/ลง journal เป็นหน้าที่ `Accounting\Inventory` ตอน sync (Phase 5)

**D6 — การจ่ายเงินแยกตาราง `pos_sale_payments` ตั้งแต่วันแรก**
v1 UI สร้าง 1 บิล = 1 payment แต่ schema พร้อมรับ split payment โดยไม่ต้องแก้โครงทีหลัง

**D7 — เลขที่บิล running ต่อ workspace**
รูปแบบ `POS-YYYYMM-#####` สร้างใน DB transaction + unique index `(workspace_id, number)`
ชนกันให้ retry — กันเครื่อง 2 เครื่องขายพร้อมกัน

**D8 — บิลแก้ไขไม่ได้ (immutable) มีแต่ void**
ปิดบิลแล้วห้ามแก้ทุกช่อง — แก้ผิดให้ void (เก็บคนทำ+เหตุผล) แล้วตีบิลใหม่ ลดช่องโกงและทำให้ยอดเชื่อถือได้

---

## 3. Data model

```
workspaces 1─n pos_categories 1─n pos_products ─(optional)→ accounting_products
workspaces 1─n pos_shifts 1─n pos_sales 1─n pos_sale_items
                                        1─n pos_sale_payments
workspaces 1─1 pos_settings
```

ทุก migration **ต้องมี `down()`** และคอลัมน์ใหม่ใช้ `->after()` ตามกฎบ้าน · ทุกตารางมี
`workspace_id` + ใช้ trait `BelongsToWorkspace`

### `pos_settings` (1 แถว/workspace)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| workspace_id | FK unique | |
| promptpay_id | string nullable | เบอร์/เลขผู้เสียภาษีรับเงินของ "ร้าน" (ไม่ใช่ของแพลตฟอร์ม) |
| vat_registered | boolean default false | จด VAT → พิมพ์ใบกำกับภาษีอย่างย่อ |
| vat_rate | decimal(5,2) default 7 | |
| receipt_footer | string nullable | เช่น "ขอบคุณที่อุดหนุนค่ะ" |
| paper_width | enum 58/80 default 58 | ความกว้างกระดาษใบเสร็จ |
| allow_negative_stock | boolean default true | ดู D5 |

### `pos_categories`

`workspace_id` · `name` · `color` (key จาก palette คงที่ — ดู §6 เรื่อง dynamic Tailwind) ·
`sort_order` · timestamps · unique `(workspace_id, name)`

### `pos_products`

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| workspace_id / category_id | FK, category nullOnDelete | |
| name | string | |
| sku | string nullable | unique `(workspace_id, sku)` |
| barcode | string nullable | index `(workspace_id, barcode)` — รับยิงสแกนเนอร์ |
| price | decimal(18,2) | ราคาขายรวม VAT |
| cost | decimal(18,2) nullable | ไว้ดูกำไรขั้นต้นคร่าว ๆ |
| image_path | string nullable | เก็บใน storage เดิม |
| track_stock | boolean default false | เปิดเฉพาะสินค้าที่อยากนับ |
| on_hand | decimal(12,2) default 0 | รองรับหน่วยชั่ง (กก.) |
| low_stock_threshold | decimal(12,2) nullable | ต่ำกว่านี้ขึ้นป้ายเตือน |
| is_active | boolean default true | ปิดชั่วคราวไม่ต้องลบ |
| accounting_product_id | FK nullable | สะพานไป accounting (Phase 5) |

### `pos_shifts`

`workspace_id` · `status` (open/closed) · `opened_by`/`opened_at`/`opening_cash` ·
`closed_by`/`closed_at`/`expected_cash`/`counted_cash`/`cash_difference` ·
`sales_count`/`sales_total` (สรุปตอนปิดกะ) · `note` — เปิดได้ทีละ 1 กะ/workspace

### `pos_sales`

| คอลัมน์ | หมายเหตุ |
|---|---|
| workspace_id / shift_id / user_id | user_id = แคชเชียร์ |
| number | `POS-YYYYMM-#####` unique `(workspace_id, number)` |
| status | completed / voided |
| subtotal / discount / total | decimal(18,2) — total = subtotal − discount |
| vat_rate / vat_amount | snapshot ณ เวลาขาย (ถอดจาก total ถ้าจด VAT) |
| client_uuid | uuid unique — กัน double-submit (ดู §9) |
| note | nullable |
| voided_by / voided_at / void_reason | ครบเมื่อ status = voided |

### `pos_sale_items`

`sale_id` · `product_id` (nullable — สินค้าโดนลบทีหลัง บิลเก่าต้องยังอ่านได้) ·
`name` + `sku` (**snapshot** ตอนขาย) · `unit_price` · `quantity` decimal(12,2) ·
`discount` · `line_total`

### `pos_sale_payments`

`sale_id` · `method` (cash / promptpay / transfer / card / other) · `amount` ·
`tendered` + `change` (เฉพาะเงินสด) · `reference` nullable (เลขอ้างอิงโอน)

### `workspaces` (เพิ่มคอลัมน์)

`pos_plan` string nullable + `pos_plan_until` timestamp nullable —
`->after('accounting_plan_until')` ตาม pattern queue/accounting เป๊ะ

---

## 4. Services (ทั้งหมดอยู่ `app/Services/Pos/` ยกเว้น PosPlan)

| Service | หน้าที่ |
|---|---|
| `App\Services\PosPlan` | โคลน `AccountingPlan` ทั้งโครง — key()/limit()/can()/หมดอายุ drop เป็น free เงียบ ๆ |
| `Pos\SaleNumber` | ออกเลขบิลใน transaction + retry เมื่อชน unique |
| `Pos\Checkout` | หัวใจระบบ — รับ `[{product_id, quantity, discount}]` + payment จาก client แล้วใน **DB transaction เดียว**: ดึงราคาจาก DB (***ไม่เชื่อราคาจาก client เด็ดขาด***) → คิด subtotal/discount/total/VAT ด้วย `Money` → ออกเลขบิล → insert sale+items+payments → ตัดสต๊อกเฉพาะ track_stock → คืน sale |
| `Pos\VoidSale` | ตรวจสิทธิ์ (owner/admin) → ตั้ง voided + เหตุผล → คืนสต๊อก → `AuditLog::record('pos.sale.void', …)` |
| `Pos\ShiftReport` | เปิด/ปิดกะ + คำนวณ expected_cash = opening + ยอดขายเงินสดในกะ − (refund ในอนาคต) เทียบ counted |
| `Pos\Reports` | ยอดขายช่วงวันที่ แยก วิธีจ่าย/สินค้า/หมวด/พนักงาน + CSV (UTF-8 BOM แบบ `TaxReporting`) — CSV เป็น flag ของแพ็กเกจ |
| `Pos\AccountingBridge` *(Phase 5)* | โพสต์สรุปขายรายวันเข้า ledger: DR เงินสด/ธนาคารแยกตาม method / CR รายได้ขาย + CR ภาษีขาย ผ่าน `LedgerPosting::post` และตัดสต๊อกฝั่งบัญชีผ่าน `Inventory::issue` สำหรับสินค้าที่ลิงก์แล้ว |

จุดที่ต้อง `AuditLog::record`: แก้ `pos_settings` · void บิล · เปิด/ปิดกะ (มีส่วนต่างเงินสด) ·
แอดมินตั้งแพ็กเกจ · แอดมินยืนยัน/ปฏิเสธสลิป

---

## 5. Routes + สิทธิ์

ใต้ `auth + verified` + `product:pos` (ProductGate key ใหม่ `pos` — soft launch ได้:
ปิดไว้ให้ admin เห็นคนเดียวจนกว่าจะพร้อม เหมือนตอนเปิด accounting)

```
GET  /pos                         จอขาย (ถ้ายังไม่เปิดกะ → บังคับเปิดกะก่อน)
POST /pos/sales                   ปิดบิล (Checkout)
GET  /pos/sales                   ประวัติบิล + ฟิลเตอร์วัน/สถานะ/พนักงาน
GET  /pos/sales/{sale}            รายละเอียดบิล
GET  /pos/sales/{sale}/receipt    หน้าใบเสร็จสำหรับพิมพ์ (print CSS)
POST /pos/sales/{sale}/void       ยกเลิกบิล                     [owner/admin]
GET|POST /pos/shifts              เปิดกะ · GET = ประวัติกะ
GET  /pos/shifts/{shift}          รายงานกะ (X/Z)
POST /pos/shifts/{shift}/close    ปิดกะ + กรอกเงินนับจริง
CRUD /pos/products, /pos/categories                              [owner/admin]
GET  /pos/reports (+ /export CSV)                                [owner/admin]
GET|PATCH /pos/settings                                          [owner]
GET  /pos/billing · GET|POST /pos/billing/{plan}   ซื้อแพ็กเกจ (โคลน QueueBilling)
```

สิทธิ์ใน workspace: **member ขายได้/เปิดปิดกะของตัวเอง** · products, settings, void,
reports = owner/admin — enforce ใน controller ผ่าน `WorkspaceMember.role` (แบบเดียวกับ queue)

ฝั่งแอดมินแพลตฟอร์ม — ลงทะเบียน `config/admin-products.php` ตามสูตร 4 ขั้นใน `docs/ADMIN.md`:

```
admin.pos.dashboard   ภาพรวม: จำนวนร้านที่ใช้, บิล/วัน, รายได้แพ็กเกจ
admin.pos.payments    ยืนยันสลิปซื้อแพ็กเกจ (โคลนหน้า queue payments)
```

`+ PATCH /admin/workspaces/{id}/pos-plan` ให้แอดมินตั้งแพ็กเกจมือ (ขายตรง/ของแถม) + AuditLog

---

## 6. UI/UX — อิง `tirmongkol-design` (ห้าม override สีแบรนด์)

**จอขาย (tablet-first, แตะเป้าหมาย ≥ 44px, Alpine.js จัดการ state ตะกร้า):**

```
┌────────────────────────────────────────────┬──────────────────────┐
│ [ค้นหา / ยิงบาร์โค้ด ____________ ]        │ กะ #12 · สมชาย       │
│ [ทั้งหมด] [เครื่องดื่ม] [ของแห้ง] [อื่นๆ]   │──────────────────────│
│ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐        │ น้ำดื่ม ×2      14.- │
│ │ 🧃    │ │ ☕    │ │ 🍜    │ │ …    │        │ มาม่า  ×1       7.- │
│ │น้ำดื่ม│ │กาแฟ  │ │มาม่า │ │      │        │ [−] [+] [ลบ]        │
│ │  7.-  │ │ 35.- │ │  7.- │ │      │        │──────────────────────│
│ └──────┘ └──────┘ └──────┘ └──────┘        │ ส่วนลด ____    รวม   │
│   (การ์ดสินค้า: รูป/ชื่อ/ราคา ใหญ่ชัด)      │      ฿21.00          │
│                                            │ ┌──────────────────┐ │
│                                            │ │   เก็บเงิน  💵    │ │  ← bg-brand-600
│                                            │ └──────────────────┘ │
└────────────────────────────────────────────┴──────────────────────┘
```

- ช่องค้นหา autofocus ตลอด — **barcode scanner = keyboard wedge** ยิงแล้ว Enter เข้าตะกร้าเอง
- กดเก็บเงิน → modal เลือกวิธีจ่าย: **เงินสด** (ปุ่มแบงค์ด่วน 20/50/100/500/1,000 + พอดี +
  เงินทอนตัวเลขใหญ่มาก) / **PromptPay** (QR จาก `PromptPay::payload(promptpay_id ของร้าน, total)`
  render ด้วย qrcode CDN — pattern เดียวกับ `queues/pay.blade.php` แล้วกด "รับเงินแล้ว") /
  **โอน/บัตร** (ใส่เลขอ้างอิง)
- ปิดบิลแล้ว → จอเงินทอน + ปุ่ม "พิมพ์ใบเสร็จ" / "บิลถัดไป"
- ใบเสร็จ: Blade view เฉพาะ + `@media print` กว้างตาม `paper_width` (58/80 มม.) —
  หัวร้านจาก workspace, เลขที่บิล, รายการ, VAT (ถ้าจด: "ใบกำกับภาษีอย่างย่อ" + เลขผู้เสียภาษี +
  "ราคารวมภาษีมูลค่าเพิ่ม"), ท้ายด้วย receipt_footer · พิมพ์ซ้ำได้จากประวัติบิล
- กฎบ้านที่เกี่ยวโดยตรง: ปุ่ม disabled ใช้ `disabled:opacity-60` · **สีหมวดสินค้า**เป็น palette
  คงที่ map ใน PHP array (ห้าม `bg-{{ $color }}-50`) · ทุก confirm (void/ปิดกะ) ใช้
  SweetAlert2 `data-confirm` · toast ผ่าน `session('status')`
- ทุกข้อความมีคู่ TH/EN ใน `lang/{th,en}/app.php` ใต้ `app.pos.*` — ภาษาไทยใช้คำบ้าน ๆ:
  "ปิดบิล", "เงินทอน", "เปิดกะ/ปิดกะ", "ยกเลิกบิล" (ไม่ใช่ "void transaction")

---

## 7. แพ็กเกจ & การขาย (`config/pos-plans.php`)

ราคาวางให้เข้าชุดกับของเดิม (queue, accounting ฿249/฿599) — ปรับได้ที่ config ที่เดียว:

| | Free ฿0 | Starter ฿299/ด. | Pro ฿699/ด. |
|---|---|---|---|
| บิล/เดือน (`max_sales_per_month`) | 30 | 1,000 | ไม่จำกัด |
| สินค้า (`max_products`) | 20 | ไม่จำกัด | ไม่จำกัด |
| เงินสด + PromptPay QR | ✓ | ✓ | ✓ |
| กะ + รายงานขาย | ✓ | ✓ | ✓ |
| ส่งออก CSV (`flags.csv_export`) | — | ✓ | ✓ |
| เชื่อมระบบบัญชี (`flags.accounting_sync`) | — | — | ✓ |

- PromptPay ให้ตั้งแต่ Free — เป็นจุดขายหลักของ POS ไทย จำกัดที่ "ปริมาณ" แทน
- เกินโควตา → toast พาไป `/pos/billing` (enforce ทั้งหน้า create และตอน submit แบบ AccountingPlan)
- flow ซื้อ: เลือกแพ็กเกจ → QR PromptPay ของแพลตฟอร์ม (setting `pos_billing.*` โครงเดียวกับ
  `queue_billing.*`) → แนบสลิป → `SlipVerifier` → แอดมินยืนยันที่ `admin.pos.payments` → ตั้ง
  `pos_plan/_until` — ใช้ตาราง pattern เดียวกับ `queue_payments` (สร้าง `pos_plan_payments`)
- การตลาด: เพิ่มการ์ด POS ในหน้า `/services` + เปิด toggle `products.pos.enabled` เมื่อพร้อมจริง

---

## 8. แผนการสร้าง — 5 เฟส (ตัดจบ–ใช้ได้จริงทุกเฟส)

> ทดสอบด้วย feature test ต่อเฟสแบบโมดูลบัญชี · sandbox นี้รัน migrate/phpunit ไม่ได้
> (ไม่มี vendor/.env) — เขียนแล้ว push ให้ user รันผ่านปุ่ม `/admin/system` ตาม flow เดิม

### Phase 1 — โครง + คลังสินค้า (~2–3 วัน)
- Migrations: `pos_settings`, `pos_categories`, `pos_products`
- ProductGate key `pos` (เริ่มปิด = admin เห็นคนเดียว) · ลงทะเบียน admin-products ·
  โครง lang `app.pos.*` (TH/EN) · เมนู POS ใน nav ของ app
- หน้า CRUD สินค้า/หมวด (+ รูป, บาร์โค้ด) · หน้า `pos/settings`
- **Tests:** ProductCrudTest, SettingsTest, scope ข้าม workspace ต้องมองไม่เห็นกัน
- **DoD:** เพิ่มสินค้า 20 ตัวจากมือถือ/แท็บเล็ตได้ลื่น

### Phase 2 — จอขาย + เงินสด + ใบเสร็จ (~4–5 วัน) ← หัวใจ
- Migrations: `pos_sales`, `pos_sale_items`, `pos_sale_payments`
- `Checkout` + `SaleNumber` + จอขาย (Alpine) + modal เงินสด + ใบเสร็จ print view +
  ประวัติบิล + `VoidSale`
- **Tests:** CheckoutTest (คิดเงิน server-side, VAT inclusive, กัน double-submit ด้วย
  client_uuid, ตัดสต๊อก, สต๊อกติดลบ+เตือน), SaleNumberTest (เลขชน retry),
  VoidTest (สิทธิ์/คืนสต๊อก/AuditLog), ReceiptTest
- **DoD:** **ร้านเราใช้ขายจริงได้ตั้งแต่จบเฟสนี้** — dogfood ก่อนขายลูกค้า

### Phase 3 — กะ + PromptPay + รายงาน (~3 วัน)
- Migration: `pos_shifts` (+ ผูก shift_id เข้ากระบวนการขาย)
- เปิด/ปิดกะ + กระทบยอดเงินสด · จ่ายด้วย PromptPay QR ของร้าน · หน้า `pos/reports` + CSV
- **Tests:** ShiftTest (expected vs counted, ห้ามขายนอกกะ, ส่วนต่าง→AuditLog), ReportsTest
- **DoD:** ปิดร้านแล้วรู้ทันทีว่าเงินขาด/เกินเท่าไร ใครขายอะไรไปบ้าง

### Phase 4 — แพ็กเกจ + เปิดขายจริง (~2–3 วัน)
- Migration: `workspaces.pos_plan/_until` + `pos_plan_payments`
- `PosPlan` + enforce limit + `/pos/billing` + แอดมินยืนยันสลิป + แอดมินตั้งแพ็กเกจ +
  การ์ดหน้า `/services`
- **Tests:** PosPlanTest (โควตา/หมดอายุ→free/flag CSV), BillingFlowTest
- **DoD:** ลูกค้าภายนอกสมัคร → ใช้ Free → จ่ายอัปเกรดเองได้ครบวงจร → **เปิด toggle ขายจริง**

### Phase 5 — เชื่อมบัญชี + ของเสริม (ทำตามแรงดึงจากลูกค้า)
- `AccountingBridge`: ปุ่ม "ส่งยอดขายเข้าบัญชี" รายวัน (journal สรุป + `Inventory::issue`
  สำหรับสินค้าที่ลิงก์) — flag ของ Pro
- ตามด้วย (เรียงตามเสียงลูกค้า): สมาชิก/แต้ม · split payment UI · PIN พนักงาน ·
  พิมพ์ฉลากบาร์โค้ด · ครัว/คิวออเดอร์ (ต่อกับ product queue เดิมได้)

---

## 9. ความเสี่ยง & วิธีกัน

| ความเสี่ยง | วิธีกัน |
|---|---|
| ราคา/ยอดถูกปลอมจาก client | `Checkout` คิดทุกบาทจาก DB — client ส่งได้แค่ id/qty/discount และ discount มีเพดาน |
| กดปิดบิลซ้ำ (เน็ตช้า/มือลั่น) | `client_uuid` unique ต่อบิล (gen ตอนเปิดตะกร้า) — ซ้ำ = คืนบิลเดิม ไม่สร้างใหม่ + disable ปุ่มตอน submit |
| เลขบิลชนเมื่อขายพร้อมกัน 2 เครื่อง | transaction + unique `(workspace_id, number)` + retry ใน `SaleNumber` |
| VAT ปัดเศษเพี้ยน | ถอด VAT ที่ "ยอดรวมบิล" ครั้งเดียวด้วย `Money` (ไม่ถอดรายบรรทัดแล้วบวกกัน) |
| เครื่องพิมพ์ thermal ต่างรุ่นต่างใจ | print CSS เรียบที่สุด ฟอนต์เดียว ไม่มีรูป ทดสอบทั้ง 58/80 + มีปุ่มพิมพ์ซ้ำเสมอ |
| เน็ตหน้าร้านหลุด | v1 ไม่ offline — แสดงสถานะชัด + ตะกร้าอยู่ใน Alpine state ไม่หายจนกว่าจะ refresh |
| สต๊อกเพี้ยนจากการขายพร้อมกัน | decrement แบบ atomic (`update … set on_hand = on_hand - ?`) ใน transaction ของ Checkout |
| ขายเกินโควตาแพ็กเกจกลางคัน | เช็คตอน submit อีกรอบ (ไม่ใช่แค่ตอนเปิดจอ) แบบ `AccountingPlan::reasonCannotCreateInvoice` |

---

## 10. เช็คลิสต์กฎบ้าน (จาก `CLAUDE.md` — ทวนทุก PR)

- [ ] migration มี `down()` จริง + คอลัมน์ใหม่ใช้ `->after()`
- [ ] lang key ครบคู่ TH/EN เสมอ + ไทยใช้คำที่คนทั่วไปเข้าใจ
- [ ] ไม่มี `confirm()/alert()` — ใช้ `data-confirm` (SweetAlert2)
- [ ] ไม่มี dynamic Tailwind class — สีหมวด/ป้ายใช้ map PHP
- [ ] ปุ่ม disabled อ่านออก (`disabled:opacity-60`)
- [ ] privileged action ลง `AuditLog::record` ครบ
- [ ] ไม่มี composer/npm dep ใหม่ (QR = CDN เดิม)
- [ ] ห้ามรัน `php artisan migrate` ใน sandbox — push แล้วให้ user กดที่ `/admin/system`
