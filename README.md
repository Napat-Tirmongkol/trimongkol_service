# Tirmongkol Service

เว็บไซต์สาธารณะของ Tirmongkol Service — ผู้ให้บริการพัฒนาระบบสำหรับธุรกิจ
(ระบบจองคิว, ระบบจัดการสินค้า, POS, CRM, เว็บไซต์, HR)

Built with Next.js 15 (App Router), TypeScript, and Tailwind CSS.
Bilingual (TH / EN) via React Context with `localStorage`-backed persistence.

## เริ่มต้นใช้งาน (Getting started)

```bash
# 1. ติดตั้ง dependencies
npm install

# 2. รันโหมด development
npm run dev

# 3. build สำหรับ production
npm run build && npm start
```

เปิดเว็บที่ http://localhost:3000

## โครงสร้างโปรเจกต์

```
app/
├── layout.tsx           # Root layout + metadata
├── page.tsx             # หน้า Home
├── services/page.tsx    # หน้า Services
├── about/page.tsx       # หน้า About
├── contact/page.tsx     # หน้า Contact (mailto form)
└── globals.css

components/
├── Navbar.tsx
├── Footer.tsx
├── LanguageProvider.tsx # Context + localStorage
└── LanguageToggle.tsx   # ปุ่ม TH / EN

lib/
├── site.ts              # ข้อมูลติดต่อ / domain
└── translations.ts      # คำแปลทั้งหมด (TH / EN)
```

## การแก้ไขเนื้อหา

- ข้อความทั้งหมดอยู่ใน `lib/translations.ts` (มีทั้งภาษาไทยและอังกฤษ)
- ข้อมูลติดต่อ (อีเมล, เบอร์โทร, LINE) อยู่ใน `lib/site.ts`
- บริการ (services) เพิ่ม/แก้/ลบในรายการ `services.items` ของ `lib/translations.ts`

## Deploy ขึ้น Production

แนะนำให้ deploy บน Vercel:

1. push repo นี้ขึ้น GitHub
2. import โปรเจกต์ที่ https://vercel.com/new
3. ที่หน้า Domains ใน Vercel ใส่ `tirmongkol.com`
4. ที่ผู้ให้บริการ domain ตั้ง DNS:
   - A record `@` → `76.76.21.21`
   - CNAME record `www` → `cname.vercel-dns.com`

หรือ deploy แบบ static export ก็ได้ — เพิ่ม `output: "export"` ใน `next.config.js`
แล้ว `npm run build` จะได้โฟลเดอร์ `out/` เอาไปวางบน hosting ใดก็ได้
