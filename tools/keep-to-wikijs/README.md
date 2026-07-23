# keep-to-wikijs

แปลงโน้ต **Google Keep** (จาก Google Takeout) → ไฟล์ **Markdown** ที่ **Wiki.js** import ได้เลย
พร้อม front-matter ครบ (title, tags, วันที่), จัดโฟลเดอร์ตาม label, แปลง checklist/ลิงก์/รูปแนบให้อัตโนมัติ

- Python 3.9+ · **ไม่ต้องลง dependency** (stdlib ล้วน)
- เป็น tool แยก ไม่เกี่ยวกับโค้ด Laravel ของ Tirmongkol

---

## ขั้นตอน

### 1) Export จาก Google Keep
ไปที่ [Google Takeout](https://takeout.google.com/) → เลือกเฉพาะ **Keep** → ดาวน์โหลด → แตก zip
จะได้โฟลเดอร์ `Takeout/Keep/` ที่มีไฟล์ `.json` (โน้ตละ 1 ไฟล์) + `.html` + รูปแนบ
> สคริปต์อ่านจาก `.json` เท่านั้น (เป็นข้อมูลสะอาด มีโครงสร้าง) — ไม่แตะ `.html`

### 2) แปลงเป็น Markdown
```bash
python3 keep_to_wikijs.py --input ./Takeout/Keep --output ./wiki-content
```
ลองก่อนแบบไม่เขียนไฟล์จริง:
```bash
python3 keep_to_wikijs.py -i ./Takeout/Keep -o ./wiki-content --dry-run
```

**ตัวเลือก:**

| flag | ค่า default | ความหมาย |
|---|---|---|
| `-i, --input` | *(บังคับ)* | โฟลเดอร์ `Takeout/Keep` |
| `-o, --output` | *(บังคับ)* | โฟลเดอร์ปลายทางไฟล์ `.md` |
| `--include-trashed` | ปิด | รวมโน้ตในถังขยะด้วย |
| `--skip-archived` | ปิด | ข้ามโน้ตที่ archive (default รวมไว้ + ติด tag `archived`) |
| `--no-copy-assets` | — | ไม่ต้องก๊อปรูปแนบ |
| `--no-status-tags` | — | ไม่ต้องเพิ่ม tag `pinned` / `archived` |
| `--color-tags` | ปิด | เพิ่ม tag ตามสีโน้ต (เช่น `color-red`) |
| `--dry-run` | ปิด | แสดงผลลัพธ์อย่างเดียว ไม่เขียนไฟล์ |

### 3) Import เข้า Wiki.js
เอาโฟลเดอร์ `wiki-content` ไปวางในที่ที่ Wiki.js sync แล้วสั่ง import:

- **Git storage** — commit + push ไฟล์ขึ้น repo ที่ผูกไว้ → Admin → Storage → **Git** → ปุ่ม **"Import Everything"**
- **Local FS storage** — วางไฟล์ในโฟลเดอร์ที่ตั้งค่าไว้ → Admin → Storage → **Local File System** → **"Import Everything"**

จดเพิ่มใน Keep ทีหลังก็ export + รันซ้ำ + import ใหม่ได้ (สคริปต์ deterministic ชื่อไฟล์เดิม)

---

## การ map ข้อมูล (Keep → Wiki.js)

| Google Keep | Wiki.js |
|---|---|
| `title` (ไม่มี → บรรทัดแรก/รายการแรก/ตามวันที่) | `title` + ชื่อไฟล์ (slug) |
| `labels[]` | `tags` **และ** โฟลเดอร์ (ใช้ label แรกเป็นโฟลเดอร์) |
| `textContent` | เนื้อหา |
| `listContent[]` (checklist) | `- [ ]` / `- [x]` |
| `annotations` (WEBLINK) | หัวข้อ **ลิงก์ที่แนบ** ท้ายหน้า |
| `attachments` (รูป) | ก๊อปวางข้างโน้ต + `![](...)` |
| `createdTimestampUsec` / `userEditedTimestampUsec` | `dateCreated` / `date` |
| `isPinned` / `isArchived` | tag `pinned` / `archived` |
| `color` (ถ้าเปิด `--color-tags`) | tag `color-<สี>` |

โน้ตไทย: slug คงตัวอักษร + สระ/วรรณยุกต์ไว้ครบ (ไม่แปลงเป็น ascii)

---

## ข้อจำกัดที่ควรรู้

- **รูปแนบ:** สคริปต์ก๊อปไฟล์วางข้างโน้ตแล้ว link แบบ relative — ตอน import ผ่าน storage ของ Wiki.js
  ควร **เช็คว่ารูปแสดงจริง** อีกที (Wiki.js จัดการ asset แยกจาก DB บางเวอร์ชันต้องอัปโหลดรูปเพิ่ม)
- **โฟลเดอร์ = label แรก** เท่านั้น (label ที่เหลืออยู่ใน tags ครบ) — เพราะ 1 หน้าใน Wiki.js มีได้ path เดียว
- **ชื่อซ้ำ** ในโฟลเดอร์เดียวกัน → ต่อท้าย `-2`, `-3` ให้อัตโนมัติ
