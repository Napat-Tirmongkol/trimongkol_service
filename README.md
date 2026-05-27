# Tirmongkol Service

เว็บไซต์สาธารณะของ Tirmongkol Service — ผู้ให้บริการพัฒนาระบบสำหรับธุรกิจ
(ระบบจองคิว, ระบบจัดการสินค้า, POS, CRM, เว็บไซต์, HR)

Built with Next.js 15 (App Router), TypeScript, and Tailwind CSS.
Bilingual (TH / EN) via React Context with `localStorage`-backed persistence.

Deployed on **Plesk Node.js** at `trimongkol.com/tirmongkol_service/`.

## รันบนเครื่อง (Local development)

```bash
npm install
npm run dev          # http://localhost:3000/tirmongkol_service
```

## โครงสร้างโปรเจกต์

```
app/
├── layout.tsx           # Root layout + metadata
├── page.tsx             # หน้า Home
├── services/page.tsx
├── about/page.tsx
├── contact/page.tsx
└── globals.css

components/
├── Navbar.tsx
├── Footer.tsx
├── LanguageProvider.tsx
└── LanguageToggle.tsx

lib/
├── site.ts              # ข้อมูลติดต่อ / domain
└── translations.ts      # คำแปล TH / EN

server.js                # Startup file สำหรับ Plesk Passenger
next.config.js           # basePath: '/tirmongkol_service'
```

## การแก้ไขเนื้อหา

- ข้อความทั้งหมดอยู่ใน `lib/translations.ts`
- ข้อมูลติดต่อ (อีเมล, เบอร์โทร, LINE) อยู่ใน `lib/site.ts`
- บริการ (services) เพิ่ม/แก้/ลบในรายการ `services.items` ใน `lib/translations.ts`

## Deploy บน Plesk (Node.js mode)

### Setup ครั้งแรก

1. **Git** — Plesk → Websites & Domains → trimongkol.com → Git
   - Repository URL: `https://github.com/Napat-Tirmongkol/trimongkol_service.git`
   - Branch: `main`
   - Deployment path: `/httpdocs/tirmongkol_service`

2. **Node.js** — Plesk → Node.js
   - Application Root: `/httpdocs/tirmongkol_service`
   - Application Startup File: `server.js`
   - Application Mode: `production`
   - กด **NPM Install**
   - กด **Run Script** → เลือก `build`
   - กด **Restart App**

3. **Reverse proxy** — เปิด `https://trimongkol.com/tirmongkol_service/`
   - ถ้า Plesk รัน Node ที่ root domain อยู่แล้ว Next.js จะตอบเฉพาะ path ที่ขึ้นต้นด้วย `/tirmongkol_service` (เพราะตั้ง `basePath`) และ 404 path อื่น — ตามต้องการ

### Deploy ครั้งถัดไป

แค่ `git push origin main` → Plesk auto-pull → ใน Node.js dashboard กด:
- **NPM Install** (เฉพาะถ้า `package.json` มีการเปลี่ยน)
- **Run Script** → `build`
- **Restart App**

หรือเปิด "Additional deployment actions" ใน Plesk Git ใส่:
```
npm install
npm run build
touch tmp/restart.txt
```
แล้ว Plesk จะรันให้อัตโนมัติทุกครั้งหลัง pull
