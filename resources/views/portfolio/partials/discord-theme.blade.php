{{-- ─────────────────────────────────────────────────────────────────────
     Discord-style skin สำหรับ /portfolio (opt-in ตามที่ผู้ใช้สั่ง)
     อ้างอิงโทเค็นจาก docs/design/discord-analysis.md
     ทำเป็น CSS layer เพราะ deploy ไม่ rebuild Tailwind (compiled CSS แช่แข็ง)
     → ถอนสไตล์นี้ = ลบ @include ตัวนี้ + class "portfolio-discord" จาก <body>
     ซ้อนบน .portfolio-dark (baseline) ด้วย specificity body.portfolio-discord ที่สูงกว่า
   ───────────────────────────────────────────────────────────────────── --}}
<link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800&display=swap" rel="stylesheet">
<style>
    /* ===== Discord tokens =====
       blurple #5865f2 · green #35ed7e · magenta #ec48bd · link #00b0f4
       canvas #0a0d3a · surface #1e2353 · hairline #2b317a · ink #fff */

    /* Canvas + gradient mesh (Blurple→magenta) — ลายเซ็นบรรยากาศของแบรนด์ */
    body.portfolio-discord {
        background-color: #0a0d3a !important;
        background-image:
            radial-gradient(55rem 55rem at 12% -8%, rgba(88,101,242,.38), transparent 60%),
            radial-gradient(45rem 45rem at 100% 2%, rgba(236,72,189,.22), transparent 55%),
            radial-gradient(60rem 60rem at 50% 122%, rgba(88,101,242,.18), transparent 60%) !important;
        background-attachment: fixed !important;
        color: #ffffff !important;
        font-family: 'Inter','Noto Sans Thai',system-ui,sans-serif;
    }

    /* Display type — heavy grotesque (แทน ABC Ginto Nord proprietary) */
    body.portfolio-discord h1, body.portfolio-discord h2,
    body.portfolio-discord h3, body.portfolio-discord h4,
    body.portfolio-discord .text-xl,  body.portfolio-discord .text-2xl,
    body.portfolio-discord .text-3xl, body.portfolio-discord .text-4xl,
    body.portfolio-discord .text-5xl {
        font-family: 'Hanken Grotesk','Noto Sans Thai',sans-serif !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em;
    }

    /* Surfaces / cards */
    body.portfolio-discord .bg-white { background-color:#1e2353 !important; }
    body.portfolio-discord .bg-slate-50,  body.portfolio-discord .bg-slate-100,
    body.portfolio-discord .bg-slate-50\/50, body.portfolio-discord .bg-slate-50\/60 { background-color:#161b47 !important; }
    body.portfolio-discord .bg-slate-200 { background-color:#141a4d !important; }

    /* Borders / hairlines */
    body.portfolio-discord .border-slate-100, body.portfolio-discord .border-slate-200,
    body.portfolio-discord .border-slate-300, body.portfolio-discord .border-slate-800,
    body.portfolio-discord .divide-slate-100 > :not([hidden]) ~ :not([hidden]),
    body.portfolio-discord .divide-slate-200 > :not([hidden]) ~ :not([hidden]) { border-color:#2b317a !important; }

    /* Text — อ่านออกบน indigo ไม่ว่าจะอยู่โหมด dark หรือ light (Discord เลี้ยงตัวเองได้) */
    body.portfolio-discord .text-slate-900, body.portfolio-discord .text-slate-800 { color:#ffffff !important; }
    body.portfolio-discord .text-slate-700 { color:#cdd2f5 !important; }
    body.portfolio-discord .text-slate-600, body.portfolio-discord .text-slate-500 { color:#9aa2d8 !important; }
    body.portfolio-discord .text-slate-400 { color:#6b73ad !important; }

    /* Status tints — พื้นอ่อน → โทนเข้มอ่านง่ายบน indigo (positive = เขียว Discord) */
    body.portfolio-discord .bg-emerald-50, body.portfolio-discord .bg-emerald-50\/50, body.portfolio-discord .bg-emerald-50\/60 { background-color:rgba(53,237,126,.12) !important; }
    body.portfolio-discord .text-emerald-600, body.portfolio-discord .text-emerald-700, body.portfolio-discord .text-emerald-800, body.portfolio-discord .text-emerald-900 { color:#35ed7e !important; }
    body.portfolio-discord .bg-rose-50, body.portfolio-discord .bg-rose-50\/50, body.portfolio-discord .bg-rose-50\/60 { background-color:rgba(244,63,94,.14) !important; }
    body.portfolio-discord .text-rose-400, body.portfolio-discord .text-rose-600, body.portfolio-discord .text-rose-700, body.portfolio-discord .text-rose-800, body.portfolio-discord .text-rose-900 { color:#ff7088 !important; }
    body.portfolio-discord .bg-amber-50 { background-color:rgba(245,158,11,.14) !important; }
    body.portfolio-discord .text-amber-700, body.portfolio-discord .text-amber-800 { color:#fbbf24 !important; }

    /* Generous Discord radii */
    body.portfolio-discord .rounded-lg  { border-radius:14px !important; }
    body.portfolio-discord .rounded-xl  { border-radius:18px !important; }
    body.portfolio-discord .rounded-2xl { border-radius:24px !important; }
    body.portfolio-discord .rounded-3xl { border-radius:32px !important; }

    /* Depth = soft violet glow (ไม่พึ่ง hard shadow ตามแนวแบรนด์) */
    body.portfolio-discord .shadow-sm, body.portfolio-discord .shadow,
    body.portfolio-discord .shadow-md { box-shadow:0 3px 40px rgba(69,42,124,.45) !important; }

    /* Brand → Blurple */
    body.portfolio-discord .bg-brand-600, body.portfolio-discord .bg-brand-500,
    body.portfolio-discord .bg-brand-400 { background-color:#5865f2 !important; }
    body.portfolio-discord .hover\:bg-brand-700:hover { background-color:#4752c4 !important; }
    body.portfolio-discord .text-brand-600, body.portfolio-discord .text-brand-700,
    body.portfolio-discord .text-brand-800 { color:#a3aaff !important; }
    body.portfolio-discord .border-brand-600 { border-color:#5865f2 !important; }
    body.portfolio-discord .bg-brand-100 { background-color:rgba(88,101,242,.18) !important; color:#c2c7ff !important; }
    body.portfolio-discord .bg-brand-50, body.portfolio-discord .bg-brand-50\/50,
    body.portfolio-discord .bg-brand-50\/30 { background-color:rgba(88,101,242,.12) !important; }
    body.portfolio-discord .border-brand-200, body.portfolio-discord .border-brand-100 { border-color:rgba(88,101,242,.3) !important; }

    /* Primary dark buttons → Blurple CTA */
    body.portfolio-discord .bg-slate-900,
    body.portfolio-discord .bg-slate-900.text-white { background-color:#5865f2 !important; color:#fff !important; }
    body.portfolio-discord .hover\:bg-slate-800:hover { background-color:#4752c4 !important; }

    /* Secondary / pill chips */
    body.portfolio-discord .bg-slate-800 { background-color:#23286b !important; }
    body.portfolio-discord .bg-slate-100 { background-color:#23286b !important; color:#fff !important; }

    /* Inputs */
    body.portfolio-discord input[type="text"],  body.portfolio-discord input[type="email"],
    body.portfolio-discord input[type="number"], body.portfolio-discord input[type="password"],
    body.portfolio-discord input[type="search"], body.portfolio-discord input[type="date"],
    body.portfolio-discord select, body.portfolio-discord textarea {
        background-color:#141a4d !important; border-color:#2b317a !important; color:#fff !important;
    }
    body.portfolio-discord input:focus, body.portfolio-discord select:focus, body.portfolio-discord textarea:focus {
        border-color:#5865f2 !important; box-shadow:0 0 0 3px rgba(88,101,242,.35) !important;
    }
    body.portfolio-discord input::placeholder { color:#7c83c0 !important; }

    /* Chrome — header + แถบเมนูล่าง: indigo โปร่งเบลอ */
    body.portfolio-discord header { background-color:rgba(10,13,58,.72) !important; border-color:#2b317a !important; backdrop-filter:blur(12px); }
    body.portfolio-discord nav.bottom-0 { background-color:rgba(14,17,64,.85) !important; border-color:#2b317a !important; backdrop-filter:blur(12px); }

    /* ซ่อนปุ่มสลับ dark/light — Discord เป็นธีมมืดโดยกำเนิด */
    body.portfolio-discord .discord-hide { display:none !important; }

    /* Scrollbar */
    body.portfolio-discord ::-webkit-scrollbar-thumb { background:#2b317a !important; }
    body.portfolio-discord ::-webkit-scrollbar-thumb:hover { background:#5865f2 !important; }
</style>
