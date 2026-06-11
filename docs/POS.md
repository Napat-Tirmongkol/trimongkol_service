# POS — เอกสารออกแบบ + แผนการสร้าง

ระบบขายหน้าร้าน (Point of Sale) — product ตัวที่ 5 ของแพลตฟอร์ม ถัดจาก scanner / queue /
accounting / social ออกแบบให้ **ใช้ขายของเองได้จริง และขายเป็นแพ็กเกจรายเดือนให้ลูกค้า SME ต่อได้**
(ร้านค้า ร้านอาหาร คลินิก ซาลอน — ตามกลุ่มเป้าหมายใน `PRODUCT.md`)

รองรับ **2 โหมดร้าน** เลือกตอนตั้งค่า (เปลี่ยนทีหลังได้):

- **โหมดร้านค้า (retail)** — หยิบของ → คิดเงินจบในจอเดียว (มินิมาร์ท ร้านขายของ คลินิก ซาลอน)
- **โหมดร้านอาหาร (restaurant)** — เปิดโต๊ะ → สั่งเพิ่มได้เรื่อย ๆ → ส่งครัว → เก็บเงินตอนลูกค้ากลับ
  (ร้านตามสั่ง ร้านกาแฟ ร้านนั่งกิน) — เป็น **ชั้น "ออเดอร์" ที่วางทับ engine ขายตัวเดียวกัน**

หลักการใหญ่: **reuse ของที่มีอยู่ให้มากที่สุด** — workspace multi-tenancy, ProductGate,
pattern แพ็กเกจของ queue/accounting, PromptPay + SlipVerifier, SweetAlert2, brand UI
**ไม่เพิ่ม composer/npm dependency ใหม่เลย** (QR ใช้ qrcode CDN ตัวเดิมของหน้า `queues/pay`)

---

## 1. ขอบเขต

### v1 ทำอะไรได้ (เมื่อครบทุกเฟส)

ทั้งสองโหมด:

- คลังสินค้า/เมนู + หมวดหมู่ + บาร์โค้ด + รูป + สต๊อกอย่างง่าย
- รับเงิน: เงินสด (คิดเงินทอน) · PromptPay QR ของร้านเอง · โอน/บัตร (บันทึกอย่างเดียว)
- ใบเสร็จรับเงิน / ใบกำกับภาษีอย่างย่อ พิมพ์ผ่าน browser (กระดาษ 58/80 มม.)
- เปิด–ปิดกะ + กระทบยอดเงินสด (X/Z report) · ยกเลิกบิลพร้อมเหตุผล + คืนสต๊อก
- รายงานขาย รายวัน/ช่วงเวลา แยกตามวิธีจ่าย/สินค้า/พนักงาน + ส่งออก CSV
- แพ็กเกจ Free/Starter/Pro ขายผ่าน PromptPay + แนบสลิป เหมือน queue

เพิ่มเฉพาะโหมดร้านอาหาร:

- ผังโต๊ะ + สถานะโต๊ะ (ว่าง/มีลูกค้า) + ย้ายโต๊ะ
- ออเดอร์ค้างต่อโต๊ะ — สั่งเพิ่มได้หลายรอบจนกว่าจะเก็บเงิน · ขายกลับบ้าน (ไม่มีโต๊ะ) ได้
- ตัวเลือกเมนู (modifier): ระดับเผ็ด/หวาน, ท็อปปิ้ง ± ราคา + โน้ตต่อจาน ("ไม่ใส่ผัก")
- ส่งครัวเป็นรอบ ๆ + พิมพ์ใบส่งครัว (kitchen ticket)
- Service charge % (ร้านที่เก็บ) คิดให้อัตโนมัติเฉพาะทานที่ร้าน

### ตั้งใจ *ยังไม่ทำ* ใน v1 (พร้อมเหตุผล)

| เรื่อง | เหตุผล / ทางไปต่อ |
|---|---|
| โหมด offline | service worker + sync ซับซ้อนมาก — v1 กัน double-submit ให้ดีพอ แล้วค่อยประเมิน |
| จ่ายหลายวิธีในบิลเดียว (split payment) | schema รองรับแล้ว (`pos_sale_payments` แยกตาราง) — เปิดที่ UI ทีหลัง |
| แยกบิลรายคน / รวมโต๊ะ | ซับซ้อนเชิง UI สูง — Phase R3 (ย้ายโต๊ะทำได้ตั้งแต่ R1) |
| ลูกค้าสแกน QR สั่งเองที่โต๊ะ | Phase R3 — reuse pattern public token ของ queue (`/q/{token}`) ได้เลย เป็นจุดขาย Pro |
| Kitchen Display (จอครัว) | v1 ใช้ใบส่งครัวพิมพ์ก่อน — KDS เป็นหน้า auto-refresh ค่อยเพิ่มได้ไม่กระทบโครง |
| สูตรอาหาร/ตัดสต๊อกวัตถุดิบ (BOM) | ลึกเกิน POS — v1 track_stock รายเมนูที่นับได้ (เช่น น้ำขวด) · ต้นทุนละเอียดเป็นงานฝั่งบัญชี |
| สมาชิก/สะสมแต้ม | Phase 5 — ตาราง `pos_customers` ค่อยเพิ่ม |
| หลายสาขาใน workspace เดียว | ใช้ 1 สาขา = 1 workspace ไปก่อน (สลับ workspace ได้อยู่แล้ว) |
| ใบกำกับภาษีเต็มรูป | เป็นงานของระบบบัญชี (`SalesInvoicing`) — POS ออกอย่างย่อพอ แล้วชี้ลูกค้าไป accounting |
| ฮาร์ดแวร์ลิ้นชักเงิน/เครื่องชั่ง | ผูกกับ driver เฉพาะรุ่น — นอกขอบเขต web app |

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
ถอด VAT จากยอดรวมบิล (`vat = total × 7/107` ปัดครั้งเดียวด้วย Money) ถ้าไม่จด เป็นใบเสร็จธรรมดา
หัวเอกสารดึงจากคอลัมน์ที่มีแล้วใน `workspaces` (`company_name, tax_id, branch, phone, company_address`)
ร้านมี service charge: `total = (subtotal − discount) × (1 + SC%)` แล้วค่อยถอด VAT จาก total

**D5 — สต๊อก POS เป็น counter อย่างง่าย ติดลบได้ (มีเตือน)**
หน้าร้านจริงห้ามขายไม่ได้เพราะตัวเลขในระบบเพี้ยน — ดีฟอลต์ขายต่อได้ + โชว์ป้ายเตือนสต๊อกติดลบ
(ปิดได้ใน settings) ส่วนต้นทุนถัวเฉลี่ย/ลง journal เป็นหน้าที่ `Accounting\Inventory` ตอน sync (Phase 5)

**D6 — การจ่ายเงินแยกตาราง `pos_sale_payments` ตั้งแต่วันแรก**
v1 UI สร้าง 1 บิล = 1 payment แต่ schema พร้อมรับ split payment โดยไม่ต้องแก้โครงทีหลัง

**D7 — เลขที่บิล running ต่อ workspace**
รูปแบบ `POS-YYYYMM-#####` สร้างใน DB transaction + unique index `(workspace_id, number)`
ชนกันให้ retry — กันเครื่อง 2 เครื่องขายพร้อมกัน

**D8 — บิล (sale) แก้ไขไม่ได้ (immutable) มีแต่ void**
ปิดบิลแล้วห้ามแก้ทุกช่อง — แก้ผิดให้ void (เก็บคนทำ+เหตุผล) แล้วตีบิลใหม่ ลดช่องโกงและทำให้ยอดเชื่อถือได้

**D9 — ร้านอาหาร = "ชั้นออเดอร์" ทับ engine เดิม ไม่ใช่ product แยก**
`pos_orders` เป็นเอกสาร**แก้ไขได้**ระหว่างลูกค้านั่งกิน (เพิ่มจาน/ยกเลิกจาน/ย้ายโต๊ะ) —
ตอนเก็บเงินค่อยแปลงเป็น `pos_sales` ผ่าน `Checkout` ตัวเดียวกับ retail แล้ว sale ก็ immutable
ตาม D8 เหมือนเดิม → โหมด retail คือกรณีพิเศษ "ออเดอร์จบทันที" (ตะกร้าอยู่ใน Alpine ไม่ลง DB)
ได้สองโหมดโดยมี engine คิดเงิน/ใบเสร็จ/รายงาน/แพ็กเกจ ชุดเดียว

**D10 — modifier บนรายการที่สั่งเก็บเป็น JSON snapshot**
ชื่อ+ราคา ณ เวลาสั่ง ฝังในแถว item (เหมือน name/sku ที่ snapshot อยู่แล้ว) — เมนูแก้ทีหลัง
บิลเก่า/ใบครัวเก่ายังถูกต้อง ส่วนตัวนิยาม modifier เป็นตาราง master ปกติ (แก้ได้ ไม่กระทบประวัติ)

---

## 3. Data model

```
workspaces 1─n pos_categories 1─n pos_products ─(optional)→ accounting_products
                               pos_products n─n pos_modifier_groups 1─n pos_modifiers
workspaces 1─n pos_tables
workspaces 1─n pos_orders 1─n pos_order_items          ← ร้านอาหาร (แก้ไขได้)
workspaces 1─n pos_shifts 1─n pos_sales 1─n pos_sale_items     ← ปิดแล้วแก้ไม่ได้
                                        1─n pos_sale_payments
           pos_orders ──(ตอนเก็บเงิน)──→ pos_sales (sale_id ผูกกลับ)
workspaces 1─1 pos_settings
```

ทุก migration **ต้องมี `down()`** และคอลัมน์ใหม่ใช้ `->after()` ตามกฎบ้าน · ทุกตารางมี
`workspace_id` + ใช้ trait `BelongsToWorkspace`

### `pos_settings` (1 แถว/workspace)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| workspace_id | FK unique | |
| shop_mode | enum retail/restaurant default retail | สลับโหมดได้ (ออเดอร์ค้างต้องเคลียร์ก่อน) |
| promptpay_id | string nullable | เบอร์/เลขผู้เสียภาษีรับเงินของ "ร้าน" (ไม่ใช่ของแพลตฟอร์ม) |
| vat_registered | boolean default false | จด VAT → พิมพ์ใบกำกับภาษีอย่างย่อ |
| vat_rate | decimal(5,2) default 7 | |
| service_charge_rate | decimal(5,2) nullable | ร้านอาหารที่เก็บ SC — คิดเฉพาะทานที่ร้าน |
| receipt_footer | string nullable | เช่น "ขอบคุณที่อุดหนุนค่ะ" |
| paper_width | enum 58/80 default 58 | ความกว้างกระดาษใบเสร็จ/ใบครัว |
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
| track_stock | boolean default false | เปิดเฉพาะสินค้าที่นับได้ (น้ำขวด ฯลฯ) |
| on_hand | decimal(12,2) default 0 | รองรับหน่วยชั่ง (กก.) |
| low_stock_threshold | decimal(12,2) nullable | ต่ำกว่านี้ขึ้นป้ายเตือน |
| is_active | boolean default true | ปิดชั่วคราว (ของหมด) ไม่ต้องลบ |
| accounting_product_id | FK nullable | สะพานไป accounting (Phase 5) |

### Modifiers (โหมดร้านอาหาร — ร้านค้าไม่บังคับใช้)

- **`pos_modifier_groups`** — `workspace_id` · `name` ("ระดับความเผ็ด", "ท็อปปิ้ง") ·
  `min_select` / `max_select` (เผ็ด: 1/1 = บังคับเลือก, ท็อปปิ้ง: 0/null = ตามใจ) · `sort_order`
- **`pos_modifiers`** — `group_id` · `name` ("เผ็ดน้อย", "เพิ่มไข่ดาว") · `price_delta`
  decimal(18,2) default 0 · `is_active` · `sort_order`
- **`pos_product_modifier_group`** — pivot ผูกเมนู↔กลุ่มตัวเลือก

### `pos_tables` (โหมดร้านอาหาร)

`workspace_id` · `name` ("โต๊ะ 1", "บาร์ 2") · `zone` nullable ("ชั้นบน") · `sort_order` ·
`is_active` · unique `(workspace_id, name)` — สถานะโต๊ะ**ไม่เก็บ** (derive จากออเดอร์ที่เปิดอยู่)

### `pos_orders` (โหมดร้านอาหาร — เอกสารแก้ไขได้)

| คอลัมน์ | หมายเหตุ |
|---|---|
| workspace_id / table_id | table nullable — กลับบ้าน/เดลิเวอรี่ไม่มีโต๊ะ |
| order_type | dine_in / takeaway / delivery |
| status | open / paid / cancelled |
| pax | จำนวนลูกค้า nullable (ไว้ดูยอดต่อหัว) |
| opened_by / opened_at | พนักงานที่เปิดโต๊ะ |
| sale_id | FK nullable — ชี้บิลที่เกิดตอนเก็บเงิน |
| closed_at / cancelled_by / cancel_reason | |
| note | nullable |

ห้ามมีออเดอร์ open ซ้อนโต๊ะเดียวกัน (unique partial เชิง logic — เช็คใน service ภายใต้ transaction)

### `pos_order_items`

`order_id` · `product_id` (FK) · `name` + `unit_price` (**snapshot รวม price_delta ของ
modifier แล้ว**) · `quantity` · `modifiers` JSON snapshot `[{name, price}]` · `note`
("ไม่ใส่ผัก") · `round_no` (รอบที่ส่งครัว — null = ยังไม่ส่ง) · `sent_at` ·
`status` (pending / sent / cancelled) · `cancelled_by` / `cancel_reason`

กติกา: แก้/ลบเสรีตอน `pending` · หลัง `sent` ยกเลิกได้เฉพาะ owner/admin + ต้องใส่เหตุผล + log

### `pos_shifts`

`workspace_id` · `status` (open/closed) · `opened_by`/`opened_at`/`opening_cash` ·
`closed_by`/`closed_at`/`expected_cash`/`counted_cash`/`cash_difference` ·
`sales_count`/`sales_total` (สรุปตอนปิดกะ) · `note` — เปิดได้ทีละ 1 กะ/workspace
ยอดเข้ากะตาม "เวลาเก็บเงิน" (ออเดอร์เปิดข้ามกะได้ — ดู §9)

### `pos_sales` (ปิดแล้วแก้ไม่ได้)

| คอลัมน์ | หมายเหตุ |
|---|---|
| workspace_id / shift_id / user_id | user_id = คนเก็บเงิน |
| order_id / order_type / table_name / pax | snapshot จากออเดอร์ (retail: order_id null, type = takeaway-style ขายจบทันที → `retail`) |
| number | `POS-YYYYMM-#####` unique `(workspace_id, number)` |
| status | completed / voided |
| subtotal / discount | decimal(18,2) |
| service_charge_rate / service_charge_amount | 0 ถ้าไม่เก็บ/ไม่ใช่ทานที่ร้าน |
| total | = (subtotal − discount) + service charge |
| vat_rate / vat_amount | snapshot ณ เวลาขาย (ถอดจาก total ถ้าจด VAT) |
| client_uuid | uuid unique — กัน double-submit (ดู §9) |
| note / voided_by / voided_at / void_reason | |

### `pos_sale_items`

`sale_id` · `product_id` (nullable — สินค้าโดนลบทีหลัง บิลเก่าต้องยังอ่านได้) ·
`name` + `sku` (**snapshot**) · `unit_price` (รวม modifier แล้ว) · `quantity` decimal(12,2) ·
`modifiers` JSON snapshot · `discount` · `line_total`

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
| `Pos\Checkout` | หัวใจระบบ — รับรายการ (จากตะกร้า retail หรือจาก `pos_orders`) + payment แล้วใน **DB transaction เดียว**: ดึงราคาสินค้า+modifier จาก DB (***ไม่เชื่อราคาจาก client เด็ดขาด***) → คิด subtotal/discount/SC/total/VAT ด้วย `Money` → ออกเลขบิล → insert sale+items+payments → ตัดสต๊อกเฉพาะ track_stock → (ร้านอาหาร) ตั้ง order = paid + ผูก sale_id → คืน sale |
| `Pos\Orders` | โหมดร้านอาหาร — เปิดออเดอร์ (กันซ้อนโต๊ะ), เพิ่ม/แก้/ยกเลิกรายการตามกติกาสถานะ, ย้ายโต๊ะ, ยกเลิกทั้งออเดอร์ (owner/admin + เหตุผล + log) |
| `Pos\KitchenTicket` | "ส่งครัว": ตีตรา round_no + sent_at ให้รายการ pending แล้วเปิดหน้าพิมพ์ใบส่งครัวของรอบนั้น |
| `Pos\VoidSale` | ตรวจสิทธิ์ (owner/admin) → ตั้ง voided + เหตุผล → คืนสต๊อก → `AuditLog::record('pos.sale.void', …)` |
| `Pos\ShiftReport` | เปิด/ปิดกะ + expected_cash = opening + ยอดขายเงินสดในกะ เทียบ counted |
| `Pos\Reports` | ยอดขายช่วงวันที่ แยก วิธีจ่าย/สินค้า/หมวด/พนักงาน/ประเภทออเดอร์ + ยอด SC รวม + CSV (UTF-8 BOM แบบ `TaxReporting`) — CSV เป็น flag ของแพ็กเกจ |
| `Pos\AccountingBridge` *(Phase 5)* | โพสต์สรุปขายรายวันเข้า ledger: DR เงินสด/ธนาคารแยกตาม method / CR รายได้ขาย + CR ภาษีขาย ผ่าน `LedgerPosting::post` และตัดสต๊อกฝั่งบัญชีผ่าน `Inventory::issue` สำหรับสินค้าที่ลิงก์แล้ว |

จุดที่ต้อง `AuditLog::record`: แก้ `pos_settings` · void บิล · ยกเลิกออเดอร์/ยกเลิกจานหลังส่งครัว ·
ปิดกะที่มีส่วนต่างเงินสด · แอดมินตั้งแพ็กเกจ · แอดมินยืนยัน/ปฏิเสธสลิป

---

## 5. Routes + สิทธิ์

ใต้ `auth + verified` + `product:pos` (ProductGate key ใหม่ `pos` — soft launch ได้:
ปิดไว้ให้ admin เห็นคนเดียวจนกว่าจะพร้อม เหมือนตอนเปิด accounting)

```
GET  /pos                         จุดเข้า: retail → จอขาย · restaurant → ผังโต๊ะ
                                  (ยังไม่เปิดกะ → บังคับเปิดกะก่อน)
POST /pos/sales                   ปิดบิล retail (Checkout)
GET  /pos/sales                   ประวัติบิล + ฟิลเตอร์วัน/สถานะ/พนักงาน
GET  /pos/sales/{sale}            รายละเอียดบิล
GET  /pos/sales/{sale}/receipt    หน้าใบเสร็จสำหรับพิมพ์ (print CSS)
POST /pos/sales/{sale}/void       ยกเลิกบิล                     [owner/admin]

— โหมดร้านอาหาร —
POST /pos/orders                  เปิดออเดอร์ (เลือกโต๊ะ/กลับบ้าน + pax)
GET  /pos/orders/{order}          จอสั่งอาหารของออเดอร์นั้น
POST /pos/orders/{order}/items    เพิ่มรายการ (+modifiers, โน้ต)
PATCH|DELETE /pos/orders/{order}/items/{item}   แก้จำนวน/ยกเลิกจาน (กติกาตามสถานะ)
POST /pos/orders/{order}/send     ส่งครัว → เปิดใบส่งครัวรอบล่าสุด
GET  /pos/orders/{order}/kitchen-ticket/{round}  พิมพ์ใบส่งครัว (พิมพ์ซ้ำได้)
POST /pos/orders/{order}/move     ย้ายโต๊ะ
POST /pos/orders/{order}/checkout เก็บเงิน → Checkout เดียวกับ retail
POST /pos/orders/{order}/cancel   ยกเลิกทั้งออเดอร์              [owner/admin]
CRUD /pos/tables, /pos/modifier-groups                           [owner/admin]

— เหมือนกันทั้งสองโหมด —
GET|POST /pos/shifts              เปิดกะ · GET = ประวัติกะ
GET  /pos/shifts/{shift}          รายงานกะ (X/Z)
POST /pos/shifts/{shift}/close    ปิดกะ + กรอกเงินนับจริง
CRUD /pos/products, /pos/categories                              [owner/admin]
GET  /pos/reports (+ /export CSV)                                [owner/admin]
GET|PATCH /pos/settings                                          [owner]
GET  /pos/billing · GET|POST /pos/billing/{plan}   ซื้อแพ็กเกจ (โคลน QueueBilling)
```

สิทธิ์ใน workspace: **member ขาย/เปิดออเดอร์/ส่งครัว/เก็บเงินได้** · products, tables,
modifiers, settings, void, ยกเลิกหลังส่งครัว, reports = owner/admin — enforce ใน controller
ผ่าน `WorkspaceMember.role` (แบบเดียวกับ queue)

ฝั่งแอดมินแพลตฟอร์ม — ลงทะเบียน `config/admin-products.php` ตามสูตร 4 ขั้นใน `docs/ADMIN.md`:

```
admin.pos.dashboard   ภาพรวม: จำนวนร้านที่ใช้ (แยกโหมด), บิล/วัน, รายได้แพ็กเกจ
admin.pos.payments    ยืนยันสลิปซื้อแพ็กเกจ (โคลนหน้า queue payments)
```

`+ PATCH /admin/workspaces/{id}/pos-plan` ให้แอดมินตั้งแพ็กเกจมือ (ขายตรง/ของแถม) + AuditLog

---

## 6. UI/UX — อิง `tirmongkol-design` (ห้าม override สีแบรนด์)

### จอขาย retail (tablet-first, แตะเป้าหมาย ≥ 44px, Alpine.js จัดการ state ตะกร้า)

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

### โหมดร้านอาหาร — ผังโต๊ะ + จอออเดอร์

```
ผังโต๊ะ (/pos)                          จอออเดอร์ (โต๊ะ 3)
┌─────────────────────────────┐  แตะโต๊ะ  ┌──────────────────────────────┬─────────────────┐
│ โซน: [ทั้งหมด] [ในร้าน] [นอก]│  ──────→ │ เมนู (grid เดิม + modifier    │ โต๊ะ 3 · 2 ที่นั่ง │
│ ┌────┐ ┌────┐ ┌────┐ ┌────┐ │          │  popup ตอนแตะเมนูที่มีตัวเลือก:│ ── รอบ 1 (ส่งแล้ว)│
│ │ 1  │ │ 2  │ │ 3● │ │ 4  │ │          │  เผ็ดน้อย/กลาง/มาก + โน้ต)    │ กะเพรา ×1  60.- │
│ │ว่าง│ │ว่าง│ │ 450│ │ว่าง│ │          │                              │  └ เผ็ดน้อย+ไข่ดาว│
│ └────┘ └────┘ └────┘ └────┘ │          │                              │ ── ยังไม่ส่ง ──   │
│ [+ ขายกลับบ้าน]              │          │                              │ ชาเย็น ×2  70.- │
│  ว่าง=slate · มีลูกค้า=amber │          │                              │ [ส่งครัว] [เก็บเงิน]│
└─────────────────────────────┘          └──────────────────────────────┴─────────────────┘
```

- ช่องค้นหา autofocus ตลอด — **barcode scanner = keyboard wedge** ยิงแล้ว Enter เข้าตะกร้าเอง
- เมนูที่มี modifier บังคับ (min_select ≥ 1) เด้ง popup ก่อนลงตะกร้า/ออเดอร์เสมอ
- ผังโต๊ะ refresh เบา ๆ (poll ทุก ~10 วิ) — เครื่องอื่นเปิดโต๊ะแล้วเห็นกัน
- กดเก็บเงิน → modal เลือกวิธีจ่าย: **เงินสด** (ปุ่มแบงค์ด่วน 20/50/100/500/1,000 + พอดี +
  เงินทอนตัวเลขใหญ่มาก) / **PromptPay** (QR จาก `PromptPay::payload(promptpay_id ของร้าน, total)`
  render ด้วย qrcode CDN — pattern เดียวกับ `queues/pay.blade.php` แล้วกด "รับเงินแล้ว") /
  **โอน/บัตร** (ใส่เลขอ้างอิง)
- ปิดบิลแล้ว → จอเงินทอน + ปุ่ม "พิมพ์ใบเสร็จ" / "บิลถัดไป" (ร้านอาหาร: โต๊ะกลับเป็นว่าง)
- **ใบเสร็จ**: Blade view + `@media print` กว้างตาม `paper_width` — หัวร้านจาก workspace,
  เลขที่บิล, โต๊ะ/ประเภท, รายการ+ตัวเลือก, ส่วนลด, **Service charge**, VAT (ถ้าจด:
  "ใบกำกับภาษีอย่างย่อ" + เลขผู้เสียภาษี + "ราคารวมภาษีมูลค่าเพิ่ม"), ท้ายด้วย receipt_footer ·
  พิมพ์ซ้ำได้จากประวัติบิล
- **ใบส่งครัว**: ฟอนต์ใหญ่ ไม่มีราคา — โต๊ะ/รอบ/เวลา/คนสั่ง + จานใหม่รอบนั้น + ตัวเลือก + โน้ต
- กฎบ้านที่เกี่ยวโดยตรง: ปุ่ม disabled ใช้ `disabled:opacity-60` · **สีหมวด/สถานะโต๊ะ**เป็น
  palette คงที่ map ใน PHP array (ห้าม `bg-{{ $color }}-50`) · ทุก confirm (void/ยกเลิกจาน/
  ปิดกะ) ใช้ SweetAlert2 `data-confirm` · toast ผ่าน `session('status')`
- ทุกข้อความมีคู่ TH/EN ใน `lang/{th,en}/app.php` ใต้ `app.pos.*` — ภาษาไทยใช้คำบ้าน ๆ:
  "เปิดโต๊ะ", "ส่งครัว", "เก็บเงิน", "ยกเลิกบิล" (ไม่ใช่ "fire order / void transaction")

---

## 7. แพ็กเกจ & การขาย (`config/pos-plans.php`)

ราคาวางให้เข้าชุดกับของเดิม (queue, accounting ฿249/฿599) — ปรับได้ที่ config ที่เดียว:

| | Free ฿0 | Starter ฿299/ด. | Pro ฿699/ด. |
|---|---|---|---|
| บิล/เดือน (`max_sales_per_month`) | 30 | 1,000 | ไม่จำกัด |
| สินค้า/เมนู (`max_products`) | 20 | ไม่จำกัด | ไม่จำกัด |
| โหมดร้านอาหาร + โต๊ะ (`max_tables`) | ✓ (5 โต๊ะ) | ✓ ไม่จำกัด | ✓ ไม่จำกัด |
| เงินสด + PromptPay QR | ✓ | ✓ | ✓ |
| กะ + รายงานขาย | ✓ | ✓ | ✓ |
| ส่งออก CSV (`flags.csv_export`) | — | ✓ | ✓ |
| ลูกค้าสแกนสั่งเองที่โต๊ะ (`flags.qr_ordering`, R3) | — | — | ✓ |
| เชื่อมระบบบัญชี (`flags.accounting_sync`) | — | — | ✓ |

- PromptPay และโหมดร้านอาหารให้ตั้งแต่ Free — เป็นจุดขายหลักของ POS ไทย จำกัดที่ "ปริมาณ" แทน
  (ร้านตามสั่งเล็ก ๆ 5 โต๊ะใช้ฟรีได้จริง → โตแล้วค่อยจ่าย)
- เกินโควตา → toast พาไป `/pos/billing` (enforce ทั้งหน้า create และตอน submit แบบ AccountingPlan)
- flow ซื้อ: เลือกแพ็กเกจ → QR PromptPay ของแพลตฟอร์ม (setting `pos_billing.*` โครงเดียวกับ
  `queue_billing.*`) → แนบสลิป → `SlipVerifier` → แอดมินยืนยันที่ `admin.pos.payments` → ตั้ง
  `pos_plan/_until` — ใช้ตาราง pattern เดียวกับ `queue_payments` (สร้าง `pos_plan_payments`)
- การตลาด: เพิ่มการ์ด POS ในหน้า `/services` + เปิด toggle `products.pos.enabled` เมื่อพร้อมจริง

---

## 8. แผนการสร้าง — ตัดจบ–ใช้ได้จริงทุกเฟส

> ทดสอบด้วย feature test ต่อเฟสแบบโมดูลบัญชี · sandbox นี้รัน migrate/phpunit ไม่ได้
> (ไม่มี vendor/.env) — เขียนแล้ว push ให้ user รันผ่านปุ่ม `/admin/system` ตาม flow เดิม

### Phase 1 — โครง + คลังสินค้า/เมนู (~2–3 วัน)
- Migrations: `pos_settings`, `pos_categories`, `pos_products`
- ProductGate key `pos` (เริ่มปิด = admin เห็นคนเดียว) · ลงทะเบียน admin-products ·
  โครง lang `app.pos.*` (TH/EN) · เมนู POS ใน nav ของ app
- หน้า CRUD สินค้า/หมวด (+ รูป, บาร์โค้ด) · หน้า `pos/settings` (รวมเลือกโหมดร้าน)
- **Tests:** ProductCrudTest, SettingsTest, scope ข้าม workspace ต้องมองไม่เห็นกัน
- **DoD:** เพิ่มเมนู 20 รายการจากมือถือ/แท็บเล็ตได้ลื่น

### Phase 2 — engine ขาย + เงินสด + ใบเสร็จ (~4–5 วัน) ← หัวใจ
- Migrations: `pos_sales`, `pos_sale_items`, `pos_sale_payments`
- `Checkout` + `SaleNumber` + จอขาย retail (Alpine) + modal เงินสด + ใบเสร็จ print view +
  ประวัติบิล + `VoidSale`
- **Tests:** CheckoutTest (คิดเงิน server-side, VAT inclusive, กัน double-submit ด้วย
  client_uuid, ตัดสต๊อก, สต๊อกติดลบ+เตือน), SaleNumberTest (เลขชน retry),
  VoidTest (สิทธิ์/คืนสต๊อก/AuditLog), ReceiptTest
- **DoD:** ร้านโหมด retail ใช้ขายจริงได้ตั้งแต่จบเฟสนี้

### Phase R1 — ชั้นร้านอาหาร: โต๊ะ + ออเดอร์ + ส่งครัว (~4–5 วัน)
- Migrations: `pos_tables`, `pos_orders`, `pos_order_items`, `pos_modifier_groups`,
  `pos_modifiers`, `pos_product_modifier_group` (+ คอลัมน์ order/SC บน `pos_sales`)
- ผังโต๊ะ + เปิดออเดอร์/กันซ้อนโต๊ะ + จอสั่ง (modifier popup + โน้ต) + ส่งครัวเป็นรอบ +
  ใบส่งครัว + ย้ายโต๊ะ + ยกเลิกจาน/ออเดอร์ตามกติกา + service charge + เก็บเงินผ่าน
  `Checkout` เดิม + CRUD โต๊ะ/ตัวเลือก
- **Tests:** OrderFlowTest (เปิด→สั่ง→ส่งครัว→สั่งเพิ่ม→เก็บเงิน→โต๊ะว่าง), กันออเดอร์ซ้อนโต๊ะ,
  ModifierPricingTest (ราคา+delta จาก DB), CancelAfterSentTest (สิทธิ์+log), ServiceChargeTest
  (สูตร SC→VAT), MoveTableTest
- **DoD:** **ร้านตามสั่ง 1 ร้านรับลูกค้าจริงได้ทั้งวัน** — นี่คือเฟส dogfood กับร้านนำร่อง
- *ถ้าลูกค้ากลุ่มแรกเป็น retail ล้วน สลับเฟสนี้ไปหลัง Phase 4 ได้ — เป็นชั้นอิสระ ไม่มีเฟสอื่นพึ่งมัน*

### Phase 3 — กะ + PromptPay + รายงาน (~3 วัน)
- Migration: `pos_shifts` (+ ผูก shift_id ตอนเก็บเงิน)
- เปิด/ปิดกะ + กระทบยอดเงินสด · จ่ายด้วย PromptPay QR ของร้าน · หน้า `pos/reports`
  (แยกวิธีจ่าย/สินค้า/พนักงาน/ประเภทออเดอร์ + ยอด SC) + CSV
- **Tests:** ShiftTest (expected vs counted, เตือนปิดกะที่มีออเดอร์ค้าง, ส่วนต่าง→AuditLog),
  ReportsTest
- **DoD:** ปิดร้านแล้วรู้ทันทีว่าเงินขาด/เกินเท่าไร เมนูไหนขายดี ใครขายเท่าไร

### Phase 4 — แพ็กเกจ + เปิดขายจริง (~2–3 วัน)
- Migration: `workspaces.pos_plan/_until` + `pos_plan_payments`
- `PosPlan` + enforce limit (บิล/เมนู/โต๊ะ) + `/pos/billing` + แอดมินยืนยันสลิป +
  แอดมินตั้งแพ็กเกจ + การ์ดหน้า `/services`
- **Tests:** PosPlanTest (โควตา/หมดอายุ→free/flag CSV/จำกัดโต๊ะ), BillingFlowTest
- **DoD:** ลูกค้าภายนอกสมัคร → ใช้ Free → จ่ายอัปเกรดเองได้ครบวงจร → **เปิด toggle ขายจริง**

### Phase 5 — เชื่อมบัญชี + ของเสริม (ทำตามแรงดึงจากลูกค้า)
- `AccountingBridge`: ปุ่ม "ส่งยอดขายเข้าบัญชี" รายวัน (journal สรุป + `Inventory::issue`
  สำหรับสินค้าที่ลิงก์) — flag ของ Pro
- **QR สั่งเองที่โต๊ะ (R3)**: token สาธารณะต่อโต๊ะแบบ `/q/{token}` ของ queue — ลูกค้าเปิดเมนู
  สั่งเข้าออเดอร์ → พนักงานกดรับ — flag Pro (~3–4 วัน)
- ตามด้วย (เรียงตามเสียงลูกค้า): แยกบิล/รวมโต๊ะ · Kitchen Display · สมาชิก/แต้ม ·
  split payment UI · PIN พนักงาน · พิมพ์ฉลากบาร์โค้ด

---

## 9. ความเสี่ยง & วิธีกัน

| ความเสี่ยง | วิธีกัน |
|---|---|
| ราคา/ยอดถูกปลอมจาก client | `Checkout`/`Orders` คิดทุกบาทจาก DB (รวม modifier delta) — client ส่งได้แค่ id/qty/discount และ discount มีเพดาน |
| กดปิดบิลซ้ำ (เน็ตช้า/มือลั่น) | `client_uuid` unique ต่อบิล — ซ้ำ = คืนบิลเดิม ไม่สร้างใหม่ + disable ปุ่มตอน submit |
| เลขบิลชนเมื่อขายพร้อมกัน 2 เครื่อง | transaction + unique `(workspace_id, number)` + retry ใน `SaleNumber` |
| สองเครื่องแก้ออเดอร์โต๊ะเดียวกัน | รายการเป็น append-only row (ชนกันยาก) · เปิดออเดอร์ซ้อนโต๊ะกันใน transaction · ผังโต๊ะ poll สถานะ |
| เก็บเงินแข่งกัน/สั่งเพิ่มระหว่างจ่าย | `Checkout` ล็อกแถวออเดอร์ (`lockForUpdate`) — จ่ายแล้วห้ามเพิ่มจาน, จ่ายซ้ำไม่ได้ |
| ยกเลิกจานหลังส่งครัวแล้ว | เฉพาะ owner/admin + เหตุผลบังคับ + AuditLog (กันเทคนิคโกง "ขายแล้วลบ") |
| ออเดอร์ค้างข้ามวัน (ลืมเก็บเงิน) | แดชบอร์ด/ผังโต๊ะโชว์อายุออเดอร์ · ปิดกะมีออเดอร์ค้าง → เตือน + ต้องยืนยัน (ยอดไปลงกะที่เก็บเงินจริง) |
| VAT/SC ปัดเศษเพี้ยน | คิด SC แล้วถอด VAT ที่ "ยอดรวมบิล" ครั้งเดียวด้วย `Money` (ไม่ถอดรายบรรทัดแล้วบวกกัน) |
| เครื่องพิมพ์ thermal ต่างรุ่นต่างใจ | print CSS เรียบที่สุด ฟอนต์เดียว ไม่มีรูป ทดสอบทั้ง 58/80 + ใบเสร็จ/ใบครัวพิมพ์ซ้ำได้เสมอ |
| เน็ตหน้าร้านหลุด | v1 ไม่ offline — แสดงสถานะชัด · ออเดอร์ร้านอาหารอยู่ใน DB แล้วไม่หาย · ตะกร้า retail อยู่ใน Alpine state จนกว่าจะ refresh |
| สต๊อกเพี้ยนจากการขายพร้อมกัน | decrement แบบ atomic (`update … set on_hand = on_hand - ?`) ใน transaction ของ Checkout |
| ขายเกินโควตาแพ็กเกจกลางคัน | เช็คตอน submit อีกรอบ (ไม่ใช่แค่ตอนเปิดจอ) แบบ `AccountingPlan::reasonCannotCreateInvoice` |

---

## 10. เช็คลิสต์กฎบ้าน (จาก `CLAUDE.md` — ทวนทุก PR)

- [ ] migration มี `down()` จริง + คอลัมน์ใหม่ใช้ `->after()`
- [ ] lang key ครบคู่ TH/EN เสมอ + ไทยใช้คำที่คนทั่วไปเข้าใจ
- [ ] ไม่มี `confirm()/alert()` — ใช้ `data-confirm` (SweetAlert2)
- [ ] ไม่มี dynamic Tailwind class — สีหมวด/สถานะโต๊ะใช้ map PHP
- [ ] ปุ่ม disabled อ่านออก (`disabled:opacity-60`)
- [ ] privileged action ลง `AuditLog::record` ครบ
- [ ] ไม่มี composer/npm dep ใหม่ (QR = CDN เดิม)
- [ ] ห้ามรัน `php artisan migrate` ใน sandbox — push แล้วให้ user กดที่ `/admin/system`
