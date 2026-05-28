# Project notes for Claude

อ่านก่อนเริ่มทุกครั้ง — เป็นกฎเฉพาะของ Tirmongkol Service ที่ Claude เคยพลาดมาก่อน

---

## UI / Tailwind

**กฎหลัก: ปุ่ม disabled ต้องอ่านได้เสมอ**

- ใช้ `disabled:opacity-60` แทนการเปลี่ยน background — pattern เดิมที่ใช้อยู่ทั่ว project
- ถ้าจำเป็นต้องเปลี่ยน `disabled:bg-X` → **ต้องใส่ `disabled:text-Y` ด้วย** ทุกครั้ง เพื่อรักษา contrast
- ห้ามใส่ `text-white` คู่กับ `disabled:bg-slate-300` (หรือสีอ่อนกว่า) — ตัวอักษรจะหาย
- ตรวจ contrast ก่อน commit: white text ใช้ได้บนพื้น `-600` ขึ้นไป, dark text ใช้ได้บนพื้น `-200` ลงมา

**ตัวอย่างที่ถูก:**
```html
<!-- pattern เดิมของ project — เลือกอันนี้เป็น default -->
<button class="bg-brand-600 text-white disabled:opacity-60">

<!-- ถ้าต้อง explicit ก็ต้องคู่กัน -->
<button class="bg-sky-600 text-white disabled:bg-slate-200 disabled:text-slate-500">
```

**Dynamic Tailwind classes:**
- Tailwind JIT อ่านเฉพาะ literal class strings ที่ปรากฏในซอร์ส
- ห้ามเขียน `bg-{{ $color }}-50` หรือ `text-{$tone}-700` — Tailwind จะไม่ build class นั้น
- ใช้ associative array map ของ class string เต็มแทน:
  ```php
  @php
      $badge = [
          'new' => 'bg-amber-50 text-amber-700 ring-amber-200',
          'won' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
      ];
  @endphp
  <span class="{{ $badge[$status] }}">...</span>
  ```

---

## Translations

- **TH + EN เป็นคู่กันเสมอ** — ทุก key ใน `lang/th/app.php` ต้องมีใน `lang/en/app.php` ด้วย
- ใช้คำไทยที่คนทั่วไปเข้าใจ — หลีกเลี่ยงศัพท์การตลาด/IT (เช่น "lead" → ใช้ "ข้อความติดต่อ" ใน UI ไทย, "Leads" ใน UI อังกฤษเก็บไว้ได้)
- เปลี่ยน lang → ต้องเคลียร์ view cache ก่อนจะ live (มีปุ่มใน `/admin/system`)

---

## Database / Migrations

- ทุก migration **ต้องมี `down()`** ที่กลับได้จริง
- เพิ่มคอลัมน์ใหม่ → ใช้ `->after('column_name')` ระบุตำแหน่งเสมอ
- **อย่ารัน `php artisan migrate` ตรง ๆ ใน sandbox นี้** — sandbox ไม่มี `vendor/` กับ `.env` ทดสอบ migration ไม่ได้ ให้เขียนแล้ว push เลย
- Production: user รัน migrate ผ่านปุ่มใน `/admin/system` (อย่าแนะนำ Plesk Scheduled Task / SSH ถ้าไม่จำเป็น)

---

## SweetAlert2 — ใช้แทน confirm()/alert()

- **ห้าม** ใช้ `onclick="return confirm(...)"` หรือ `window.confirm()` / `window.alert()`
- ใช้ pattern นี้แทน:
  ```html
  <form data-confirm="ลบสิ่งนี้?" data-confirm-danger="1" method="POST" ...>
      @csrf @method('DELETE')
      <button type="submit">ลบ</button>
  </form>
  ```
- `session('status')` / `session('error')` จะเด้งเป็น toast อัตโนมัติ — ไม่ต้องเขียน inline div ซ้ำ

---

## Admin architecture

โครงสร้าง: **Platform vs Products** — อ่าน `docs/ADMIN.md` ก่อนถ้าจะแก้ admin nav หรือเพิ่ม product

- Platform tools (Users, Audit, Leads, Security, CMS, System) อยู่ใต้ `admin.<name>`
- Product moderation อยู่ใต้ `admin.products.<product>.<feature>` — ลงทะเบียนใน `config/admin-products.php`
- ทุก privileged action ต้อง log ผ่าน `AuditLog::record(...)` — ดูที่ `/admin/logs` ได้

---

## Deploy flow (Plesk)

- ไม่ใช้ SSH (ปิด) ไม่ใช้ Scheduled Task (ลำบาก)
- ใช้ปุ่มใน `/admin/system`:
  1. Pull จาก Git
  2. Run migrations
  3. Clear caches
- Webhook URL เก็บใน site_settings (`deploy.webhook_url`)

---

## Don'ts

- ❌ อย่าเพิ่ม composer dependency โดยไม่ถามก่อน — user รัน `composer install` บน server ลำบาก
- ❌ อย่าเพิ่ม npm dependency โดยไม่ถามก่อน — ใช้ CDN ก่อน (เช่น SweetAlert2)
- ❌ อย่าสร้าง `.md` documentation ใหม่ทุกครั้งที่ทำฟีเจอร์ — แก้ที่ `docs/CHANGELOG.md` แทน
- ❌ อย่าใช้คำอังกฤษทับศัพท์ใน UI ไทย ถ้ามีคำไทยที่เข้าใจง่ายกว่า
