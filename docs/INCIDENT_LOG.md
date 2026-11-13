# บันทึกข้อผิดพลาดและบทเรียนการพัฒนาระบบ (Incident & Post-Mortem Log)

เอกสารนี้ใช้บันทึกทุกข้อผิดพลาด, บั๊กสำคัญ, และบทเรียนการแก้ไขในระบบ **Trimongkol Service** เพื่อป้องกันไม่ให้เกิดปัญหาซ้ำซ้อน ตามคำสั่งของผู้ใช้งาน: *"ทุกครั้งที่มีข้อผิดพลาด มึงจดบันทึกไปด้วย"*

---

## 📋 กฎเหล็กป้องกันข้อผิดพลาดซ้ำ (Preventive Protocol)
1. **Schema Verification First:** ก่อนเขียน Query ดึงข้อมูลจากตารางใด ต้องเปิดดู Migration file หรือ Model `$fillable` เพื่อยืนยันชื่อคอลัมน์จริง ห้ามเดาหรือสมมุติชื่อฟิลด์เด็ดขาด
2. **Defensive Telemetry:** ทุกโมดูลที่เป็น Dashboard / Aggregation / Telemetry เสริม ต้องครอบ `try-catch` รายบล็อกเสมอ เพื่อไม่ให้โมดูลย่อยตัวใดตัวหนึ่งดึงทั้งหน้าเว็บล่ม (Fault Isolation)
3. **Pre-Deploy Execution Test:** ก่อน Deploy ขึ้น Production ต้องรัน Syntax Check (`php -l`) และเทสต์ความเข้ากันได้ของ Query ทุกครั้ง

---

## 🔴 รายการข้อผิดพลาดที่เกิดขึ้น (Incident History)

### Incident #001: HTTP 500 Column not found `cost_thb` on Main Dashboard
- **วันที่/เวลา:** 2026-11-11 08:22:02 (GMT+7)
- **ประเภท:** Runtime Exception / SQL Error (HTTP 500 Server Error)
- **ไฟล์ที่เกิดเหตุ:** `app/Http/Controllers/Portfolio/DashboardController.php` (Line 76)
- **ผลกระทบ:** ผู้ใช้ที่ล็อกอินเข้าใช้งานหน้าแดชบอร์ดหลัก (`/` หรือ `/portfolio`) เจอหน้าจอ 500 Server Error โหลดหน้าเว็บไม่ขึ้น

#### 1. สาเหตุ (Root Cause)
ในฟังก์ชัน `index()` ของ `DashboardController` ขณะเขียนโค้ดดึงสถิติค่าน้ำมันเดือนปัจจุบันเพื่อนำมาแสดงในวิดเจ็ต Master Overview ได้มีการเรียกใช้:
```php
FuelLog::query()->where('fuel_date', '>=', ...)->sum('cost_thb');
```
แต่โครงสร้างจริงในตาราง `portfolio_fuel_logs` (ตาม Migration `2026_11_10_000001_create_portfolio_fuel_logs_table.php`) ใช้ชื่อคอลัมน์:
- `refill_date` (ไม่ใช่วันที่ `fuel_date`)
- `total_price` (ไม่ใช่ค่าใช้จ่าย `cost_thb`)
ทำให้ MySQL แจ้งข้อผิดพลาด `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'cost_thb' in 'SELECT'`

#### 2. การแก้ไข (Resolution)
1. แก้ไขชื่อคอลัมน์ใน Query ให้ถูกต้องตรงกับ Schema จริง:
   ```php
   FuelLog::query()->forUser($userId)->where('refill_date', '>=', now()->startOfMonth()->toDateString())->sum('total_price');
   ```
2. ครอบเกราะป้องกัน `try-catch` แยกอิสระสำหรับทุกมิติของแดชบอร์ด (Life Goals, Second Brain, Couple Space, Fuel, Persona) พร้อมสั่ง Log Warning ลงใน Laravel Log
3. Deploy Hotfix ขึ้น Plesk Production Server และล้างแคชวิว (`storage/framework/views`) ทันที

#### 3. บทเรียนที่ได้รับ (Lessons Learned)
- ใน Blade template ต้องใช้ FQCN `\Carbon\Carbon` เสมอหากไม่มี Global View Alias

---

### Incident #003: HTTP 500 Undefined variable `$readinessScore` in `HealthDashboardController.php`
- **วันที่/เวลา:** 2026-11-11 12:55:08 (GMT+7)
- **ประเภท:** PHP ErrorException (HTTP 500 Server Error)
- **ไฟล์ที่เกิดเหตุ:** `app/Http/Controllers/Portfolio/HealthDashboardController.php` (Line 117)
- **ผลกระทบ:** ผู้ใช้เปิดหน้า `/portfolio/health` แล้วเจอหน้าจอ 500 Server Error หลังเพิ่มโค้ด Google Calendar & SOC Shift Radar

#### 1. สาเหตุ (Root Cause)
ในระหว่างการเพิ่มโค้ดโมดูลคำนวณเวร SOC บล็อกโค้ดที่ทำหน้าที่กำหนดค่าเริ่มต้นให้ตัวแปร `$readinessScore = 85;` ถูกตัดหายไป ทำให้เมื่อไม่มีตารางเวรวันนี้ (`$todayShift` เป็น null) ตัวแปร `$readinessScore` ไม่ได้ถูก initialize ก่อนถูกส่งเข้าไปประเมิน `max(50, min(99, $readinessScore))` ส่งผลให้ PHP พ่น `ErrorException: Undefined variable $readinessScore`.

#### 2. การแก้ไข (Resolution)
1. เพิ่มการ initialize `$readinessScore = 85;` และคำนวณเกณฑ์การนอนหลับ/การก้าวเดินพื้นฐานไว้ที่จุดเริ่มต้นของส่วนประเมินความพร้อม
2. ให้โมดูล SOC Shift Radar ทำการปรับคะแนนเพิ่มหรือลดบนตัวแปร `$readinessScore` ที่ถูก initialize ไว้อย่างถูกต้องแล้ว
3. ตรวจสอบ Syntax ด้วย `php -l` ผ่าน 100%
4. Deploy Hotfix ขึ้น Plesk Production Server และล้างแคชวิว (`storage/framework/views`)

#### 3. บทเรียนที่ได้รับ (Lessons Learned)
- ตัวแปรทุกตัวที่ต้องถูกคำนวณแบบสะสม (accumulative/modifier) ต้องถูกกำหนด Initial Value ชัดเจนเสมอก่อนเข้าบล็อก conditional logic

- แม้จะเป็นเพียงการดึงข้อมูลเพื่อสรุปตัวเลขสถิติบน Dashboard ก็ต้องตรวจสอบ Model Fillable / Migration ทุกครั้ง
- ระบบขนาดใหญ่ที่มีการผสานข้อมูลข้ามหลาย Subsystem ต้องมี Fault Tolerance แยกแต่ละส่วนเสมอ การล้มเหลวของวิดเจ็ตย่อยต้องไม่ทำให้ Core App ใช้การไม่ได้

---

### Incident #002: HTTP 500 Class "Carbon" not found in `portfolio/health.blade.php`
- **วันที่/เวลา:** 2026-11-11 10:34:48 (GMT+7)
- **ประเภท:** Blade Template Compilation / Runtime Fatal Error (HTTP 500 Server Error)
- **ไฟล์ที่เกิดเหตุ:** `resources/views/portfolio/health.blade.php` (Line 171, 303, 304)
- **ผลกระทบ:** ผู้ใช้เปิดหน้า `/portfolio/health` หรือ `/health` แล้วเจอหน้าจอ 500 Server Error ไม่สามารถดูแดชบอร์ดสุขภาพได้

#### 1. สาเหตุ (Root Cause)
ภายใน Blade View มีการเรียกใช้ Helper วันที่แบบย่อ `Carbon::parse($row->date)` โดยไม่มี Fully Qualified Class Name (FQCN) `\Carbon\Carbon` และไม่ได้มีการตั้ง Class Alias สำหรับ Carbon ใน Blade Compiler ทำให้ PHP Engine พยายามค้นหาคลาสในเนมสเปซย่อยแล้วเกิดข้อผิดพลาด `Class "Carbon" not found`.

#### 2. การแก้ไข (Resolution)
1. ปรับการเรียกใช้ทั้งหมดใน Blade ให้เป็น `\Carbon\Carbon::parse(...)` แบบ Fully Qualified Class Name (FQCN) เพื่อความแม่นยำและป้องกัน Name Resolution Error 100%
2. ปรับใช้ FQCN ในทุกจุดของ Blade Template
3. Purge cached views ใน `storage/framework/views` บน Plesk และ Verify การแสดงผลใหม่

#### 3. บทเรียนที่ได้รับ (Lessons Learned)
- ใน Blade template ต้องใช้ FQCN `\Carbon\Carbon` เสมอหากไม่มี Global View Alias

---

### Incident #003: HTTP 500 Undefined variable `$readinessScore` in `HealthDashboardController.php`
- **วันที่/เวลา:** 2026-11-11 12:55:08 (GMT+7)
- **ประเภท:** PHP ErrorException (HTTP 500 Server Error)
- **ไฟล์ที่เกิดเหตุ:** `app/Http/Controllers/Portfolio/HealthDashboardController.php` (Line 117)
- **ผลกระทบ:** ผู้ใช้เปิดหน้า `/portfolio/health` แล้วเจอหน้าจอ 500 Server Error หลังเพิ่มโค้ด Google Calendar & SOC Shift Radar

#### 1. สาเหตุ (Root Cause)
ในระหว่างการเพิ่มโค้ดโมดูลคำนวณเวร SOC บล็อกโค้ดที่ทำหน้าที่กำหนดค่าเริ่มต้นให้ตัวแปร `$readinessScore = 85;` ถูกตัดหายไป ทำให้เมื่อไม่มีตารางเวรวันนี้ (`$todayShift` เป็น null) ตัวแปร `$readinessScore` ไม่ได้ถูก initialize ก่อนถูกส่งเข้าไปประเมิน `max(50, min(99, $readinessScore))` ส่งผลให้ PHP พ่น `ErrorException: Undefined variable $readinessScore`.

#### 2. การแก้ไข (Resolution)
1. เพิ่มการ initialize `$readinessScore = 85;` และคำนวณเกณฑ์การนอนหลับ/การก้าวเดินพื้นฐานไว้ที่จุดเริ่มต้นของส่วนประเมินความพร้อม
2. ให้โมดูล SOC Shift Radar ทำการปรับคะแนนเพิ่มหรือลดบนตัวแปร `$readinessScore` ที่ถูก initialize ไว้อย่างถูกต้องแล้ว
3. ตรวจสอบ Syntax ด้วย `php -l` ผ่าน 100%
4. Deploy Hotfix ขึ้น Plesk Production Server และล้างแคชวิว (`storage/framework/views`)

#### 3. บทเรียนที่ได้รับ (Lessons Learned)
- ตัวแปรทุกตัวที่ต้องถูกคำนวณแบบสะสม (accumulative/modifier) ต้องถูกกำหนด Initial Value ชัดเจนเสมอก่อนเข้าบล็อก conditional logic

- ในไฟล์ Blade Template ต้องระวังการเรียก Static Class ที่ไม่มี Global Alias ให้ใช้ Fully Qualified Namespace `\Carbon\Carbon::...` เสมอ
- การทดสอบ Verify หน้าเว็บจริงผ่าน Browser Subagent ทันทีหลัง Deploy ช่วยตรวจจับ Runtime View Exception ได้รวดเร็วและแม่นยำที่สุด


---

### Incident #004: HTTP 500 Undefined array key "icon" in `health.blade.php`
- **วันที่/เวลา:** 2026-11-11 17:05:00 (GMT+7)
- **ประเภท:** PHP 8.4 ErrorException (HTTP 500 Server Error)
- **ไฟล์ที่เกิดเหตุ:** `resources/views/portfolio/health.blade.php` (Line 258, 270, 297, 309, 341)
- **ผลกระทบ:** ผู้ใช้เปิดหน้า `/portfolio/health` บนมือถือ/PWA แล้วเจอ HTTP 500 Server Error

#### 1. สาเหตุ (Root Cause)
หลังจากยิงสร้าง Event `WFH` วันที่ 27 พ.ย. 2026 ลง Google Calendar โดยตรง สคริปต์ Sync หลังบ้านทำการบันทึก JSON เข้า `storage/app/google_calendar_shifts.json` โดยมีโครงสร้างฟิลด์ไม่ครบถ้วน (ขาดคีย์ `icon`, `badge_color`, `label`, `start_formatted`, `end_formatted`, `day_formatted`) ส่งผลให้ Blade View ของหน้า Health Dashboard ใน PHP 8.4 ที่ไม่ได้ใส่ Null Coalescing Operator (`??`) พ่น `ErrorException: Undefined array key "icon"` และหยุดการทำงานทันที

#### 2. การแก้ไข (Resolution)
1. เพิ่ม Null Coalescing Operator (`?? '📌'`, `?? ''`, `?? 'bg-slate-500/20 text-slate-300'`) ให้ครอบคลุมทุกจุดในการดึง Array Key ของ `$todayShift`, `$tomorrowShift`, และ `$calendarSummary['upcoming']` ใน `resources/views/portfolio/health.blade.php` เพื่อการันตี Defensive Rendering 100%
2. ปรับปรุงสคริปต์ประมวลผลข้อมูลกะให้จัดฟอร์แมตข้อมูลครบทั้ง 12 ฟิลด์ตาม Schema ของ `GoogleCalendarController` และทำการอัปเดตไฟล์ `google_calendar_shifts.json` บน Plesk Production Server
3. ทำการล้างแคชวิวที่คอมไพล์แล้วใน `storage/framework/views`
4. ตรวจสอบการตอบสนองผ่าน HTTP GET บน Production Server ยืนยันผลลัพธ์ **HTTP 200 OK** สมบูรณ์

#### 3. บทเรียนที่ได้รับ (Lessons Learned)
- ใน PHP 8.4 การเข้าถึง Array Key ที่ไม่มีอยู่จริงจะถูกยกระดับเป็น Warning/Error ทันที การเข้าถึง Array จาก Dynamic Data หรือ External Cache ใน Blade ต้องใช้ Null Coalescing (`??`) เสมอ
- การสร้างหรือ Sync ข้อมูล Cache ในระดับ Storage JSON ต้องปฏิบัติตาม Data Contract และ Schema ที่ Controller และ View คาดหวังอย่างเคร่งครัด

---

### Incident #005: Debt Overview Payment Edits Reverting on Save (`TikTok PayLater` 3,867.24 Overwrite Bug)
- **วันที่/เวลา:** 2026-11-12 20:15:00 (GMT+7)
- **ประเภท:** Logic / Data Overwrite Bug
- **ไฟล์ที่เกิดเหตุ:**
  - `app/Http/Controllers/Portfolio/DebtOverviewController.php` (Lines 21–32)
  - `app/Http/Controllers/Portfolio/BudgetController.php` (Lines 36–41, 757–758, 1061)
- **ผลกระทบ:** เมื่อผู้ใช้แก้ไขยอดชำระรายงวดของ `TikTok PayLater (ครบกำหนดทุกวันที่ 1)` (เช่น เปลี่ยนจาก `3867.24` เป็นยอดอื่น) แล้วกดบันทึก ข้อมูลจะถูกเซฟลงฐานข้อมูลแล้ว Redirect กลับมาหน้า `/portfolio/debts` แต่ค่ากลับเด้งคืนเป็น `3867.24` เหมือนเดิมทุกครั้ง

#### 1. สาเหตุ (Root Cause)
1. ใน `DebtOverviewController::index()` มีเงื่อนไข `$needsSync` ตรวจสอบว่า `Debt::where('label', 'ใบแจ้งยอดล่วงหน้า (ครบกำหนดทุกวันที่ 1)')->doesntExist()` หรือ `Installment::whereNotIn('label', $verifiedInstallmentLabels)->exists()`
2. แต่ใน `BudgetController.php` มีการเปลี่ยนชื่อรายการเป็น `'TikTok PayLater (ครบกำหนดทุกวันที่ 1)'` และ `'สินเชื่อเงินสด TikTok'` ไปก่อนหน้าแล้ว ทำให้ `'ใบแจ้งยอดล่วงหน้า (ครบกำหนดทุกวันที่ 1)'` **ไม่มีอยู่จริงตลอดกาล**
3. ส่งผลให้ `$needsSync` เป็น `true` ทุกครั้งที่เปิดหน้า `/portfolio/debts` จึงไปเรียก `seedVerifiedNovember2026Plan()` เพื่อ `updateOrCreate` ค่าเริ่มต้น (`3867.24`) ทับข้อมูลที่ผู้ใช้เพิ่งบันทึกไปทันทีหลัง Redirect กลับมาหน้าเดิม
4. นอกจากนี้ `BudgetController::updateDebtPayment()` ยังเขียนทับ `$payment->notes = null` เมื่อฟอร์มแก้ไขส่งมาแค่ `amount` (ไม่ได้ส่ง `notes`)

#### 2. การแก้ไข (Resolution)
1. แก้ไขเงื่อนไข `$needsSync` ใน `DebtOverviewController::index()` ให้ทำงานเฉพาะเมื่อมีพารามิเตอร์ `?sync_plan=1` หรือเมื่อยังไม่มีข้อมูลหนี้และรายการผ่อนใดๆ ในระบบเลยเท่านั้น (ไม่รันทับข้อมูลที่ผู้ใช้แก้ไขแล้ว)
2. แก้ไขเงื่อนไข Auto-seed ใน `BudgetController::index()` ให้ทำงานเฉพาะเมื่อเดือน `2026-11` ไม่มีข้อมูลใดๆ ทั้งในตาราง Income, BudgetItem และ Installment
3. ปรับ `BudgetController::updateDebtPayment()` ให้อัปเดต `notes` เฉพาะเมื่อ Request ส่งคีย์ `notes` มาด้วย และให้ซิงก์ `total_amount` ของหนี้แม่ตามผลรวมงวดอัตโนมัติ
4. ปรับ `$activeInstallmentLabels` ใน `BudgetController::seedVerifiedNovember2026Plan()` ให้ตรงกับ `'สินเชื่อเงินสด TikTok'`
5. ตรวจสอบ Syntax ด้วย `php -l` และ Deploy ขึ้น Plesk Production Server

#### 3. บทเรียนที่ได้รับ (Lessons Learned)
- ห้ามวางเงื่อนไข Auto-seed จากชื่อ Label ที่อาจถูก Migrate/แก้ไขภายหลัง เพราะเมื่อ Label เปลี่ยน เงื่อนไข `doesntExist()` จะเป็น `true` ตลอดกาลและเขียนทับข้อมูลผู้ใช้ทุกครั้งที่โหลดหน้า
- การอัปเดต Partial Form ควรใช้ `$request->has('field')` ก่อนเขียนทับคอลัมน์ที่ไม่ได้ส่งมาจากฟอร์มนั้น

---

### Incident #006: Health Webhook Auth (`401`) & Array-to-Int Cast (`1`) Data Corruption
- **วันที่/เวลา:** 2026-11-12 20:45:00 (GMT+7)
- **ประเภท:** Integration Auth Mismatch & Payload Parsing / Type-Casting Bug
- **ไฟล์ที่เกิดเหตุ:**
  - `app/Http/Controllers/Portfolio/HealthWebhookController.php` (Lines 79–215 เดิม)
- **ผลกระทบ:**
  1. แอป **Health Connect to Webhook** (`v1.9.32`) บน S24 Ultra ส่งคำขอไปที่ Webhook แล้วเกิด HTTP `401` (`failed: 30` ใน 24 ชม.) จนกว่าจะแนบ Token ให้ตรง
  2. เมื่อส่งผ่านครั้งแรกในวันที่ `2026-11-12` ข้อมูลจริง (`steps: 3542`, `heart_rate`, `sleep`, `calories`) ถูกบันทึกเป็น `1` ทั้งหมด

#### 1. สาเหตุ (Root Cause)
1. แอปมือถือส่ง Telemetry แบบ Bucketed Array ต่อชนิดข้อมูล (เช่น `steps: [{"count": 3542, "start_time": ...}]`, `heart_rate: [...]`, `sleep: [...]`, `active_calories: [...]`, `oxygen_saturation: [...]`)
2. โค้ดเดิมใน `HealthWebhookController::handle()` ใช้ `(int) $steps` และ `(float) $activeCalories` โดยไม่ได้ตรวจ `is_array()` ทำให้ใน PHP การ Cast Non-empty Array เป็น `(int)`/`(float)` กลายเป็นค่า `1` ทุกฟิลด์

#### 2. การแก้ไข (Resolution)
1. เพิ่ม `isBucketedHealthConnectPayload()` และ `normalizeBucketedPayload()` เพื่อรวมยอดรายวันตามเขตเวลาของ Payload (รองรับ `steps`, `distance`, `active_calories`, `total_calories`, `heart_rate`, `resting_heart_rate`, `sleep` พร้อม Stage 4=Light / 5=Deep / 6=REM, และ `oxygen_saturation`) พร้อมกันไม่ให้ Rolling Tail ของวันก่อนหน้าทับข้อมูลเต็มวัน
2. เพิ่ม `toIntOrNull()` / `toFloatOrNull()` ที่ตรวจ `is_numeric()` ก่อน Cast และรองรับ Payload ทดสอบ `{"test": true}` โดยไม่สร้างแถวว่างทับ DB
3. ทดสอบ Syntax ด้วย `php -l`, ทดสอบ `normalizeBucketedPayload` ด้วยข้อมูลจริงจากพอร์ต `8787`, Deploy ขึ้น Plesk และยิง `sync_now` ผ่าน Health Connect MCP จน `https://trimongkol.com/api/health/test` ยืนยันข้อมูลวันนี้ (`steps: 3559`, `heart_rate_avg: 63`, `calories: 231.75`)

#### 3. บทเรียนที่ได้รับ (Lessons Learned)
- ห้าม Cast ค่า Input จาก External Webhook ด้วย `(int)` หรือ `(float)` ตรงๆ โดยไม่เช็ก `is_numeric()` เพราะ Array ใน PHP จะกลายเป็น `1` ทันทีโดยไม่ Throw Error

---

### Incident #007: Production HTTP 503 Service Unavailable (`php-fpm.sock` Down & ModSecurity >1000 JSON Keys)
- **วันที่/เวลา:** 2026-11-13 ~10:04–16:07 (หนักสุดช่วง 13:38–14:04 GMT+7)
- **ประเภท:** Server Availability / PHP-FPM Socket & ModSecurity WAF Limit
- **หลักฐานจาก Plesk Logs (`/logs/error_log` และ `/logs/access_ssl_log`):**
  - ระหว่าง `13:38:57` ถึง `14:04:07` (รวมถึงช่วง `10:04–11:45`, `13:05`, `15:45–16:07`) Apache `mod_proxy_fcgi` ฟ้องซ้ำๆ ว่า:
    `AH02454: FCGI: attempt to connect to Unix domain socket /var/www/vhosts/system/trimongkol.com/php-fpm.sock (127.0.0.1:9000) failed` และ `AH01079: failed to make connection to backend: 127.0.0.1`
  - หน้า Static (`/sw.js`, `/favicon.ico`, `/error_docs/*`) ยังตอบ `200 OK` ปกติ แต่ทุกคำขอที่ต้องผ่าน PHP (`GET /`, `GET /portfolio`, `GET /portfolio/brain`, `POST /api/health/webhook`) คืน `HTTP 503`
  - ก่อนและระหว่างช่วงเวลาดังกล่าว (`10:48`, `10:53`, `11:03`, `11:45`, `13:05`, `15:45`) Apache ModSecurity บันทึก error ด้วย:
    `[security2:error] ModSecurity: JSON parsing error: More than 1000 JSON keys [hostname "trimongkol.com"] [uri "/api/health/webhook"]` จากเครื่อง S24 Ultra (`okhttp/4.12.0`)

#### 1. สาเหตุ (Root Cause)
1. **PHP-FPM Socket ขาดช่วง/ดับ (`php-fpm.sock` หายไปชั่วคราว):** โปรเซส PHP-FPM (`plesk-php83-fpm`) ของโดเมน `trimongkol.com` ไม่ได้เปิดฟังอยู่ที่ socket `/var/www/vhosts/system/trimongkol.com/php-fpm.sock` ในช่วงเวลาดังกล่าว (เดิมตั้งไว้ `pm = ondemand`, `pm.start_servers = 1`, `memory_limit = 256M`) ทำให้ Apache คืน `503 Service Unavailable` ทุกหน้าที่วิ่งผ่าน PHP
2. **ModSecurity Warning บน Health Webhook (ไม่ทำให้ 503):** Payload ซิงค์สุขภาพจากมือถือมี key ใน JSON เกิน `1000` ทำให้ Apache ModSecurity เขียน `[security2:error] More than 1000 JSON keys` ลง `error_log` แต่จากการตรวจ log ย้อนหลัง (`2026-11-12 20:41` ถึง `2026-11-13 06:46`) และทดสอบยิง `POST /api/health/webhook` ด้วย `>1000` JSON keys จริง พบว่าคำขอผ่านถึง Laravel และตอบ `200 OK` ปกติเมื่อ PHP-FPM ทำงานอยู่

#### 2. การแก้ไข (Resolution)
1. **ปรับตั้งค่า Plesk PHP-FPM & Memory ผ่าน XML-RPC API (`/enterprise/control/agent.php`) สำเร็จทั้ง `trimongkol.com` (`id: 1533`) และ `dev.trimongkol.com` (`id: 1537`):**
   - `pm`: `ondemand` → `dynamic`
   - `memory_limit`: `256M` → `512M`
   - `pm.max_children`: `10`
   - `pm.start_servers`: `1` → `2`
   - `pm.min_spare_servers`: `1`
   - `pm.max_spare_servers`: `1` → `3`
   - `pm.max_requests`: `500`
   - `waf-rule-engine`: คงไว้ที่ `on` (หลังทดสอบ `POST /api/health/webhook` พร้อม 1,005 dummy keys แล้วตอบ `200 OK` `{"success":true,"message":"Health webhook connection verified"}`)
2. ฝั่งแอป: เพิ่ม JSON `401` สำหรับ AJAX ใน `EnsurePortfolioAccess` และ `LOCK_EX` ใน `JourneyController::saveJsonStorage()` เพื่อกันไฟล์ชนกันเพิ่มเติม พร้อมตรวจยืนยันหน้าเว็บหลักหลังปรับ Plesk ให้ตอบ `200 OK` / `302` ปกติ

#### 3. บทเรียนที่ได้รับ (Lessons Learned)
- เมื่อเว็บขึ้น `503` ในขณะที่ static asset (`/sw.js`, `/favicon.ico`) ยังโหลด `200` ให้เช็ก `/logs/error_log` ทันทีว่าเป็น `AH02454: php-fpm.sock` หรือ ModSecurity WAF จะแยกปัญหาฝั่ง Server ออกจาก Laravel App-level crash ได้ภายในไม่กี่วินาที
- การใช้ `pm = dynamic` พร้อม spare servers แทน `ondemand` ช่วยลดอาการ socket หายหรือค้างช่วง spin-up process ใหม่บน Plesk PHP-FPM ได้ดีกว่า
