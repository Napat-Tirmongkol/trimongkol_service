<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- PWA & Mobile Web App Meta -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0a0c10">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Folk Life">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <title>{{ __('app.portfolio.heading') }} — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        /* ── Tailwind utility fallbacks (compiled CSS predates budget templates) ── */
        .h-1\.5{height:.375rem}
        .h-3\.5{height:.875rem}
        .w-3\.5{width:.875rem}
        .gap-1\.5{gap:.375rem}
        .p-0\.5{padding:.125rem}
        .p-1\.5{padding:.375rem}
        .p-2\.5{padding:.625rem}
        .py-1\.5{padding-top:.375rem;padding-bottom:.375rem}
        .py-2\.5{padding-top:.625rem;padding-bottom:.625rem}
        .mb-0\.5{margin-bottom:.125rem}
        .mt-0\.5{margin-top:.125rem}
        .text-\[10px\]{font-size:10px;line-height:1.4}
        .text-\[11px\]{font-size:11px;line-height:1.4}
        .bg-purple-50{background-color:#faf5ff}
        .bg-purple-100{background-color:#f3e8ff}
        .border-purple-200{border-color:#e9d5ff}
        .border-purple-600{border-color:#9333ea}
        .bg-purple-600{background-color:#9333ea}
        .text-purple-600{color:#9333ea}
        .text-purple-700{color:#7e22ce}
        .text-purple-800{color:#6b21a8}
        .hover\:bg-purple-100:hover{background-color:#f3e8ff}

        /* ── Portfolio Dark Mode (mirrors admin theme) ────────────── */

        /* Global Backgrounds */
        html.portfolio-dark-pre,
        body.portfolio-dark {
            background-color: #0a0c10 !important;
            color: #f1f5f9 !important;
        }

        /* Cards & Surfaces */
        .portfolio-dark .bg-white {
            background-color: #141720 !important;
            border-color: #1e2231 !important;
        }

        .portfolio-dark .bg-slate-50,
        .portfolio-dark .bg-slate-50\/50 {
            background-color: #0d0f14 !important;
        }

        .portfolio-dark .bg-slate-100,
        .portfolio-dark .bg-slate-200 {
            background-color: #181c28 !important;
        }

        /* Badge colors & light container backgrounds */
        .portfolio-dark .bg-emerald-50,
        .portfolio-dark .bg-emerald-50\/60 {
            background-color: rgba(16, 185, 129, 0.1) !important;
            color: #34d399 !important;
            border-color: rgba(16, 185, 129, 0.2) !important;
        }
        .portfolio-dark .bg-rose-50,
        .portfolio-dark .bg-rose-50\/60 {
            background-color: rgba(244, 63, 94, 0.1) !important;
            color: #fb7185 !important;
            border-color: rgba(244, 63, 94, 0.2) !important;
        }
        .portfolio-dark .bg-sky-50,
        .portfolio-dark .bg-sky-50\/60 {
            background-color: rgba(14, 165, 233, 0.1) !important;
            color: #38bdf8 !important;
            border-color: rgba(14, 165, 233, 0.2) !important;
        }
        .portfolio-dark .bg-amber-50 {
            background-color: rgba(245, 158, 11, 0.1) !important;
            color: #fbbf24 !important;
            border-color: rgba(245, 158, 11, 0.2) !important;
        }
        .portfolio-dark .bg-violet-50 {
            background-color: rgba(139, 92, 246, 0.1) !important;
            color: #a78bfa !important;
            border-color: rgba(139, 92, 246, 0.2) !important;
        }
        .portfolio-dark .bg-purple-50,
        .portfolio-dark .bg-purple-50\/60,
        .portfolio-dark .bg-purple-100 {
            background-color: rgba(168, 85, 247, 0.12) !important;
            color: #c084fc !important;
            border-color: rgba(168, 85, 247, 0.25) !important;
        }
        .portfolio-dark .text-purple-600,
        .portfolio-dark .text-purple-700,
        .portfolio-dark .text-purple-800 {
            color: #c084fc !important;
        }
        .portfolio-dark .border-purple-200,
        .portfolio-dark .border-purple-300 {
            border-color: rgba(168, 85, 247, 0.3) !important;
        }
        .portfolio-dark .hover\:bg-purple-100:hover,
        .portfolio-dark .hover\:bg-purple-50:hover {
            background-color: rgba(168, 85, 247, 0.22) !important;
            color: #e9d5ff !important;
        }
        .portfolio-dark .bg-purple-600 {
            background-color: #9333ea !important;
            color: #ffffff !important;
        }
        .portfolio-dark .border-purple-600 {
            border-color: #a855f7 !important;
        }
        .portfolio-dark .hover\:bg-emerald-100:hover {
            background-color: rgba(16, 185, 129, 0.2) !important;
            color: #6ee7b7 !important;
        }
        .portfolio-dark .hover\:bg-sky-100:hover {
            background-color: rgba(14, 165, 233, 0.2) !important;
            color: #7dd3fc !important;
        }
        .portfolio-dark .text-sky-700,
        .portfolio-dark .text-sky-800 {
            color: #38bdf8 !important;
        }
        .portfolio-dark .border-sky-200 {
            border-color: rgba(14, 165, 233, 0.25) !important;
        }
        .portfolio-dark .dca-planner-card {
            background-color: #141720 !important;
            background-image: none !important;
            border-color: rgba(16, 185, 129, 0.25) !important;
        }
        .portfolio-dark .dca-projection-card {
            background-color: #0d1017 !important;
            border-color: #1e2433 !important;
        }
        .portfolio-dark .bg-indigo-50 {
            background-color: rgba(99, 102, 241, 0.1) !important;
            color: #818cf8 !important;
            border-color: rgba(99, 102, 241, 0.2) !important;
        }
        .portfolio-dark .bg-stone-100 {
            background-color: rgba(120, 113, 108, 0.1) !important;
            color: #d6d3d1 !important;
            border-color: rgba(120, 113, 108, 0.2) !important;
        }
        
        /* Table headers, hover states */
        .portfolio-dark .bg-slate-50\/60 {
            background-color: #0d0f14 !important;
        }
        .portfolio-dark .hover\:bg-brand-50:hover {
            background-color: rgba(79, 70, 229, 0.15) !important;
            color: #818cf8 !important;
        }
        .portfolio-dark .hover\:bg-rose-50:hover {
            background-color: rgba(244, 63, 94, 0.15) !important;
        }

        /* Borders & Dividers */
        .portfolio-dark .border,
        .portfolio-dark .border-t,
        .portfolio-dark .border-b,
        .portfolio-dark .border-l,
        .portfolio-dark .border-r,
        .portfolio-dark .border-slate-100,
        .portfolio-dark .border-slate-200,
        .portfolio-dark .border-slate-300,
        .portfolio-dark .divide-y > :not([hidden]) ~ :not([hidden]),
        .portfolio-dark .divide-slate-100 > :not([hidden]) ~ :not([hidden]),
        .portfolio-dark .divide-slate-200 > :not([hidden]) ~ :not([hidden]) {
            border-color: #1e2231 !important;
        }

        /* Shadows — deeper glow for dark surfaces */
        .portfolio-dark .shadow-sm,
        .portfolio-dark .shadow,
        .portfolio-dark .shadow-md {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4), 0 0 1px rgba(0, 0, 0, 0.5) !important;
        }

        /* Rounded cards — subtle border glow */
        .portfolio-dark .rounded-2xl.border {
            border-color: #242a3a !important;
        }

        /* Text Colors */
        .portfolio-dark .text-slate-900,
        .portfolio-dark .text-slate-800 {
            color: #f1f5f9 !important;
        }

        .portfolio-dark .text-slate-700 {
            color: #cbd5e1 !important;
        }

        .portfolio-dark .text-slate-600,
        .portfolio-dark .text-slate-500 {
            color: #8896ab !important;
        }

        .portfolio-dark .text-slate-400 {
            color: #566175 !important;
        }

        /* Input Controls */
        .portfolio-dark input[type="text"],
        .portfolio-dark input[type="email"],
        .portfolio-dark input[type="number"],
        .portfolio-dark input[type="password"],
        .portfolio-dark input[type="search"],
        .portfolio-dark input[type="date"],
        .portfolio-dark select,
        .portfolio-dark textarea {
            background-color: #0d0f14 !important;
            border-color: #2a3042 !important;
            color: #f1f5f9 !important;
        }

        .portfolio-dark input::placeholder {
            color: #566175 !important;
        }

        .portfolio-dark input:focus,
        .portfolio-dark select:focus,
        .portfolio-dark textarea:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25) !important;
        }

        /* Select arrow for dark bg */
        .portfolio-dark select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7f99' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
        }

        /* Checkbox */
        .portfolio-dark input[type="checkbox"] {
            background-color: #0d0f14 !important;
            border-color: #2a3042 !important;
        }

        /* Status Cards — Green */
        .portfolio-dark .bg-emerald-50\/50,
        .portfolio-dark .bg-emerald-50\/60,
        .portfolio-dark .bg-emerald-50 {
            background-color: rgba(6, 95, 70, 0.15) !important;
        }
        .portfolio-dark .border-emerald-200 {
            border-color: rgba(16, 185, 129, 0.25) !important;
        }
        .portfolio-dark .text-emerald-700,
        .portfolio-dark .text-emerald-800,
        .portfolio-dark .text-emerald-900 {
            color: #34d399 !important;
        }
        .portfolio-dark .text-emerald-600 {
            color: #34d399 !important;
        }
        .portfolio-dark .bg-emerald-500 {
            background-color: #10b981 !important;
        }

        /* Status Cards — Rose / Red */
        .portfolio-dark .bg-rose-50\/50,
        .portfolio-dark .bg-rose-50\/30,
        .portfolio-dark .bg-rose-50 {
            background-color: rgba(159, 18, 57, 0.15) !important;
        }
        .portfolio-dark .border-rose-200 {
            border-color: rgba(244, 63, 94, 0.25) !important;
        }
        .portfolio-dark .text-rose-700,
        .portfolio-dark .text-rose-800,
        .portfolio-dark .text-rose-900 {
            color: #fb7185 !important;
        }
        .portfolio-dark .text-rose-600 {
            color: #fb7185 !important;
        }
        .portfolio-dark .text-rose-400 {
            color: #fb7185 !important;
        }

        /* Amber / Warning */
        .portfolio-dark .bg-amber-50 {
            background-color: rgba(120, 80, 0, 0.15) !important;
        }
        .portfolio-dark .border-amber-200 {
            border-color: rgba(245, 158, 11, 0.25) !important;
        }
        .portfolio-dark .text-amber-700,
        .portfolio-dark .text-amber-800 {
            color: #fbbf24 !important;
        }

        /* Brand Accent */
        .portfolio-dark .bg-brand-100 {
            background-color: rgba(14, 165, 233, 0.15) !important;
        }
        .portfolio-dark .bg-brand-50\/50,
        .portfolio-dark .bg-brand-50\/30,
        .portfolio-dark .bg-brand-50 {
            background-color: rgba(14, 165, 233, 0.08) !important;
        }
        .portfolio-dark .border-brand-200 {
            border-color: rgba(14, 165, 233, 0.2) !important;
        }
        .portfolio-dark .border-brand-100 {
            border-color: rgba(14, 165, 233, 0.12) !important;
        }
        .portfolio-dark .text-brand-800 {
            color: #38bdf8 !important;
        }
        .portfolio-dark .text-brand-700 {
            color: #38bdf8 !important;
        }
        .portfolio-dark .text-brand-600 {
            color: #38bdf8 !important;
        }
        .portfolio-dark .border-brand-600 {
            border-color: #38bdf8 !important;
        }
        .portfolio-dark .bg-brand-600 {
            background-color: #0ea5e9 !important;
        }
        .portfolio-dark .bg-brand-500,
        .portfolio-dark .bg-brand-400 {
            background-color: #0ea5e9 !important;
        }
        .portfolio-dark .bg-brand-600:hover,
        .portfolio-dark .hover\:bg-brand-700:hover {
            background-color: #0284c7 !important;
        }

        /* Progress Bar bg */
        .portfolio-dark .bg-slate-200 {
            background-color: #1e2231 !important;
        }

        /* ── Buttons ───────────────────────────────────── */

        /* Primary dark button (bg-slate-900 text-white) → keep as-is, just lighten slightly */
        .portfolio-dark .bg-slate-900.text-white {
            background-color: #e2e8f0 !important;
            color: #0b0c0e !important;
        }
        .portfolio-dark .bg-slate-900.text-white:hover,
        .portfolio-dark .hover\:bg-slate-800:hover {
            background-color: #f8fafc !important;
            color: #0b0c0e !important;
        }

        /* Cancel/secondary buttons (bg-slate-200 text-slate-700) */
        .portfolio-dark .bg-slate-200.text-slate-700 {
            background-color: #1e2231 !important;
            color: #cbd5e1 !important;
        }

        /* Icon add buttons (+) */
        .portfolio-dark button.rounded-lg.border.border-slate-200 {
            border-color: #2a3042 !important;
            color: #8896ab !important;
        }
        .portfolio-dark button.rounded-lg.border.border-slate-200:hover {
            background-color: #1e2231 !important;
            color: #f1f5f9 !important;
        }

        /* Inline form bg */
        .portfolio-dark .rounded-xl.bg-slate-50,
        .portfolio-dark .bg-slate-50.p-2\.5 {
            background-color: #0d0f14 !important;
            border-color: #1e2231 !important;
        }

        /* Hover states */
        .portfolio-dark .hover\:bg-slate-50:hover {
            background-color: #181c28 !important;
        }
        .portfolio-dark .hover\:bg-slate-100:hover {
            background-color: #1e2231 !important;
        }
        .portfolio-dark .hover\:bg-slate-200:hover {
            background-color: #242a3a !important;
        }

        /* Nav Sub-tabs */
        .portfolio-dark .hover\:border-slate-600:hover {
            border-color: #475569 !important;
        }
        .portfolio-dark .hover\:text-slate-300:hover {
            color: #cbd5e1 !important;
        }

        /* Logout button */
        .portfolio-dark .rounded-full.bg-slate-100 {
            background-color: #1e2231 !important;
            color: #cbd5e1 !important;
        }
        .portfolio-dark .rounded-full.bg-slate-100:hover {
            background-color: #242a3a !important;
        }

        /* Ring on avatar */
        .portfolio-dark .ring-slate-700 {
            --tw-ring-color: #2a3042 !important;
        }

        /* Auto-calc badge */
        .portfolio-dark .bg-slate-100.rounded.border.border-slate-200 {
            background-color: #1e2231 !important;
            border-color: #2a3042 !important;
            color: #8896ab !important;
        }

        /* line-through items */
        .portfolio-dark .line-through {
            color: #3d4657 !important;
        }

        /* Scrollbar */
        .portfolio-dark ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .portfolio-dark ::-webkit-scrollbar-track {
            background: #0a0c10;
        }
        .portfolio-dark ::-webkit-scrollbar-thumb {
            background: #1e2231;
            border-radius: 4px;
        }
        .portfolio-dark ::-webkit-scrollbar-thumb:hover {
            background: #2a3042;
        }

        /* ── Select element: remove native browser arrow to prevent Chromium
              dark-mode double-arrow rendering bug ──────────────────────────── */
        select:not([multiple]):not([size]) {
            -webkit-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1.1rem;
            padding-right: 2.25rem;
        }
        .portfolio-dark select:not([multiple]):not([size]) {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E") !important;
        }
        [x-cloak] { display: none !important; }

        /* ── Top Bar Dropdown & Mobile Drawer (Solid Opaque Surfaces) ── */
        .portfolio-dropdown-menu {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        }
        .portfolio-dark .portfolio-dropdown-menu {
            background-color: #141720 !important;
            border: 1px solid #2a3142 !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.95), 0 0 0 1px rgba(255, 255, 255, 0.08) !important;
        }
        .portfolio-dropdown-item {
            color: #334155 !important;
        }
        .portfolio-dropdown-item:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }
        .portfolio-dark .portfolio-dropdown-item {
            color: #cbd5e1 !important;
        }
        .portfolio-dark .portfolio-dropdown-item:hover {
            background-color: #1f2536 !important;
            color: #ffffff !important;
        }
        .portfolio-mobile-drawer-panel {
            background-color: #ffffff !important;
            color: #0f172a !important;
        }
        .portfolio-dark .portfolio-mobile-drawer-panel {
            background-color: #121418 !important;
            color: #f1f5f9 !important;
        }
    </style>

    @include('portfolio.partials.discord-theme')

    <script>
        // Restore theme before paint to prevent flash
        (function(){
            var d = localStorage.getItem('portfolio_dark');
            if (d === null) d = '1'; // default dark
            if (d === '1') document.documentElement.classList.add('portfolio-dark-pre');
        })();
    </script>
</head>
<body class="portfolio-discord" x-data="{
        dark: localStorage.getItem('portfolio_dark') !== '0',
        toggle() {
            this.dark = !this.dark;
            localStorage.setItem('portfolio_dark', this.dark ? '1' : '0');
        },
        mobileDrawer: null,
        quickActionOpen: false,
        deferredPrompt: null,
        isPwaInstalled: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
        openDrawer(key) {
            this.mobileDrawer = (this.mobileDrawer === key) ? null : key;
        },
        closeDrawer() {
            this.mobileDrawer = null;
        },
        installPwa() {
            if (this.deferredPrompt) {
                this.deferredPrompt.prompt();
                this.deferredPrompt.userChoice.then((choice) => {
                    if (choice.outcome === 'accepted') {
                        this.deferredPrompt = null;
                        this.isPwaInstalled = true;
                    }
                });
            } else {
                alert('📲 สำหรับ iPhone (Safari): แตะปุ่มแชร์ (Share) ที่แถบล่างเบราว์เซอร์ แล้วเลือก \'เพิ่มไปยังหน้าจอโฮม (Add to Home Screen)\' ได้เลยเพื่อน!\n\nสำหรับ Android: แตะเมนู 3 จุดมุมขวาบน แล้วเลือก \'ติดตั้งแอป\' หรือ \'เพิ่มลงหน้าจอหลัก\'');
            }
        }
     }"
     x-init="
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
        });
        $watch('dark', v => v
            ? document.documentElement.classList.add('portfolio-dark-pre')
            : document.documentElement.classList.remove('portfolio-dark-pre')
        );
     "
     :class="dark
         ? 'min-h-screen bg-slate-950 text-slate-100 antialiased portfolio-dark'
         : 'min-h-screen bg-slate-50 text-slate-900 antialiased'"
>
    <div class="min-h-screen flex flex-col">
        @auth
            @php
                // จัดหมวดหมู่ 5 กลุ่มหลัก เพื่อความเรียบง่าย สบายตา ใช้งานง่ายทั้ง Desktop และ Mobile
                $navGroups = [
                    [
                        'type'  => 'link',
                        'label' => __('app.portfolio.nav.dashboard'),
                        'short' => __('app.portfolio.nav_short.dashboard'),
                        'href'  => route('portfolio.dashboard'),
                        'match' => 'portfolio.dashboard',
                        'icon'  => 'home',
                    ],
                    [
                        'type'  => 'group',
                        'key'   => 'finance',
                        'label' => 'การเงิน & พอร์ต',
                        'short' => 'การเงิน',
                        'icon'  => 'wallet',
                        'badge' => null,
                        'match' => ['portfolio.finance.*', 'portfolio.budget.*', 'portfolio.planner', 'portfolio.ai.*', 'portfolio.ledger.*', 'portfolio.debts.*', 'portfolio.subscriptions.*'],
                        'items' => [
                            ['href' => route('portfolio.finance.index'),       'match' => 'portfolio.finance.*',       'label' => 'ศูนย์การเงิน & พอร์ต',                 'short' => 'ศูนย์การเงิน',                                'icon' => 'trending-up', 'desc' => 'ความมั่งคั่งสุทธิ สินทรัพย์ หนี้สิน และพอร์ตหุ้น'],
                            ['href' => route('portfolio.budget.index'),        'match' => 'portfolio.budget.*',        'label' => __('app.portfolio.nav.budget'),        'short' => __('app.portfolio.nav_short.budget'),        'icon' => 'wallet',      'desc' => 'บันทึกงบประมาณและกระเป๋าเงิน'],
                            ['href' => route('portfolio.planner'),             'match' => 'portfolio.planner',         'label' => __('app.portfolio.nav.planner'),       'short' => __('app.portfolio.nav_short.planner'),       'icon' => 'trending-up', 'desc' => 'แผนกระจายสินทรัพย์และเป้าหมาย'],
                            ['href' => route('portfolio.ai.index'),            'match' => 'portfolio.ai.*',            'label' => 'AI วิเคราะห์หุ้น',                     'short' => 'AI หุ้น',                                     'icon' => 'sparkles',    'desc' => 'วิเคราะห์และคัดกรองหุ้นด้วย AI'],
                            ['href' => route('portfolio.ledger.index'),        'match' => 'portfolio.ledger.*',        'label' => __('app.portfolio.nav.ledger'),        'short' => __('app.portfolio.nav_short.ledger'),        'icon' => 'book',        'desc' => 'สมุดบัญชีรายรับ-รายจ่าย'],
                            ['href' => route('portfolio.debts.index'),         'match' => 'portfolio.debts.*',         'label' => __('app.portfolio.nav.debts'),         'short' => __('app.portfolio.nav_short.debts'),         'icon' => 'card',        'desc' => 'ติดตามหนี้สินและตารางผ่อนชำระ'],
                            ['href' => route('portfolio.subscriptions.index'), 'match' => 'portfolio.subscriptions.*', 'label' => __('app.portfolio.nav.subscriptions'), 'short' => __('app.portfolio.nav_short.subscriptions'), 'icon' => 'repeat',      'desc' => 'รายการบิลและสมาชิกรายเดือน'],
                        ],
                    ],
                    [
                        'type'  => 'group',
                        'key'   => 'lifestyle',
                        'label' => 'ไลฟ์สไตล์ & สุขภาพ',
                        'short' => 'สุขภาพ & ชีวิต',
                        'icon'  => 'activity',
                        'badge' => 'Watch 7',
                        'match' => ['portfolio.health.*', 'portfolio.life-goals.*', 'portfolio.journey.*', 'portfolio.fuel.*'],
                        'items' => [
                            ['href' => route('portfolio.health.index'),        'match' => 'portfolio.health.*',        'label' => 'Health & Bio-Hacker 🏃‍♂️',              'short' => 'สุขภาพ Watch 7',                              'icon' => 'activity',    'desc' => 'สถิติสุขภาพ ก้าวเดิน หัวใจ การนอน Galaxy Watch 7'],
                            ['href' => route('portfolio.life-goals.index'),    'match' => 'portfolio.life-goals.*',    'label' => 'เป้าหมายชีวิต & Roadmap',            'short' => 'เป้าหมายชีวิต',                               'icon' => 'target',      'desc' => 'Life Goals ระยะสั้น กลาง ยาว'],
                            ['href' => route('portfolio.journey.index'),       'match' => 'portfolio.journey.*',       'label' => __('app.portfolio.nav.journey'),       'short' => __('app.portfolio.nav_short.journey'),       'icon' => 'map',         'desc' => 'บันทึกทริปและไทม์ไลน์ชีวิต'],
                            ['href' => route('portfolio.fuel.index'),          'match' => 'portfolio.fuel.*',          'label' => __('app.portfolio.nav.fuel'),          'short' => __('app.portfolio.nav_short.fuel'),          'icon' => 'fuel',        'desc' => 'บันทึกค่าน้ำมันและอัตราสิ้นเปลือง'],
                        ],
                    ],
                    [
                        'type'  => 'group',
                        'key'   => 'brain_ai',
                        'label' => 'Second Brain & AI',
                        'short' => 'AI & Brain',
                        'icon'  => 'brain',
                        'badge' => 'New',
                        'match' => ['portfolio.brain.*', 'portfolio.persona.*', 'portfolio.command-center.*'],
                        'items' => [
                            ['href' => route('portfolio.brain.index'),          'match' => 'portfolio.brain.*',         'label' => 'Second Brain (P.A.R.A.)',             'short' => 'สมองที่สอง',                                  'icon' => 'brain',       'desc' => 'จดบันทึกไอเดีย โปรเจกต์ ความรู้'],
                            ['href' => route('portfolio.persona.index'),        'match' => 'portfolio.persona.*',       'label' => 'Personal Profile (คู่มือตัวเอง)',      'short' => 'ตัวตน & นิสัย',                               'icon' => 'user',        'desc' => 'คู่มือชีวิต จุดแข็ง จุดอ่อน วิธีเติมพลัง'],
                            ['href' => route('portfolio.command-center.index'), 'match' => 'portfolio.command-center.*', 'label' => 'AI Command Center',                'short' => 'ศูนย์ AI 🕹️',                                 'icon' => 'sparkles',    'desc' => 'ห้องบัญชาการทีม AI 8-Bit Retro'],
                        ],
                    ],
                    [
                        'type'  => 'link',
                        'label' => 'Couple Space 💕',
                        'short' => 'คู่รัก 💕',
                        'href'  => route('portfolio.couple.index'),
                        'match' => 'portfolio.couple.*',
                        'icon'  => 'heart',
                    ],
                ];
            @endphp
            <header class="relative z-30" :class="dark
                ? 'border-b border-slate-800 bg-[#121418]'
                : 'border-b border-slate-200 bg-white'">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                    <a href="{{ route('portfolio.dashboard') }}" class="flex items-center gap-2.5">
                        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto object-contain">
                        <div class="hidden sm:block">
                            <div class="text-sm font-semibold leading-tight" :class="dark ? 'text-slate-200' : 'text-slate-900'">{{ __('app.portfolio.heading') }}</div>
                            <div class="text-xs text-slate-500">{{ __('app.portfolio.subheading') }}</div>
                        </div>
                    </a>

                    <div class="flex items-center gap-3">
                        {{-- Dark Mode Toggle --}}
                        <button @click="toggle()" type="button"
                                class="discord-hide relative flex h-8 w-8 items-center justify-center rounded-full transition"
                                :class="dark ? 'text-amber-400 hover:bg-slate-800' : 'text-slate-500 hover:bg-slate-100'"
                                :title="dark ? 'เปลี่ยนเป็น Light Mode' : 'เปลี่ยนเป็น Dark Mode'">
                            {{-- Sun icon (shown in dark mode → click to go light) --}}
                            <svg x-show="dark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            {{-- Moon icon (shown in light mode → click to go dark) --}}
                            <svg x-show="!dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>

                        @if (auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="" referrerpolicy="no-referrer"
                                 class="h-8 w-8 rounded-full ring-2" :class="dark ? 'ring-slate-700' : 'ring-slate-100'">
                        @else
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                                {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->email, 0, 1)) }}
                            </div>
                        @endif
                        <span class="hidden text-sm sm:inline" :class="dark ? 'text-slate-400' : 'text-slate-700'">{{ auth()->user()->name }}</span>

                        <form method="POST" action="{{ route('portfolio.logout') }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
                                    :class="dark
                                        ? 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200'">
                                {{ __('app.portfolio.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
                {{-- Desktop Top Bar Sub-navigation --}}
                <div class="hidden sm:block" :class="dark
                    ? 'border-t border-slate-800 bg-[#0b0c0e]'
                    : 'border-t border-slate-200 bg-slate-50'">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <nav class="-mb-px flex items-center space-x-1 sm:space-x-3" aria-label="Tabs">
                            @foreach ($navGroups as $group)
                                @if ($group['type'] === 'link')
                                    @php $isActive = request()->routeIs($group['match']); @endphp
                                    <a href="{{ $group['href'] }}"
                                       class="inline-flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-medium transition {{ $isActive ? 'border-brand-500 text-brand-400 font-semibold' : 'border-transparent text-slate-400 hover:border-slate-600 hover:text-slate-200' }}">
                                        @include('portfolio.partials.nav-icon', ['icon' => $group['icon'], 'class' => 'h-4 w-4 ' . ($isActive ? 'text-brand-400' : 'text-slate-400')])
                                        <span>{{ $group['label'] }}</span>
                                    </a>
                                @else
                                    @php
                                        $isGroupActive = collect($group['match'])->contains(fn($m) => request()->routeIs($m));
                                    @endphp
                                    <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                        <button type="button"
                                                @click="open = !open"
                                                class="inline-flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-medium transition cursor-pointer {{ $isGroupActive ? 'border-brand-500 text-brand-400 font-semibold' : 'border-transparent text-slate-400 hover:border-slate-600 hover:text-slate-200' }}"
                                                :aria-expanded="open.toString()">
                                            @include('portfolio.partials.nav-icon', ['icon' => $group['icon'], 'class' => 'h-4 w-4 ' . ($isGroupActive ? 'text-brand-400' : 'text-slate-400')])
                                            <span>{{ $group['label'] }}</span>
                                            @if (!empty($group['badge']))
                                                <span class="rounded-full bg-brand-500/20 px-1.5 py-0.2 text-[10px] font-semibold text-brand-400 border border-brand-500/30">{{ $group['badge'] }}</span>
                                            @endif
                                            <svg class="h-3.5 w-3.5 transition-transform duration-200 text-slate-400" :class="open ? 'rotate-180 text-brand-400' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        {{-- Dropdown Menu (Solid Opaque Surface) --}}
                                        <div x-show="open"
                                             x-cloak
                                             x-transition:enter="transition ease-out duration-150"
                                             x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                             x-transition:leave="transition ease-in duration-100"
                                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                             x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                                             class="portfolio-dropdown-menu absolute left-0 top-full z-50 mt-1 min-w-[290px] rounded-xl p-1.5 shadow-2xl">
                                            <div class="space-y-0.5">
                                                @foreach ($group['items'] as $sub)
                                                    @php $isSubActive = request()->routeIs($sub['match']); @endphp
                                                    <a href="{{ $sub['href'] }}"
                                                       class="portfolio-dropdown-item group flex items-start gap-3 rounded-lg px-3 py-2.5 text-xs transition {{ $isSubActive ? 'bg-brand-500/15 !text-brand-400 font-semibold' : '' }}">
                                                        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $isSubActive ? 'bg-brand-500/25 text-brand-400' : 'bg-slate-800/80 text-slate-400 group-hover:text-slate-100 group-hover:bg-slate-700' }}">
                                                            @include('portfolio.partials.nav-icon', ['icon' => $sub['icon'], 'class' => 'h-4 w-4'])
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="font-medium truncate flex items-center justify-between">
                                                                <span class="{{ $isSubActive ? '!text-brand-400' : '' }}">{{ $sub['label'] }}</span>
                                                                @if($isSubActive)
                                                                    <span class="h-1.5 w-1.5 rounded-full bg-brand-400"></span>
                                                                @endif
                                                            </div>
                                                            @if(!empty($sub['desc']))
                                                                <div class="text-[11px] text-slate-400 font-normal truncate mt-0.5 group-hover:text-slate-300">{{ $sub['desc'] }}</div>
                                                            @endif
                                                        </div>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </nav>
                    </div>
                </div>
            </header>

            {{-- แถบเมนูล่าง (เฉพาะมือถือ) 5 หมวดหมู่หลัก --}}
            <nav class="fixed bottom-0 left-0 right-0 z-40 border-t border-slate-800 bg-[#121418]/95 backdrop-blur-md sm:hidden"
                 style="padding-bottom: env(safe-area-inset-bottom);"
                 aria-label="{{ __('app.portfolio.heading') }}">
                <div class="flex items-center justify-around">
                    @foreach ($navGroups as $group)
                        @if ($group['type'] === 'link')
                            @php $isActive = request()->routeIs($group['match']); @endphp
                            <a href="{{ $group['href'] }}"
                               class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[10px] font-medium transition {{ $isActive ? 'text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200' }}">
                                @include('portfolio.partials.nav-icon', ['icon' => $group['icon'], 'class' => 'h-5 w-5 ' . ($isActive ? 'text-brand-400' : 'text-slate-400')])
                                <span class="truncate">{{ $group['short'] }}</span>
                            </a>
                        @else
                            @php $isGroupActive = collect($group['match'])->contains(fn($m) => request()->routeIs($m)); @endphp
                            <button type="button"
                                    @click="openDrawer('{{ $group['key'] }}')"
                                    class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[10px] font-medium transition cursor-pointer {{ $isGroupActive ? 'text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200' }}">
                                <div class="relative">
                                    @include('portfolio.partials.nav-icon', ['icon' => $group['icon'], 'class' => 'h-5 w-5 ' . ($isGroupActive ? 'text-brand-400' : 'text-slate-400')])
                                    @if ($isGroupActive)
                                        <span class="absolute -top-1 -right-1 h-2 w-2 rounded-full bg-brand-500 ring-2 ring-[#121418]"></span>
                                    @endif
                                </div>
                                <span class="truncate flex items-center gap-0.5">
                                    {{ $group['short'] }}
                                    <svg class="h-2.5 w-2.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                                </span>
                            </button>
                        @endif
                    @endforeach
                </div>
            </nav>

            {{-- Mobile Drawer Sheet (สำหรับหมวดหมู่แบบกลุ่ม) --}}
            <div x-show="mobileDrawer !== null"
                 x-cloak
                 class="fixed inset-0 z-50 sm:hidden"
                 role="dialog"
                 aria-modal="true">
                {{-- Backdrop --}}
                <div x-show="mobileDrawer !== null"
                     x-transition:enter="transition-opacity ease-linear duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity ease-linear duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="closeDrawer()"
                     class="fixed inset-0 bg-black/75 backdrop-blur-xs"></div>

                {{-- Slide-up panel --}}
                <div x-show="mobileDrawer !== null"
                     x-transition:enter="transition ease-out duration-250 transform"
                     x-transition:enter-start="translate-y-full"
                     x-transition:enter-end="translate-y-0"
                     x-transition:leave="transition ease-in duration-200 transform"
                     x-transition:leave-start="translate-y-0"
                     x-transition:leave-end="translate-y-full"
                     class="portfolio-mobile-drawer-panel fixed inset-x-0 bottom-0 z-50 max-h-[82vh] overflow-y-auto rounded-t-2xl border-t border-slate-700/80 p-4 pb-8 shadow-2xl">
                    
                    <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-slate-700/60"></div>

                    @foreach ($navGroups as $group)
                        @if ($group['type'] === 'group')
                            <div x-show="mobileDrawer === '{{ $group['key'] }}'" x-cloak>
                                <div class="mb-4 flex items-center justify-between border-b border-slate-800 pb-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500/10 text-brand-400">
                                            @include('portfolio.partials.nav-icon', ['icon' => $group['icon'], 'class' => 'h-5 w-5'])
                                        </div>
                                        <div>
                                            <h3 class="text-base font-bold">{{ $group['label'] }}</h3>
                                            <p class="text-xs text-slate-400">เลือกเมนูที่ต้องการเปิดใช้งาน</p>
                                        </div>
                                    </div>
                                    <button type="button" @click="closeDrawer()" class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>

                                <div class="space-y-1.5">
                                    @foreach ($group['items'] as $sub)
                                        @php $isSubActive = request()->routeIs($sub['match']); @endphp
                                        <a href="{{ $sub['href'] }}"
                                           @click="closeDrawer()"
                                           class="flex items-center justify-between rounded-xl p-3 transition {{ $isSubActive ? 'bg-brand-500/15 border border-brand-500/40 text-brand-400' : 'bg-slate-800/40 border border-slate-700/40 hover:bg-slate-800 text-slate-200' }}">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $isSubActive ? 'bg-brand-500/20 text-brand-400' : 'bg-slate-800 text-slate-300' }}">
                                                    @include('portfolio.partials.nav-icon', ['icon' => $sub['icon'], 'class' => 'h-5 w-5'])
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-sm font-semibold truncate">{{ $sub['label'] }}</div>
                                                    @if(!empty($sub['desc']))
                                                        <div class="text-xs text-slate-400 truncate">{{ $sub['desc'] }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Floating Quick Action Button (Mobile Only) --}}
            <div class="fixed bottom-16 right-4 z-40 sm:hidden">
                <button type="button"
                        @click="quickActionOpen = true"
                        class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-purple-600 via-indigo-600 to-pink-600 px-3.5 py-2 text-xs font-bold text-white shadow-xl shadow-purple-950/60 border border-purple-400/40 active:scale-95 transition-transform">
                    <svg class="h-4 w-4 animate-pulse text-yellow-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" /></svg>
                    <span>ด่วน</span>
                </button>
            </div>

            {{-- Quick Action Bottom Sheet (Mobile Only) --}}
            <div x-show="quickActionOpen"
                 x-cloak
                 class="fixed inset-0 z-50 sm:hidden"
                 role="dialog"
                 aria-modal="true">
                {{-- Backdrop --}}
                <div x-show="quickActionOpen"
                     x-transition:enter="transition-opacity ease-linear duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity ease-linear duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="quickActionOpen = false"
                     class="fixed inset-0 bg-black/80 backdrop-blur-xs"></div>

                {{-- Slide-up panel --}}
                <div x-show="quickActionOpen"
                     x-transition:enter="transition ease-out duration-250 transform"
                     x-transition:enter-start="translate-y-full"
                     x-transition:enter-end="translate-y-0"
                     x-transition:leave="transition ease-in duration-200 transform"
                     x-transition:leave-start="translate-y-0"
                     x-transition:leave-end="translate-y-full"
                     class="portfolio-mobile-drawer-panel fixed inset-x-0 bottom-0 z-50 max-h-[85vh] overflow-y-auto rounded-t-3xl border-t border-purple-500/40 p-5 pb-8 shadow-2xl">
                    
                    <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-slate-700/60"></div>

                    <div class="mb-4 flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-500/20 text-purple-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">⚡ Mobile Quick Actions</h3>
                                <p class="text-xs text-slate-400">ปุ่มลัดทำงานด่วนบนมือถือสำหรับ Folk</p>
                            </div>
                        </div>
                        <button type="button" @click="quickActionOpen = false" class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        {{-- 1. ลงค่าน้ำมันด่วน --}}
                        <a href="{{ route('portfolio.fuel.index') }}" @click="quickActionOpen = false"
                           class="flex flex-col items-center justify-center rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4 text-center hover:bg-amber-500/20 active:scale-95 transition">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500/20 text-2xl mb-2">⛽</div>
                            <span class="text-xs font-bold text-amber-300">ลงค่าน้ำมัน</span>
                            <span class="text-[10px] text-amber-400/80 mt-0.5">บันทึกหน้าตู้ปั๊ม</span>
                        </a>

                        {{-- 2. จดรายจ่ายด่วน --}}
                        <a href="{{ route('portfolio.ledger.index') }}" @click="quickActionOpen = false"
                           class="flex flex-col items-center justify-center rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-center hover:bg-emerald-500/20 active:scale-95 transition">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/20 text-2xl mb-2">💸</div>
                            <span class="text-xs font-bold text-emerald-300">จดรายจ่าย</span>
                            <span class="text-[10px] text-emerald-400/80 mt-0.5">บันทึกสมุดบัญชี</span>
                        </a>

                        {{-- 3. One-Tap Shift & Health Sync --}}
                        <form action="{{ route('portfolio.calendar.sync') }}" method="POST" class="col-span-1">
                            @csrf
                            <button type="submit"
                                    class="w-full flex flex-col items-center justify-center rounded-2xl border border-cyan-500/30 bg-cyan-500/10 p-4 text-center hover:bg-cyan-500/20 active:scale-95 transition">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-500/20 text-2xl mb-2">🔄</div>
                                <span class="text-xs font-bold text-cyan-300">ซิงค์เวร & นาฬิกา</span>
                                <span class="text-[10px] text-cyan-400/80 mt-0.5">Google Cal + Watch 7</span>
                            </button>
                        </form>

                        {{-- 4. โน้ตสมองที่สอง (Second Brain) --}}
                        <a href="{{ route('portfolio.brain.index') }}" @click="quickActionOpen = false"
                           class="flex flex-col items-center justify-center rounded-2xl border border-purple-500/30 bg-purple-500/10 p-4 text-center hover:bg-purple-500/20 active:scale-95 transition">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-500/20 text-2xl mb-2">🧠</div>
                            <span class="text-xs font-bold text-purple-300">ดรอปโน้ตด่วน</span>
                            <span class="text-[10px] text-purple-400/80 mt-0.5">Second Brain</span>
                        </a>
                    </div>

                    {{-- 5. ติดตั้ง PWA App --}}
                    <div class="rounded-2xl border border-slate-700 bg-slate-800/60 p-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <img src="/pwa-192x192.png" alt="App Icon" class="h-10 w-10 rounded-xl shadow border border-slate-600">
                            <div>
                                <div class="text-xs font-bold text-white flex items-center gap-1.5">
                                    <span>ติดตั้งแอปลงมือถือ</span>
                                    <span class="rounded bg-indigo-500/20 px-1.5 py-0.5 text-[9px] font-semibold text-indigo-300 border border-indigo-500/30">PWA</span>
                                </div>
                                <div class="text-[10px] text-slate-400">เปิดแบบเต็มจอ ไร้แถบ URL กวนใจ</div>
                            </div>
                        </div>
                        <button type="button" @click="installPwa()" class="rounded-xl bg-indigo-600 hover:bg-indigo-500 px-3 py-1.5 text-xs font-bold text-white shadow active:scale-95 transition cursor-pointer">
                            ติดตั้ง 📲
                        </button>
                    </div>
                </div>
            </div>
        @endauth

        <main class="flex-1">
            {{ $slot }}
            @auth
                {{-- เว้นที่ให้แถบเมนูล่าง (มือถือ) ไม่ทับเนื้อหา — รวม safe-area ของ iPhone --}}
                <div class="sm:hidden" aria-hidden="true" style="height: calc(3.75rem + env(safe-area-inset-bottom));"></div>
            @endauth
        </main>
    </div>

    @include('partials.sweetalert')
    @livewireScripts

    {{-- Progressive Web App Service Worker Registration --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('PWA Service Worker registered:', reg.scope))
                    .catch(err => console.log('PWA SW registration failed:', err));
            });
        }
    </script>
</body>
</html>
