<x-portfolio-layout>
<style>
    /* Quick-pick chip styles for Core & Satellite */
    .chip-satellite {
        background-color: #faf5ff;
        color: #7e22ce;
        border-color: #e9d5ff;
    }
    .chip-satellite:hover {
        background-color: #f3e8ff;
        color: #6b21a8;
    }
    .portfolio-dark .chip-satellite {
        background-color: rgba(168, 85, 247, 0.15) !important;
        color: #c084fc !important;
        border-color: rgba(168, 85, 247, 0.35) !important;
    }
    .portfolio-dark .chip-satellite:hover {
        background-color: rgba(168, 85, 247, 0.28) !important;
        color: #f3e8ff !important;
        border-color: rgba(168, 85, 247, 0.55) !important;
    }
    .chip-core {
        background-color: #ecfdf5;
        color: #065f46;
        border-color: #a7f3d0;
    }
    .chip-core:hover {
        background-color: #d1fae5;
        color: #047857;
    }
    .portfolio-dark .chip-core {
        background-color: rgba(16, 185, 129, 0.15) !important;
        color: #34d399 !important;
        border-color: rgba(16, 185, 129, 0.35) !important;
    }
    .portfolio-dark .chip-core:hover {
        background-color: rgba(16, 185, 129, 0.28) !important;
        color: #6ee7b7 !important;
        border-color: rgba(16, 185, 129, 0.55) !important;
    }
    /* DCA Planner container & projection cards dark mode */
    .dca-planner-card {
        background-color: #ffffff;
        border-color: #a7f3d0;
    }
    .portfolio-dark .dca-planner-card {
        background-color: #141720 !important;
        background-image: none !important;
        border-color: rgba(16, 185, 129, 0.25) !important;
    }
    .dca-projection-card {
        background-color: #f8fafc;
        border-color: #e2e8f0;
    }
    .portfolio-dark .dca-projection-card {
        background-color: #0d1017 !important;
        border-color: #1e2433 !important;
    }
    .dca-quick-btn {
        background-color: rgba(16, 185, 129, 0.08);
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    .dca-quick-btn:hover {
        background-color: rgba(16, 185, 129, 0.16);
    }
    .portfolio-dark .dca-quick-btn {
        background-color: rgba(16, 185, 129, 0.15) !important;
        color: #34d399 !important;
        border-color: rgba(16, 185, 129, 0.3) !important;
    }
    .portfolio-dark .dca-quick-btn:hover {
        background-color: rgba(16, 185, 129, 0.25) !important;
        color: #6ee7b7 !important;
    }
</style>
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="stockAiWorkspace()">

    {{-- ── Main Header ─────────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-bold text-slate-900">✨ ศูนย์วางแผนลงทุน & AI วิเคราะห์หุ้นครบวงจร</h1>
                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-semibold text-indigo-700">
                    Yahoo Finance + Gemini AI + Paper Trade
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-500">
                วิเคราะห์ 10 Prompt ยอดนิยม · คำนวณขนาดไม้ & วางแผนซื้อ 3 ไม้ · พอร์ตจำลองบันทึกจริง · เฝ้าระวังพอร์ตจริง · ประเมินมูลค่าหุ้น
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('portfolio.ai.refresh-prices') }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                        title="ดึงราคาล่าสุดจาก Yahoo Finance สำหรับพอร์ตจริงและพอร์ตจำลอง">
                    <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    รีเฟรชราคาตลาดสด
                </button>
            </form>
            <button @click="apiKeyModal = !apiKeyModal" type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                <span class="h-2 w-2 rounded-full {{ $hasGeminiKey ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                {{ $hasGeminiKey ? 'Gemini AI เชื่อมต่อแล้ว' : 'ตั้งค่า Gemini Key' }}
            </button>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800 shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 flex items-center justify-between rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-800 shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- ── Collapsible Gemini API Key Settings ────────────────────────── --}}
    <div x-show="apiKeyModal" x-cloak class="mb-6 rounded-2xl border bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <div>
                <h2 class="text-sm font-bold text-slate-800">ตั้งค่า Google Gemini API Key</h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    ระบบจะดึงข้อมูลราคา แนวรับ-แนวต้าน RSI และ Fibonacci จริงจาก Yahoo Finance เสมอ และหากใส่ Gemini API Key ระบบจะส่งตัวเลขจริงให้ Gemini เขียนบทวิเคราะห์ภาษาไทยเชิงลึกทันที
                </p>
            </div>
            <button @click="apiKeyModal = false" type="button" class="text-xs font-semibold text-slate-400 hover:text-slate-600">✕ ปิด</button>
        </div>
        <form method="POST" action="{{ route('portfolio.ai.api-key') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex-1 min-w-0">
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Gemini API Key (เข้ารหัสปลอดภัยในฐานข้อมูล)</label>
                <input type="password" name="gemini_key" required placeholder="AIzaSy..."
                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <button type="submit"
                    class="rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700 transition">
                บันทึก API Key
            </button>
        </form>
    </div>

    {{-- ── 5 Main Workflow Tabs Navigation ─────────────────────────────── --}}
    <div class="mb-6 border-b border-slate-200">
        <nav class="-mb-px flex flex-wrap gap-x-6 gap-y-2" aria-label="Tabs">
            <button type="button" @click="activeTab = 'prompts'; window.location.hash = 'prompts'"
                    :class="activeTab === 'prompts' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                    class="border-b-2 px-1 py-3 text-sm font-medium transition whitespace-nowrap flex items-center gap-2">
                <span>🤖</span>
                <span>1. AI 10 Prompts & กราฟสด</span>
            </button>
            <button type="button" @click="activeTab = 'sniper'; window.location.hash = 'sniper'"
                    :class="activeTab === 'sniper' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                    class="border-b-2 px-1 py-3 text-sm font-medium transition whitespace-nowrap flex items-center gap-2">
                <span>🎯</span>
                <span>2. วางแผนเข้าซื้อ & ถัวเฉลี่ย</span>
            </button>
            <button type="button" @click="activeTab = 'paper'; window.location.hash = 'paper'"
                    :class="activeTab === 'paper' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                    class="border-b-2 px-1 py-3 text-sm font-medium transition whitespace-nowrap flex items-center gap-2">
                <span>🧪</span>
                <span>3. พอร์ตจำลอง (Paper Trading)</span>
                @if($openPaperTrades->count() > 0)
                    <span class="rounded-full bg-sky-100 px-1.5 py-0.2 text-[10px] font-bold text-sky-700">{{ $openPaperTrades->count() }}</span>
                @endif
            </button>
            <button type="button" @click="activeTab = 'real'; window.location.hash = 'real'"
                    :class="activeTab === 'real' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                    class="border-b-2 px-1 py-3 text-sm font-medium transition whitespace-nowrap flex items-center gap-2">
                <span>💼</span>
                <span>4. พอร์ตจริง & จุดเฝ้าระวัง</span>
                <span class="rounded-full bg-emerald-100 px-1.5 py-0.2 text-[10px] font-bold text-emerald-700">{{ $holdings->count() }}</span>
            </button>
            <button type="button" @click="activeTab = 'valuation'; window.location.hash = 'valuation'"
                    :class="activeTab === 'valuation' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                    class="border-b-2 px-1 py-3 text-sm font-medium transition whitespace-nowrap flex items-center gap-2">
                <span>⚖️</span>
                <span>5. ประเมินมูลค่า & ปรับพอร์ต</span>
            </button>
        </nav>
    </div>

    {{-- ================================================================= --}}
    {{-- TAB 1: 🤖 10 POPULAR INVESTMENT PROMPTS & LIVE TECHNICALS         --}}
    {{-- ================================================================= --}}
    <div x-show="activeTab === 'prompts'" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- 10 Prompts Selector (Left) --}}
            <div class="space-y-3">
                <div class="rounded-2xl border bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-bold text-slate-800">🔥 10 Prompt ลงทุนยอดนิยม</h2>
                        <span class="text-[11px] text-slate-500">คลิกเลือกหัวข้อ</span>
                    </div>

                    <div class="space-y-2">
                        <template x-for="item in prompts" :key="item.id">
                            <button type="button"
                                    @click="selectPrompt(item.id)"
                                    class="w-full text-left rounded-xl border p-3 transition"
                                    :class="activePromptId === item.id
                                        ? 'border-brand-600 bg-indigo-50/60 shadow-sm'
                                        : 'border-slate-200 bg-white hover:bg-slate-50'">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                          :class="activePromptId === item.id ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600'"
                                          x-text="'#' + item.id + ' · ' + item.category"></span>
                                    <span class="text-[10px] font-medium text-slate-500" x-text="item.badge"></span>
                                </div>
                                <p class="text-xs font-semibold text-slate-800 leading-snug" x-text="item.title"></p>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Prompt Customizer & Results (Right 2 Cols) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Prompt Box --}}
                <div class="rounded-2xl border bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-4 mb-4">
                        <div>
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-[10px] font-semibold text-indigo-700"
                                  x-text="activePrompt.badge"></span>
                            <h2 class="mt-1 text-base font-bold text-slate-900" x-text="activePrompt.title"></h2>
                        </div>
                        <button type="button" @click="resetCurrentFields()"
                                class="rounded-lg border border-slate-200 px-2.5 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-50 transition">
                            รีเซ็ตค่าเริ่มต้น
                        </button>
                    </div>

                    {{-- Quick-pick stock chips: Categorized into Core (Beat Inflation) & Satellite (Mega-Trends) --}}
                    <div class="mb-4 space-y-2">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-[11px] font-bold text-slate-700 mr-1">🛡️ หุ้นพื้นฐานชนะเงินเฟ้อ (Core):</span>
                            <template x-for="sym in coreTickers" :key="sym">
                                <button type="button" @click="fillQuickTicker(sym)"
                                        class="chip-core rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-800 hover:bg-emerald-100 transition shadow-none cursor-pointer"
                                        x-text="sym"></button>
                            </template>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-[11px] font-bold text-slate-700 mr-1">🚀 หุ้นใหม่เกาะกระแส (Satellite):</span>
                            <template x-for="sym in trendTickers" :key="sym">
                                <button type="button" @click="fillQuickTicker(sym)"
                                        class="chip-satellite rounded-full border border-purple-200 bg-purple-50 px-2.5 py-0.5 text-[10px] font-semibold text-purple-800 hover:bg-purple-100 transition shadow-none cursor-pointer"
                                        x-text="sym"></button>
                            </template>
                            @if($holdings->isNotEmpty())
                                @foreach($holdings->take(3) as $h)
                                    @if($h->symbol)
                                    <button type="button" @click="fillQuickTicker('{{ $h->symbol }}')"
                                            class="rounded-full border border-sky-200 bg-sky-50 px-2.5 py-0.5 text-[10px] font-semibold text-sky-800 hover:bg-sky-100 transition shadow-none cursor-pointer">
                                        💼 {{ $h->symbol }}
                                    </button>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- Dynamic Inputs --}}
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 mb-4">
                        <template x-for="field in activePrompt.fields" :key="field.key">
                            <div :class="activePrompt.fields.length === 1 ? 'sm:col-span-3' : (activePrompt.fields.length === 2 ? 'sm:col-span-1' : '')">
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1" x-text="field.label"></label>
                                <input type="text"
                                       x-model="fieldValues[field.key]"
                                       @focus="focusedFieldKey = field.key"
                                       @input="rebuildPromptText()"
                                       :placeholder="field.placeholder"
                                       class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-800">
                            </div>
                        </template>
                    </div>

                    {{-- Textarea --}}
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700">คำสั่ง Prompt พร้อมใช้งาน</label>
                            <span class="text-[11px] text-emerald-600 font-semibold" x-show="copiedPrompt" x-cloak>✓ คัดลอก Prompt แล้ว!</span>
                        </div>
                        <textarea x-model="customPromptText" rows="3"
                                  class="block w-full rounded-xl border border-slate-300 bg-slate-50/50 p-3 text-sm text-slate-800 leading-relaxed"></textarea>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" @click="runAnalysis()"
                                    :disabled="loading"
                                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-700 transition">
                                <svg x-show="!loading" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-text="loading ? 'กำลังดึงกราฟจริง & วิเคราะห์...' : '🚀 ให้ AI วิเคราะห์ทันที (พร้อมกราฟจริง)'"></span>
                            </button>
                            <button type="button" @click="copyPrompt()"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                คัดลอก Prompt
                            </button>
                        </div>
                        <span class="text-[11px] text-slate-400" x-show="lastGeneratedAt" x-cloak x-text="'วิเคราะห์ล่าสุด: ' + lastGeneratedAt"></span>
                    </div>
                </div>

                {{-- Live News & Sentiment Analyzer Section --}}
                <div class="rounded-2xl border bg-white p-5 shadow-sm space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600 text-base font-bold">📰</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">ดึงข่าวสารสด & วิเคราะห์ Sentiment แนวโน้ม (Google News & Yahoo Finance)</h3>
                                <p class="text-[11px] text-slate-500">ดึงข่าวการเงินจริงของหุ้นไทย (SET) และหุ้นสหรัฐฯ ย้อนหลัง 7 วัน แล้วให้ AI วิเคราะห์ทิศทางราคา</p>
                            </div>
                        </div>
                        <template x-if="sentimentData && sentimentData.label">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
                                  :class="{
                                      'bg-emerald-50 text-emerald-700 border border-emerald-200': sentimentData.score >= 60,
                                      'bg-rose-50 text-rose-700 border border-rose-200': sentimentData.score <= 40,
                                      'bg-amber-50 text-amber-700 border border-amber-200': sentimentData.score > 40 && sentimentData.score < 60
                                  }">
                                <span class="h-2 w-2 rounded-full"
                                      :class="{
                                          'bg-emerald-500': sentimentData.score >= 60,
                                          'bg-rose-500': sentimentData.score <= 40,
                                          'bg-amber-500': sentimentData.score > 40 && sentimentData.score < 60
                                      }"></span>
                                <span x-text="'Sentiment: ' + sentimentData.label + ' (' + sentimentData.score + '%)'"></span>
                            </span>
                        </template>
                    </div>

                    {{-- Search & Action Controls --}}
                    <div class="space-y-2">
                        <div class="flex flex-wrap sm:flex-nowrap gap-2">
                            <div class="relative flex-1">
                                <input type="text" x-model="newsTicker" @keydown.enter.prevent="fetchNewsFor(newsTicker)"
                                       placeholder="พิมพ์ชื่อหุ้น เช่น DELTA.BK, PTT.BK, CPALL.BK, NVDA, TSLA, AAPL"
                                       class="block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-semibold text-slate-800 placeholder-slate-400 focus:border-brand-500 focus:ring-brand-500">
                            </div>
                            <button type="button" @click="fetchNewsFor(newsTicker)" :disabled="loadingNews"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 transition disabled:opacity-50">
                                <span x-show="loadingNews" class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-slate-600 border-t-transparent"></span>
                                <span x-text="loadingNews ? 'กำลังค้นหาข่าว...' : '🔍 ดึงข่าวสารล่าสุด'"></span>
                            </button>
                            <button type="button" @click="analyzeNewsTrend(newsTicker)" :disabled="loading"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:from-brand-500 hover:to-indigo-500 transition disabled:opacity-50">
                                <span>🧠 ให้ AI วิเคราะห์แนวโน้มจากข่าว</span>
                            </button>
                        </div>

                        {{-- Quick Pills --}}
                        <div class="flex flex-wrap items-center gap-1.5 pt-1">
                            <span class="text-[10px] font-semibold text-slate-400">เลือกหุ้นส่องข่าวด่วน:</span>
                            @if($holdings->isNotEmpty())
                                @foreach($holdings->take(3) as $h)
                                    @if($h->symbol)
                                    <button type="button" @click="fetchNewsFor('{{ $h->symbol }}')"
                                            class="rounded-lg border px-2 py-0.5 text-[10px] font-medium transition"
                                            :class="newsTicker === '{{ $h->symbol }}' ? 'border-emerald-500 bg-emerald-50 text-emerald-700 font-bold' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                                        💼 {{ $h->symbol }}
                                    </button>
                                    @endif
                                @endforeach
                            @endif
                            <template x-for="sym in ['DELTA.BK', 'PTT.BK', 'CPALL.BK', 'ADVANC.BK', 'NVDA', 'TSLA', 'AAPL']" :key="sym">
                                <button type="button" @click="fetchNewsFor(sym)"
                                        class="rounded-lg border px-2 py-0.5 text-[10px] font-medium transition"
                                        :class="newsTicker === sym ? 'border-brand-500 bg-brand-50 text-brand-700 font-bold' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                                        x-text="sym"></button>
                            </template>
                        </div>
                    </div>

                    {{-- Live News Headlines List --}}
                    <div x-show="liveNews.length > 0" class="space-y-2 pt-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700" x-text="'📰 พาดหัวข่าวล่าสุดของ ' + newsTicker + ' (' + liveNews.length + ' ข่าวล่าสุด)'"></span>
                            <span class="text-[10px] text-slate-400">คลิกที่ข่าวเพื่อเปิดดูรายละเอียดฉบับเต็ม</span>
                        </div>

                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <template x-for="(news, idx) in liveNews" :key="idx">
                                <a :href="news.link || '#'" target="_blank" rel="noopener noreferrer"
                                   class="group block rounded-xl border border-slate-200 bg-slate-50/50 p-3 hover:border-brand-300 hover:bg-indigo-50/30 transition">
                                    <div class="flex items-start justify-between gap-2 mb-1">
                                        <span class="inline-flex items-center rounded-md bg-white px-2 py-0.5 text-[10px] font-bold text-slate-700 border border-slate-200"
                                              x-text="news.publisher"></span>
                                        <span class="text-[10px] text-slate-400 shrink-0" x-text="news.pub_date"></span>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-800 group-hover:text-brand-600 leading-snug line-clamp-2"
                                       x-text="news.title"></p>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Interactive TradingView Chart Section --}}
                <div id="chart-section" class="rounded-2xl border bg-white p-5 shadow-sm space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 text-base font-bold">📈</span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-slate-900">กราฟเทคนิคสดแบบ Interactive (Live TradingView Chart)</h3>
                                    <span class="rounded bg-brand-50 px-2 py-0.5 text-[10px] font-bold text-brand-700" x-text="chartTicker"></span>
                                </div>
                                <p class="text-[11px] text-slate-500">ดูกราฟแท่งเทียนสด, Volume, เส้นค่าเฉลี่ย Moving Average และ RSI สำหรับหุ้นไทยและหุ้นโลก</p>
                            </div>
                        </div>

                        {{-- Chart Controls & Timeframe --}}
                        <div class="flex items-center gap-1.5">
                            <button type="button" @click="changeChartInterval('D')"
                                    class="rounded-lg px-2.5 py-1 text-xs font-semibold transition"
                                    :class="chartInterval === 'D' ? 'bg-brand-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50'">
                                Day
                            </button>
                            <button type="button" @click="changeChartInterval('W')"
                                    class="rounded-lg px-2.5 py-1 text-xs font-semibold transition"
                                    :class="chartInterval === 'W' ? 'bg-brand-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50'">
                                Week
                            </button>
                            <button type="button" @click="changeChartInterval('M')"
                                    class="rounded-lg px-2.5 py-1 text-xs font-semibold transition"
                                    :class="chartInterval === 'M' ? 'bg-brand-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50'">
                                Month
                            </button>
                        </div>
                    </div>

                    {{-- Quick Ticker Selector for Chart --}}
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-[10px] font-bold text-slate-600 mr-1">สลับดูกราฟด่วน:</span>
                            <span class="text-[10px] font-semibold text-emerald-700">🛡️ ชนะเงินเฟ้อ:</span>
                            <template x-for="sym in coreTickers.slice(0, 4)" :key="sym">
                                <button type="button" @click="openChart(sym)"
                                        class="rounded-lg border px-2 py-0.5 text-[10px] font-medium transition"
                                        :class="chartTicker === sym ? 'border-emerald-600 bg-emerald-600 text-white font-bold' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                                        x-text="sym"></button>
                            </template>
                            <span class="text-[10px] font-semibold text-purple-700 ml-1">🚀 เกาะกระแส:</span>
                            <template x-for="sym in trendTickers.slice(0, 4)" :key="sym">
                                <button type="button" @click="openChart(sym)"
                                        class="rounded-lg border px-2 py-0.5 text-[10px] font-medium transition"
                                        :class="chartTicker === sym ? 'border-purple-600 bg-purple-600 text-white font-bold' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                                        x-text="sym"></button>
                            </template>
                        </div>
                    </div>

                    {{-- Chart Canvas Container --}}
                    <div class="w-full rounded-xl overflow-hidden border border-slate-200 bg-slate-50" style="height: 500px;">
                        <div id="tradingview_chart_container" class="w-full h-full"></div>
                    </div>
                </div>

                {{-- Real-Time Market & Indicator Cards with Workflow Action Buttons --}}
                <div x-show="marketData.length > 0" x-cloak class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-800">📈 สัญญาณเทคนิคจริง (Yahoo Finance 6-Month Daily)</h3>
                        <span class="text-[11px] text-slate-500">คลิกปุ่มเพื่อส่งค่าเข้าสู่เครื่องมือวางแผนหรือพอร์ตจำลอง</span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <template x-for="stock in marketData" :key="stock.symbol">
                            <div class="rounded-2xl border bg-white p-4 shadow-sm space-y-3">
                                <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2.5">
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-base font-bold text-slate-900" x-text="stock.symbol"></span>
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600" x-text="stock.currency"></span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 truncate max-w-xs" x-text="stock.name"></p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-slate-900" x-text="stock.price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></div>
                                        <div class="text-[11px] font-semibold"
                                             :class="stock.change_1d_pct >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                                             x-text="(stock.change_1d_pct >= 0 ? '+' : '') + stock.change_1d_pct + '% (1D)'"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div class="rounded-lg bg-slate-50 p-2">
                                        <span class="block text-[10px] text-slate-500">SMA 20 / 50</span>
                                        <span class="font-bold text-slate-800" x-text="stock.sma20 + ' / ' + stock.sma50"></span>
                                    </div>
                                    <div class="rounded-lg bg-slate-50 p-2">
                                        <span class="block text-[10px] text-slate-500">RSI 14 & Vol</span>
                                        <span class="font-bold"
                                              :class="stock.rsi14 >= 70 ? 'text-rose-600' : (stock.rsi14 <= 30 ? 'text-emerald-600' : 'text-sky-600')"
                                              x-text="'RSI ' + stock.rsi14 + ' · ' + stock.vol_ratio + 'x'"></span>
                                    </div>
                                    <div class="rounded-lg bg-emerald-50/60 p-2">
                                        <span class="block text-[10px] text-emerald-700">แนวรับ 1 / 2</span>
                                        <span class="font-bold text-emerald-700" x-text="stock.support1 + ' / ' + stock.support2"></span>
                                    </div>
                                    <div class="rounded-lg bg-rose-50/60 p-2">
                                        <span class="block text-[10px] text-rose-700">แนวต้าน 1 / 2</span>
                                        <span class="font-bold text-rose-700" x-text="stock.resistance1 + ' / ' + stock.resistance2"></span>
                                    </div>
                                </div>

                                <div class="border-t border-slate-100 pt-2 text-[11px] text-slate-600 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500">Fibonacci 38.2% / 61.8%:</span>
                                        <span class="font-semibold text-slate-800" x-text="stock.fib_levels['38.2%'] + ' / ' + stock.fib_levels['61.8% (Golden)']"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500">Stop Loss แนะนำ (-3.5%):</span>
                                        <span class="font-semibold text-rose-600" x-text="stock.stop_loss + ' ' + stock.currency"></span>
                                    </div>
                                </div>

                                {{-- Workflow Connectors Buttons --}}
                                <div class="border-t border-slate-100 pt-2 flex flex-wrap items-center gap-1.5">
                                    <button type="button" @click="sendToSniper(stock)"
                                            class="flex-1 rounded-lg border border-brand-200 bg-brand-50 px-2 py-1 text-[11px] font-semibold text-brand-700 hover:bg-brand-100 transition text-center">
                                        🎯 ส่งไปวางแผนจุดเข้าซื้อ
                                    </button>
                                    <button type="button" @click="sendToPaperTrade(stock)"
                                            class="rounded-lg border border-sky-200 bg-sky-50 px-2 py-1 text-[11px] font-semibold text-sky-700 hover:bg-sky-100 transition">
                                        🧪 ซื้อจำลอง
                                    </button>
                                    <button type="button" @click="openChart(stock.symbol)"
                                            class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100 transition">
                                        📈 ดูกราฟสด
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- AI Output Report --}}
                <div x-show="analysisText" x-cloak class="rounded-2xl border bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-4 mb-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-slate-900">🤖 รายงานบทวิเคราะห์การลงทุน</h3>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-700" x-text="modelLabel"></span>
                        </div>
                        <button type="button" @click="copyAnalysis()"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            <span x-text="copiedAnalysis ? '✓ คัดลอกแล้ว' : '📋 คัดลอกบทวิเคราะห์'"></span>
                        </button>
                    </div>
                    <div class="prose max-w-none text-sm text-slate-700 leading-relaxed space-y-2" x-html="renderMarkdown(analysisText)"></div>
                </div>

            </div>
        </div>
    </div>

    {{-- ================================================================= --}}
    {{-- TAB 2: 🎯 SNIPER ENTRY, POSITION SIZING & AVERAGE DOWN PLANNER     --}}
    {{-- ================================================================= --}}
    <div x-show="activeTab === 'sniper'" class="space-y-6" x-cloak>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Tool 1: Position Sizing & Risk/Reward Calculator (Col 1 & 2) --}}
            <div class="lg:col-span-2 rounded-2xl border bg-white p-6 shadow-sm space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">🎯 เครื่องคำนวณขนาดไม้ & อัตราความคุ้มค่า (Position Sizing & Risk/Reward)</h2>
                        <p class="text-xs text-slate-500 mt-0.5">คำนวณจำนวนหุ้นสูงสุดที่ควรซื้อเพื่อไม่ให้ขาดทุนเกิน % ความเสี่ยงที่ยอมรับได้</p>
                    </div>
                    <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-[10px] font-bold text-brand-700">Risk Management</span>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">ชื่อหุ้น (Ticker)</label>
                        <input type="text" x-model="sniper.symbol" placeholder="เช่น NVDA หรือ ADVANC.BK"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-bold text-slate-800">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">ขนาดพอร์ตทั้งหมด (฿)</label>
                        <input type="number" step="1000" x-model.number="sniper.capital"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-800">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">ความเสี่ยงที่ยอมเสียต่อไม้ (%)</label>
                        <select x-model.number="sniper.riskPct" class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                            <option value="1">1% ของพอร์ต (อนุรักษนิยม Conservative)</option>
                            <option value="2">2% ของพอร์ต (มาตรฐาน Standard)</option>
                            <option value="3">3% ของพอร์ต (ยอมรับเสี่ยงปานกลาง)</option>
                            <option value="5">5% ของพอร์ต (เชิงรุก Aggressive)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">ราคาเข้าซื้อ (Entry Price)</label>
                        <input type="number" step="0.01" x-model.number="sniper.entry" placeholder="0.00"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-800">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-rose-600 mb-1">จุดตัดขาดทุน (Stop Loss)</label>
                        <input type="number" step="0.01" x-model.number="sniper.stopLoss" placeholder="0.00"
                               class="block w-full rounded-lg border border-rose-300 px-3 py-1.5 text-sm font-semibold text-rose-700 bg-rose-50/30">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-emerald-600 mb-1">เป้าหมายทำกำไร (Target Price)</label>
                        <input type="number" step="0.01" x-model.number="sniper.target" placeholder="0.00"
                               class="block w-full rounded-lg border border-emerald-300 px-3 py-1.5 text-sm font-semibold text-emerald-700 bg-emerald-50/30">
                    </div>
                </div>

                {{-- Computed Output Card --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 text-center">
                        <div>
                            <span class="block text-[10px] text-slate-500 uppercase tracking-wide">จำนวนหุ้นแนะนำ</span>
                            <span class="text-lg font-bold text-slate-900" x-text="sniperOutput.shares.toLocaleString() + ' หุ้น'"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] text-slate-500 uppercase tracking-wide">มูลค่าเงินที่ใช้ซื้อ</span>
                            <span class="text-lg font-bold text-slate-900" x-text="'฿' + sniperOutput.totalCost.toLocaleString('en-US', {maximumFractionDigits: 0})"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] text-rose-600 uppercase tracking-wide">ขาดทุนสูงสุดหากคัท (Max Risk)</span>
                            <span class="text-lg font-bold text-rose-600" x-text="'-฿' + sniperOutput.maxRiskMoney.toLocaleString('en-US', {maximumFractionDigits: 0})"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] text-emerald-600 uppercase tracking-wide">กำไรเป้าหมาย (Expected Profit)</span>
                            <span class="text-lg font-bold text-emerald-600" x-text="'+฿' + sniperOutput.expectedProfit.toLocaleString('en-US', {maximumFractionDigits: 0})"></span>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 pt-3 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-slate-700">Risk : Reward Ratio =</span>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                                  :class="sniperOutput.rrRatio >= 2.0 ? 'bg-emerald-100 text-emerald-800' : (sniperOutput.rrRatio >= 1.5 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')"
                                  x-text="'1 : ' + sniperOutput.rrRatio"></span>
                            <span class="text-slate-500" x-text="sniperOutput.rrRatio >= 2.0 ? '✓ คุ้มค่าเสี่ยงมาก (ดีเยี่ยม)' : (sniperOutput.rrRatio >= 1.5 ? 'พอใช้ได้' : '⚠️ ไม่คุ้มเสี่ยง ควรหาจุดเข้าใหม่')"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-500">จุดคุ้มทุนรวมคอมมิชชัน (0.157% + VAT):</span>
                            <span class="font-bold text-slate-800" x-text="sniperOutput.breakevenPrice"></span>
                        </div>
                    </div>
                </div>

                {{-- Action: Send to Paper Trade --}}
                <div class="flex items-center justify-end gap-2">
                    <button type="button" @click="sendSniperToPaperTrade()"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-sky-600 px-4 py-2 text-xs font-semibold text-white hover:bg-sky-700 transition">
                        🧪 บันทึกคำสั่งนี้เข้าพอร์ตจำลอง (Virtual Buy)
                    </button>
                </div>
            </div>

            {{-- Tool 2 & 3: 3-Tranche Pyramid & Average Down (Col 3) --}}
            <div class="space-y-6">

                {{-- 3-Tranche Entry Planner --}}
                <div class="rounded-2xl border bg-white p-5 shadow-sm space-y-3">
                    <div class="border-b border-slate-100 pb-2">
                        <h3 class="text-sm font-bold text-slate-800">🔺 วางแผนแบ่งซื้อ 3 ไม้ (3-Step Pyramid)</h3>
                        <p class="text-[11px] text-slate-500">แบ่งเงินรับตามแนวรับเพื่อควบคุมต้นทุนเฉลี่ย</p>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">งบรวมสำหรับตัวนี้ (฿)</label>
                            <input type="number" step="1000" x-model.number="tranche.budget"
                                   class="block w-full rounded border border-slate-300 px-2 py-1 text-xs font-semibold">
                        </div>
                        <div class="grid grid-cols-3 gap-1.5">
                            <div>
                                <label class="block text-[9px] text-slate-500">ไม้ 1 (30%) ราคา</label>
                                <input type="number" step="0.01" x-model.number="tranche.p1" placeholder="แนวรับ 1"
                                       class="block w-full rounded border border-slate-300 px-1.5 py-1 text-xs">
                            </div>
                            <div>
                                <label class="block text-[9px] text-slate-500">ไม้ 2 (30%) ราคา</label>
                                <input type="number" step="0.01" x-model.number="tranche.p2" placeholder="Fib 61.8%"
                                       class="block w-full rounded border border-slate-300 px-1.5 py-1 text-xs">
                            </div>
                            <div>
                                <label class="block text-[9px] text-slate-500">ไม้ 3 (40%) ราคา</label>
                                <input type="number" step="0.01" x-model.number="tranche.p3" placeholder="แนวรับหลัก"
                                       class="block w-full rounded border border-slate-300 px-1.5 py-1 text-xs">
                            </div>
                        </div>

                        <div class="rounded-lg bg-indigo-50/60 p-3 text-[11px] text-indigo-900 space-y-1">
                            <div class="flex justify-between">
                                <span>จำนวนหุ้นรวมทั้ง 3 ไม้:</span>
                                <span class="font-bold" x-text="trancheOutput.totalShares.toLocaleString() + ' หุ้น'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>ต้นทุนเฉลี่ยเมื่อรับครบ:</span>
                                <span class="font-bold text-indigo-700" x-text="trancheOutput.avgCost.toFixed(2)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Average Down Calculator --}}
                <div class="rounded-2xl border bg-white p-5 shadow-sm space-y-3">
                    <div class="border-b border-slate-100 pb-2">
                        <h3 class="text-sm font-bold text-slate-800">📉 เครื่องคำนวณถัวเฉลี่ยต้นทุน (Average Down)</h3>
                        <p class="text-[11px] text-slate-500">คำนวณต้นทุนใหม่และ % เด้งเพื่อหลุดดอย</p>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] text-slate-600 mb-0.5">หุ้นเดิม (จำนวน)</label>
                                <input type="number" x-model.number="avgDown.oldShares"
                                       class="block w-full rounded border border-slate-300 px-2 py-1 text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-600 mb-0.5">ทุนเดิมเฉลี่ย (฿)</label>
                                <input type="number" step="0.01" x-model.number="avgDown.oldCost"
                                       class="block w-full rounded border border-slate-300 px-2 py-1 text-xs">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] text-slate-600 mb-0.5">ซื้อเพิ่ม (จำนวน)</label>
                                <input type="number" x-model.number="avgDown.newShares"
                                       class="block w-full rounded border border-slate-300 px-2 py-1 text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-600 mb-0.5">ราคาที่ซื้อถัว (฿)</label>
                                <input type="number" step="0.01" x-model.number="avgDown.newPrice"
                                       class="block w-full rounded border border-slate-300 px-2 py-1 text-xs">
                            </div>
                        </div>

                        <div class="rounded-lg bg-emerald-50/60 p-3 text-[11px] text-emerald-900 space-y-1">
                            <div class="flex justify-between">
                                <span>ต้นทุนเฉลี่ยใหม่:</span>
                                <span class="font-bold text-emerald-700" x-text="avgDownOutput.newAvgCost.toFixed(2) + ' ฿'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>ต้องรอเด้งจากราคาถัว:</span>
                                <span class="font-bold text-slate-800" x-text="'+' + avgDownOutput.reboundPct + '% เพื่อหลุดดอย'"></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Tool 4: DCA & ดอกเบี้ยทบต้นชนะเงินเฟ้อ (Core & Satellite DCA Planner) -- Full Width --}}
        <div class="dca-planner-card rounded-2xl border bg-white p-6 shadow-sm space-y-5 border-emerald-200">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📈</span>
                            <h2 class="text-base font-bold text-slate-900">เครื่องคำนวณสะสมหุ้น DCA & พลังดอกเบี้ยทบต้นชนะเงินเฟ้อ (Core & Satellite DCA Planner)</h2>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">วางแผนทยอยสะสมหุ้นทุกเดือนตามเงินสดที่ปลดหนี้ได้ เพื่อสร้างพอร์ตเติบโตและรับเงินปันผลต่อเนื่องระยะยาว</p>
                    </div>
                    <span class="rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-xs font-bold text-emerald-800">Compounding Wealth</span>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">งบสะสมต่อเดือน (฿)</label>
                        <input type="number" step="500" x-model.number="dca.monthly"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-bold text-slate-800">
                        <div class="flex items-center gap-1.5 mt-1.5 text-[10px] text-slate-500">
                            <span>ด่วน:</span>
                            <button type="button" @click="dca.monthly = 2000" class="dca-quick-btn rounded px-2 py-0.5 text-[10px] font-semibold transition cursor-pointer">฿2,000</button>
                            <button type="button" @click="dca.monthly = 4000" class="dca-quick-btn rounded px-2 py-0.5 text-[10px] font-semibold transition cursor-pointer">฿4,000</button>
                            <button type="button" @click="dca.monthly = 8900" class="dca-quick-btn rounded px-2 py-0.5 text-[10px] font-semibold transition cursor-pointer">฿8,900</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">สัดส่วน Core : Satellite</label>
                        <select x-model="dca.allocation" class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-800">
                            <option value="70:30">70% ชนะเงินเฟ้อ : 30% เกาะกระแส (สมดุล แนะนำ)</option>
                            <option value="60:40">60% ชนะเงินเฟ้อ : 40% เกาะกระแส (เน้นเติบโต)</option>
                            <option value="80:20">80% ชนะเงินเฟ้อ : 20% เกาะกระแส (เน้นปลอดภัย)</option>
                            <option value="100:0">100% ชนะเงินเฟ้อล้วน (Defensive)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">ผลตอบแทนเฉลี่ยต่อปี (%)</label>
                        <input type="number" step="0.5" x-model.number="dca.expectedReturnPct"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-800">
                        <span class="block text-[10px] text-slate-500 mt-1">ปกติเฉลี่ย 8% – 12% ต่อปี</span>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">เงินปันผลเฉลี่ยต่อปี (%)</label>
                        <input type="number" step="0.1" x-model.number="dca.dividendYieldPct"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-800">
                        <span class="block text-[10px] text-slate-500 mt-1">เงินปันผลต่อปีที่จะนำมาทบต้น</span>
                    </div>
                </div>

                {{-- DCA Output Projections --}}
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3 pt-2">
                    <template x-for="p in dcaProjections" :key="p.years">
                        <div class="dca-projection-card rounded-xl border p-4 shadow-sm space-y-2.5">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <span class="font-bold text-slate-900 text-sm" x-text="p.years + ' ปี (' + (p.years * 12) + ' งวด)'"></span>
                                <span class="rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700" x-text="'+' + p.gainPct + '%'"></span>
                            </div>
                            <div class="space-y-1.5 text-xs">
                                <div class="flex items-center justify-between gap-2 text-slate-500">
                                    <span class="whitespace-nowrap">เงินต้นสะสม:</span>
                                    <span class="font-semibold text-slate-700 whitespace-nowrap" x-text="'฿' + p.totalInvested.toLocaleString()"></span>
                                </div>
                                <div class="flex items-center justify-between gap-2 py-1 border-y border-dashed border-slate-200">
                                    <span class="font-bold text-slate-800 whitespace-nowrap">มูลค่าพอร์ตทบต้น:</span>
                                    <span class="font-extrabold text-emerald-600 text-sm whitespace-nowrap" x-text="'฿' + Math.round(p.futureValue).toLocaleString()"></span>
                                </div>
                                <div class="flex items-center justify-between gap-2 text-[11px] text-slate-500">
                                    <span class="whitespace-nowrap">กำไรทบต้น:</span>
                                    <span class="font-semibold text-emerald-700 whitespace-nowrap" x-text="'+฿' + Math.round(p.compoundedGain).toLocaleString()"></span>
                                </div>
                                <div class="border-t border-slate-100 pt-1.5 flex items-center justify-between gap-2 text-[11px]">
                                    <span class="text-amber-700 font-medium whitespace-nowrap">ปันผลต่อปี (Passive):</span>
                                    <span class="font-bold text-amber-800 whitespace-nowrap" x-text="'฿' + Math.round(p.annualDividend).toLocaleString() + ' / ปี'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    {{-- ================================================================= --}}
    {{-- TAB 3: 🧪 PAPER TRADING SANDBOX (พอร์ตจำลอง บันทึกลงฐานข้อมูลจริง) --}}
    {{-- ================================================================= --}}
    <div x-show="activeTab === 'paper'" class="space-y-6" x-cloak>

        {{-- Paper Summary Tiles --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">เงินสดจำลองคงเหลือ</p>
                    <button type="button" @click="resetPaperModal = true" class="text-[10px] text-brand-600 hover:text-brand-700 font-semibold">รีเซ็ตงบ</button>
                </div>
                <p class="mt-2 text-2xl font-bold text-slate-900">
                    ฿{{ number_format($paperCash, 2) }}
                </p>
                <p class="mt-1 text-[11px] text-slate-500">งบตั้งต้น ฿{{ number_format((float) $paperAccount->initial_balance, 2) }}</p>
            </div>

            <div class="rounded-2xl border bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">มูลค่าหุ้นจำลองที่ถืออยู่</p>
                <p class="mt-2 text-2xl font-bold text-sky-600">
                    ฿{{ number_format($paperHoldingsValue, 2) }}
                </p>
                <p class="mt-1 text-[11px] text-slate-500">ถืออยู่ {{ $openPaperTrades->count() }} ออเดอร์</p>
            </div>

            <div class="rounded-2xl border bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">มูลค่าพอร์ตจำลองรวม (Net Worth)</p>
                <p class="mt-2 text-2xl font-bold text-indigo-700">
                    ฿{{ number_format($paperNetWorth, 2) }}
                </p>
                <p class="mt-1 text-[11px] text-slate-500">เงินสด + มูลค่าหุ้นตลาดสด</p>
            </div>

            <div class="rounded-2xl border bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">กำไร/ขาดทุนสะสม (P&L)</p>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $paperWinRate >= 50 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                        Win Rate {{ $paperWinRate }}% ({{ $closedCount }} ไม้)
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold {{ $paperTotalPnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $paperTotalPnl >= 0 ? '+' : '' }}฿{{ number_format($paperTotalPnl, 2) }}
                </p>
                <p class="mt-1 text-[11px] text-slate-500">
                    Unrealized: {{ $paperUnrealized >= 0 ? '+' : '' }}฿{{ number_format($paperUnrealized, 2) }} · Realized: {{ $paperRealized >= 0 ? '+' : '' }}฿{{ number_format($paperRealized, 2) }}
                </p>
            </div>
        </div>

        {{-- Action Bar: New Virtual Trade Form (Collapsible) --}}
        <div class="rounded-2xl border bg-white shadow-sm" x-data="{ openBuyModal: false }">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">🧪 ออเดอร์พอร์ตจำลองที่เปิดอยู่ (Active Virtual Positions)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">ทดสอบซื้อขายด้วยราคาตลาดจริงจาก Yahoo Finance บันทึกกำไรขาดทุนจริง</p>
                </div>
                <button type="button" @click="openBuyModal = !openBuyModal"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700 transition">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    + เปิดออเดอร์ซื้อจำลอง
                </button>
            </div>

            {{-- Open Order Form --}}
            <div x-show="openBuyModal" x-cloak class="border-b border-slate-100 bg-slate-50/60 p-5">
                <form method="POST" action="{{ route('portfolio.ai.paper-trade.store') }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">ชื่อหุ้น (Ticker) *</label>
                            <input type="text" name="symbol" required placeholder="เช่น NVDA หรือ ADVANC.BK"
                                   class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-bold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">จำนวนหุ้น (Quantity) *</label>
                            <input type="number" step="0.0001" min="0.0001" name="quantity" required placeholder="100"
                                   class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">ราคาเข้าซื้อ (เว้นว่าง = ใช้ราคาตลาดสด)</label>
                            <input type="number" step="0.0001" min="0" name="entry_price" placeholder="ราคาตลาดสด"
                                   class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-emerald-600 mb-1">เป้าขายทำกำไร (Target Price)</label>
                            <input type="number" step="0.0001" min="0" name="target_price" placeholder="ไม่บังคับ"
                                   class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-rose-600 mb-1">จุดตัดขาดทุน (Stop Loss)</label>
                            <input type="number" step="0.0001" min="0" name="stop_loss" placeholder="ไม่บังคับ"
                                   class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">เหตุผลที่เข้าซื้อ (Trade Thesis / Setup)</label>
                            <input type="text" name="trade_thesis" placeholder="เช่น ย่อทดสอบแนวรับ SMA50 พร้อม RSI Oversold คาดหวัง Rebound"
                                   class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="openBuyModal = false"
                                class="rounded-lg bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-300">ยกเลิก</button>
                        <button type="submit"
                                class="rounded-lg bg-brand-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">ส่งคำสั่งซื้อจำลอง</button>
                    </div>
                </form>
            </div>

            {{-- Open Positions Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50/60 text-slate-500 text-xs uppercase font-semibold">
                            <th class="px-5 py-3 text-left">หุ้น / สินทรัพย์</th>
                            <th class="px-3 py-3 text-right">จำนวน</th>
                            <th class="px-3 py-3 text-right">ราคาทุน</th>
                            <th class="px-3 py-3 text-right">ราคาตลาดสด</th>
                            <th class="px-3 py-3 text-right">มูลค่ารวม (฿)</th>
                            <th class="px-3 py-3 text-right">กำไร/ขาดทุน (P&L)</th>
                            <th class="px-3 py-3 text-center">Target / Stop Loss</th>
                            <th class="px-5 py-3 text-right">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($openPaperTrades as $trade)
                        @php
                            $unrealizedThb = $trade->unrealizedPnlThb();
                            $unrealizedPct = $trade->unrealizedPnlPct();
                            $targetProg    = $trade->targetProgressPct();
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900">{{ $trade->symbol }}</span>
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">{{ $trade->currency }}</span>
                                </div>
                                @if($trade->trade_thesis)
                                    <p class="text-[11px] text-slate-400 mt-0.5 truncate max-w-xs">{{ $trade->trade_thesis }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3.5 text-right font-medium text-slate-800">{{ number_format((float) $trade->quantity, 2) }}</td>
                            <td class="px-3 py-3.5 text-right font-medium text-slate-700">{{ number_format((float) $trade->entry_price, 2) }}</td>
                            <td class="px-3 py-3.5 text-right font-bold text-slate-900">
                                {{ $trade->current_price ? number_format((float) $trade->current_price, 2) : '—' }}
                            </td>
                            <td class="px-3 py-3.5 text-right font-semibold text-slate-900">
                                ฿{{ number_format((float) ($trade->current_value_thb ?? $trade->entry_total_thb), 2) }}
                            </td>
                            <td class="px-3 py-3.5 text-right">
                                <span class="font-bold {{ $unrealizedThb >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $unrealizedThb >= 0 ? '+' : '' }}฿{{ number_format($unrealizedThb, 2) }}
                                </span>
                                <span class="block text-[11px] font-semibold {{ $unrealizedPct >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    ({{ $unrealizedPct >= 0 ? '+' : '' }}{{ number_format($unrealizedPct, 2) }}%)
                                </span>
                            </td>
                            <td class="px-3 py-3.5 text-center text-xs">
                                @if($trade->target_price || $trade->stop_loss)
                                    <div class="inline-flex flex-col items-center gap-0.5">
                                        @if($trade->target_price)
                                            <span class="text-emerald-600 font-semibold text-[11px]">TP: {{ number_format((float) $trade->target_price, 2) }}</span>
                                        @endif
                                        @if($trade->stop_loss)
                                            <span class="text-rose-600 font-semibold text-[11px]">SL: {{ number_format((float) $trade->stop_loss, 2) }}</span>
                                        @endif
                                        @if($targetProg !== null)
                                            <span class="text-[9px] text-slate-400">เป้าหมาย {{ $targetProg }}%</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- Close trade modal trigger --}}
                                    <form method="POST" action="{{ route('portfolio.ai.paper-trade.close', $trade) }}"
                                          data-confirm="ยืนยันปิดออเดอร์จำลอง '{{ $trade->symbol }}' ณ ราคาปัจจุบัน?">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition" title="ขายทำกำไร/ตัดขาดทุน">
                                            ปิดออเดอร์
                                        </button>
                                    </form>

                                    {{-- Convert to real holding --}}
                                    <form method="POST" action="{{ route('portfolio.ai.paper-trade.convert', $trade) }}"
                                          data-confirm="โอน '{{ $trade->symbol }}' เข้าสู่พอร์ตจริงของคุณ?">
                                        @csrf
                                        <button type="submit" class="rounded-lg border border-brand-200 bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-100 transition" title="โอนเข้าพอร์ตจริง">
                                            🚀 เข้าพอร์ตจริง
                                        </button>
                                    </form>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('portfolio.ai.paper-trade.destroy', $trade) }}"
                                          data-confirm="ยกเลิกออเดอร์ '{{ $trade->symbol }}' และคืนเงินสดจำลอง?" data-confirm-danger="1">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1 rounded text-rose-400 hover:text-rose-600 transition" title="ลบ">
                                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-slate-400">
                                <p class="text-sm">ยังไม่มีออเดอร์จำลองที่เปิดอยู่</p>
                                <p class="mt-1 text-xs">กดปุ่ม "+ เปิดออเดอร์ซื้อจำลอง" ด้านบน หรือกด "🧪 ซื้อจำลอง" จากผลการวิเคราะห์ของ AI</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Closed Trades History --}}
        @if($closedPaperTrades->isNotEmpty())
        <div class="rounded-2xl border bg-white p-5 shadow-sm space-y-3" x-data="{ showHistory: false }">
            <div class="flex items-center justify-between cursor-pointer" @click="showHistory = !showHistory">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold text-slate-800">📜 ประวัติออเดอร์ที่ปิดแล้ว (Closed Trades History: {{ $closedPaperTrades->count() }} ไม้)</h3>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                        Realized Total: {{ $paperRealized >= 0 ? '+' : '' }}฿{{ number_format($paperRealized, 2) }}
                    </span>
                </div>
                <button type="button" class="text-xs text-brand-600 font-semibold" x-text="showHistory ? 'ซ่อน ▴' : 'แสดงประวัติ ▾'"></button>
            </div>

            <div x-show="showHistory" x-cloak class="overflow-x-auto pt-2">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-slate-500 border-b pb-1 text-left font-semibold">
                            <th class="py-2">หุ้น</th>
                            <th class="py-2 text-right">จำนวน</th>
                            <th class="py-2 text-right">ทุน</th>
                            <th class="py-2 text-right">ราคาขาย</th>
                            <th class="py-2 text-right">กำไร/ขาดทุนสุทธิ</th>
                            <th class="py-2 text-right">วันที่ปิด</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($closedPaperTrades as $closed)
                        <tr>
                            <td class="py-2 font-bold text-slate-800">{{ $closed->symbol }}</td>
                            <td class="py-2 text-right text-slate-600">{{ number_format((float) $closed->quantity, 2) }}</td>
                            <td class="py-2 text-right text-slate-600">{{ number_format((float) $closed->entry_price, 2) }}</td>
                            <td class="py-2 text-right font-medium text-slate-800">{{ number_format((float) $closed->exit_price, 2) }}</td>
                            <td class="py-2 text-right font-bold {{ (float) $closed->realized_pnl_thb >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ (float) $closed->realized_pnl_thb >= 0 ? '+' : '' }}฿{{ number_format((float) $closed->realized_pnl_thb, 2) }} ({{ (float) $closed->realized_pnl_pct >= 0 ? '+' : '' }}{{ number_format((float) $closed->realized_pnl_pct, 2) }}%)
                            </td>
                            <td class="py-2 text-right text-slate-400">{{ $closed->closed_at ? $closed->closed_at->format('d/m/y H:i') : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Reset Paper Account Modal --}}
        <div x-show="resetPaperModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4" @click.outside="resetPaperModal = false">
                <h3 class="text-base font-bold text-slate-900">รีเซ็ตพอร์ตจำลอง (Reset Paper Account)</h3>
                <p class="text-xs text-slate-500">ตั้งจำนวนเงินสดเริ่มต้นใหม่และเลือกว่าจะล้างประวัติการเทรดจำลองทั้งหมดหรือไม่</p>
                <form method="POST" action="{{ route('portfolio.ai.paper-account.reset') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">เงินสดตั้งต้นใหม่ (฿)</label>
                        <input type="number" step="1000" min="1000" name="initial_balance" value="100000" required
                               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold">
                    </div>
                    <label class="flex items-center gap-2 text-xs text-slate-700">
                        <input type="checkbox" name="clear_trades" value="1" checked class="rounded border-slate-300 text-brand-600">
                        <span>ลบออเดอร์และประวัติการเทรดจำลองเดิมทั้งหมด</span>
                    </label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="resetPaperModal = false"
                                class="rounded-lg bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700">ยกเลิก</button>
                        <button type="submit"
                                class="rounded-lg bg-rose-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">ยืนยันรีเซ็ต</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    {{-- ================================================================= --}}
    {{-- TAB 4: 💼 REAL PORTFOLIO GUARD & STOP LOSS MONITOR                --}}
    {{-- ================================================================= --}}
    <div x-show="activeTab === 'real'" class="space-y-6" x-cloak>

        {{-- Real Portfolio Summary Tiles --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">มูลค่าพอร์ตลงทุนจริง</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">
                    ฿{{ number_format($totalInvestedThb, 2) }}
                </p>
                <p class="mt-1 text-[11px] text-slate-500">รวมหุ้น, กองทุน และคริปโต</p>
            </div>

            <div class="rounded-2xl border bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">ต้นทุนรวม (Cost Basis)</p>
                <p class="mt-2 text-2xl font-bold text-slate-700">
                    ฿{{ number_format($totalCostBasis, 2) }}
                </p>
                <p class="mt-1 text-[11px] text-slate-500">จากประวัติการซื้อ</p>
            </div>

            <div class="rounded-2xl border bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">กำไร/ขาดทุนรวม (Unrealized)</p>
                <p class="mt-2 text-2xl font-bold {{ $totalRealPnlThb >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $totalRealPnlThb >= 0 ? '+' : '' }}฿{{ number_format($totalRealPnlThb, 2) }}
                </p>
                <p class="mt-1 text-[11px] font-semibold {{ $totalRealPnlPct >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $totalRealPnlPct >= 0 ? '+' : '' }}{{ number_format($totalRealPnlPct, 2) }}%
                </p>
            </div>

            <div class="rounded-2xl border bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">เครื่องมือตรวจพอร์ต</p>
                <button type="button" @click="sendFullPortfolioToAi()"
                        class="mt-2 inline-flex items-center gap-1.5 rounded-xl bg-brand-50 border border-brand-200 px-3 py-1.5 text-xs font-bold text-brand-700 hover:bg-brand-100 transition w-full justify-center">
                    🤖 ส่งพอร์ตนี้ให้ AI วิเคราะห์ความเสี่ยง
                </button>
                <p class="mt-1 text-[10px] text-slate-400 text-center">Prompt #6 (Concentration & Risk)</p>
            </div>
        </div>

        {{-- Real Holdings Guard Table --}}
        <div class="rounded-2xl border bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">🛡️ สินทรัพย์ในพอร์ตจริง & จุดเฝ้าระวัง (Real Portfolio Guard)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">ตั้งเป้าหมายทำกำไร (Target) และจุดตัดขาดทุน (Stop Loss) สำหรับหุ้นจริงแต่ละตัว</p>
                </div>
                <a href="{{ route('portfolio.dashboard') }}" class="text-xs text-brand-600 hover:text-brand-700 font-semibold">
                    จัดการพอร์ตหลัก →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50/60 text-slate-500 text-xs uppercase font-semibold">
                            <th class="px-5 py-3 text-left">สินทรัพย์ / Ticker</th>
                            <th class="px-3 py-3 text-right">จำนวน</th>
                            <th class="px-3 py-3 text-right">ราคาตลาดสด</th>
                            <th class="px-3 py-3 text-right">มูลค่า (฿)</th>
                            <th class="px-3 py-3 text-right">สัดส่วนพอร์ต</th>
                            <th class="px-3 py-3 text-center">เป้าขาย (Target)</th>
                            <th class="px-3 py-3 text-center">จุดหนี (Stop Loss)</th>
                            <th class="px-5 py-3 text-right">ตั้งค่า</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($holdings as $h)
                        @php
                            $meta = (array) ($h->metadata ?? []);
                            $tp   = $meta['target_price'] ?? null;
                            $sl   = $meta['stop_loss'] ?? null;
                            $weight = $totalInvestedThb > 0 ? round(((float) $h->current_value_thb / $totalInvestedThb) * 100, 1) : 0;
                            $pnl = (float) $h->current_value_thb - (float) $h->cost_basis;
                            $curPrice = (float) ($h->current_price ?? 0);
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition" x-data="{ editGuard: false, tpVal: '{{ $tp }}', slVal: '{{ $sl }}', notesVal: '{{ $meta['strategy_notes'] ?? '' }}' }">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900">{{ $h->symbol ?: $h->label }}</span>
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">{{ $h->kind }}</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5 truncate max-w-xs">{{ $h->label }}</p>
                                @if(!empty($meta['strategy_notes']))
                                    <p class="text-[10px] text-indigo-600 mt-0.5">📌 {{ $meta['strategy_notes'] }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3.5 text-right font-medium text-slate-800">{{ number_format((float) $h->quantity, 2) }}</td>
                            <td class="px-3 py-3.5 text-right font-bold text-slate-900">
                                {{ $curPrice > 0 ? number_format($curPrice, 2) . ' ' . $h->currency : '—' }}
                            </td>
                            <td class="px-3 py-3.5 text-right font-semibold text-slate-900">
                                ฿{{ number_format((float) $h->current_value_thb, 2) }}
                                <span class="block text-[11px] {{ $pnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $pnl >= 0 ? '+' : '' }}฿{{ number_format($pnl, 2) }}
                                </span>
                            </td>
                            <td class="px-3 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <div class="h-1.5 w-12 rounded-full bg-slate-200 overflow-hidden hidden sm:block">
                                        <div class="h-1.5 rounded-full bg-brand-500" style="width: {{ min(100, $weight) }}%"></div>
                                    </div>
                                    <span class="font-semibold text-slate-800 text-xs">{{ $weight }}%</span>
                                </div>
                            </td>
                            <td class="px-3 py-3.5 text-center text-xs">
                                @if($tp)
                                    <span class="font-bold text-emerald-700">฿{{ number_format((float) $tp, 2) }}</span>
                                    @if($curPrice > 0 && (float) $tp > $curPrice)
                                        <span class="block text-[10px] text-emerald-600">(+{{ round((( (float) $tp - $curPrice) / $curPrice) * 100, 1) }}%)</span>
                                    @endif
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3.5 text-center text-xs">
                                @if($sl)
                                    <span class="font-bold text-rose-700">฿{{ number_format((float) $sl, 2) }}</span>
                                    @if($curPrice > 0 && $curPrice > (float) $sl)
                                        <span class="block text-[10px] text-rose-600">(-{{ round((( $curPrice - (float) $sl) / $curPrice) * 100, 1) }}%)</span>
                                    @endif
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" @click="editGuard = !editGuard"
                                            class="rounded border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                        ตั้งค่าเป้า/Stop
                                    </button>
                                    @if($h->symbol)
                                    <button type="button" @click="analyzeSingleHolding('{{ $h->symbol }}')"
                                            class="rounded border border-indigo-200 bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition"
                                            title="วิเคราะห์ด้วย AI">
                                        วิเคราะห์
                                    </button>
                                    @endif
                                </div>

                                {{-- Inline edit modal/form --}}
                                <div x-show="editGuard" x-cloak class="mt-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-left space-y-2">
                                    <form method="POST" action="{{ route('portfolio.ai.holding-guard.update', $h) }}" class="space-y-2">
                                        @csrf @method('PATCH')
                                        <div class="grid grid-cols-2 gap-2 text-xs">
                                            <div>
                                                <label class="block text-[10px] text-emerald-700 font-semibold mb-0.5">เป้าขาย Target (฿)</label>
                                                <input type="number" step="0.01" name="target_price" x-model="tpVal" placeholder="0.00"
                                                       class="block w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] text-rose-700 font-semibold mb-0.5">จุดตัดขาดทุน Stop Loss (฿)</label>
                                                <input type="number" step="0.01" name="stop_loss" x-model="slVal" placeholder="0.00"
                                                       class="block w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] text-slate-600 font-semibold mb-0.5">กลยุทธ์ / บันทึกย่อ</label>
                                            <input type="text" name="strategy_notes" x-model="notesVal" placeholder="เช่น ถือรับปันผล / รอขายที่แนวต้าน 2"
                                                   class="block w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                        </div>
                                        <div class="flex justify-end gap-1.5 pt-1">
                                            <button type="button" @click="editGuard = false" class="rounded px-2 py-0.5 text-xs bg-slate-200 text-slate-700">ยกเลิก</button>
                                            <button type="submit" class="rounded bg-brand-600 px-2.5 py-0.5 text-xs font-semibold text-white">บันทึก</button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-slate-400">
                                ยังไม่มีรายการหุ้น/กองทุนในพอร์ตหลัก
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- ================================================================= --}}
    {{-- TAB 5: ⚖️ VALUATION, REBALANCE & CASHFLOW BRIDGE                 --}}
    {{-- ================================================================= --}}
    <div x-show="activeTab === 'valuation'" class="space-y-6" x-cloak>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- 1. Fair Value & Graham Number Estimator --}}
            <div class="rounded-2xl border bg-white p-6 shadow-sm space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-base font-bold text-slate-900">⚖️ เครื่องประเมินมูลค่าหุ้น (Fair Value & Graham Number)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">คำนวณราคาเหมาะสมตาม P/E เป้าหมาย และสูตรคลาสสิก Benjamin Graham</p>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">กำไรต่อหุ้น (EPS) ฿</label>
                        <input type="number" step="0.01" x-model.number="valuation.eps" placeholder="เช่น 5.20"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 font-bold text-slate-800">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">มูลค่าทางบัญชี (BVPS) ฿</label>
                        <input type="number" step="0.01" x-model.number="valuation.bvps" placeholder="เช่น 32.50"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 font-bold text-slate-800">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Target P/E เหมาะสม (เท่า)</label>
                        <input type="number" step="0.5" x-model.number="valuation.targetPe" placeholder="15"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-800">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">ราคาตลาดปัจจุบัน (฿)</label>
                        <input type="number" step="0.01" x-model.number="valuation.marketPrice" placeholder="เช่น 65.00"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-800">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">เงินปันผลจ่ายต่อหุ้น (DPS) ฿</label>
                        <input type="number" step="0.01" x-model.number="valuation.dps" placeholder="เช่น 3.50"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-800">
                    </div>
                </div>

                {{-- Valuation Results --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2 text-xs">
                    <div class="flex justify-between items-center py-1 border-b border-slate-200">
                        <span class="text-slate-600">ราคาเหมาะสมตาม Target P/E:</span>
                        <span class="text-base font-bold text-brand-600" x-text="valuationOutput.peFairPrice.toFixed(2) + ' ฿'"></span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-200">
                        <span class="text-slate-600">Benjamin Graham Number (√22.5×EPS×BVPS):</span>
                        <span class="text-base font-bold text-indigo-700" x-text="valuationOutput.grahamPrice.toFixed(2) + ' ฿'"></span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-200">
                        <span class="text-slate-600">Margin of Safety (ส่วนลดจากราคาตลาด):</span>
                        <span class="font-bold" :class="valuationOutput.mosPct >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                              x-text="(valuationOutput.mosPct >= 0 ? '+' : '') + valuationOutput.mosPct.toFixed(1) + '%'"></span>
                    </div>
                    <div class="flex justify-between items-center py-1">
                        <span class="text-slate-600">อัตราปันผลสุทธิหลังหักภาษี 10%:</span>
                        <span class="font-bold text-emerald-700" x-text="valuationOutput.netDivYield.toFixed(2) + '% ต่อปี'"></span>
                    </div>
                </div>
            </div>

            {{-- 2. Cash Flow to Investment Bridge (Connected with Nov 2026 Debt Plan) --}}
            <div class="rounded-2xl border bg-white p-6 shadow-sm space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-slate-900">💸 ไทม์ไลน์ปลดหนี้สู่เม็ดเงินลงทุน (Cashflow Bridge)</h2>
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">อ้างอิงแผน พ.ย. 69</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">กระแสเงินสดจริงที่จะปลดล็อกในแต่ละงวด สำหรับนำมาแบ่งไม้ลงทุน</p>
                </div>

                <div class="space-y-3">
                    @foreach($debtMilestones as $m)
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3.5 flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-800 text-xs">{{ $m['period'] }}</span>
                                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-700">{{ $m['badge'] }}</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1">{{ $m['status'] }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-base font-bold text-emerald-600">+฿{{ number_format($m['unlocked_thb'], 0) }}</span>
                            <span class="block text-[10px] text-slate-400">เงินลงทุน/เดือน</span>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="rounded-xl border border-indigo-200 bg-indigo-50/50 p-3 text-xs text-indigo-900">
                    💡 **คำแนะนำ:** ช่วง พ.ย. 69 ให้เน้นสะสมเงินสำรองฉุกเฉินและซ้อมมือใน **พอร์ตจำลอง** เมื่อเข้าสู่ พ.ค. 70 ใบแจ้งยอดล่วงหน้าและ SPayLater จบลง จะมีเงินสดอิสระสูงถึง **฿8,977/เดือน** เพื่อเริ่มทยอยซื้อหุ้นจริงตามกลยุทธ์ 3 ไม้ได้อย่างปลอดภัย
                </div>
            </div>

        </div>
    </div>

</div>

<script src="https://s3.tradingview.com/tv.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('stockAiWorkspace', () => ({
        activeTab: ['prompts', 'sniper', 'paper', 'real', 'valuation'].includes(window.location.hash.substring(1)) ? window.location.hash.substring(1) : 'prompts',
        prompts: @json($prompts),
        activePromptId: 1,
        fieldValues: {},
        focusedFieldKey: null,
        customPromptText: '',
        chartTicker: 'DELTA.BK',
        chartInterval: 'D',
        coreTickers: ['CPALL.BK', 'BDMS.BK', 'ADVANC.BK', 'AOT.BK', 'MSFT', 'SCHD', 'KO'],
        trendTickers: ['NVDA', 'TSLA', 'DELTA.BK', 'GULF.BK', 'QQQ', 'SMH'],
        popularTickers: ['NVDA', 'AAPL', 'TSLA', 'PTT.BK', 'ADVANC.BK', 'DELTA.BK', 'SCB.BK', 'BDMS.BK', 'CPALL.BK', 'GULF.BK', 'BTC-USD'],
        loading: false,
        apiKeyModal: false,
        resetPaperModal: false,
        copiedPrompt: false,
        copiedAnalysis: false,
        marketData: [],
        analysisText: '',
        modelLabel: '',
        lastGeneratedAt: '',
        liveNews: [],
        sentimentData: null,
        newsTicker: 'DELTA.BK',
        loadingNews: false,

        // Sniper Calculator state
        sniper: {
            symbol: 'NVDA',
            capital: {{ $totalInvestedThb > 0 ? $totalInvestedThb : 100000 }},
            riskPct: 2,
            entry: 135.0,
            stopLoss: 125.0,
            target: 160.0
        },

        // Tranche Planner state
        tranche: {
            budget: 50000,
            p1: 135.0,
            p2: 130.0,
            p3: 125.0
        },

        // Average Down state
        avgDown: {
            oldShares: 100,
            oldCost: 150.0,
            newShares: 100,
            newPrice: 120.0
        },

        // Valuation state
        valuation: {
            eps: 5.5,
            bvps: 35.0,
            targetPe: 16.0,
            marketPrice: 70.0,
            dps: 3.5
        },

        // DCA & Compounding state
        dca: {
            monthly: 2000,
            allocation: '70:30',
            expectedReturnPct: 10.0,
            dividendYieldPct: 3.5
        },

        get activePrompt() {
            return this.prompts.find(p => p.id === this.activePromptId) || this.prompts[0];
        },

        get sniperOutput() {
            const cap = parseFloat(this.sniper.capital) || 0;
            const rPct = parseFloat(this.sniper.riskPct) || 2;
            const entry = parseFloat(this.sniper.entry) || 0;
            const sl = parseFloat(this.sniper.stopLoss) || 0;
            const tp = parseFloat(this.sniper.target) || 0;

            const maxRiskMoney = (cap * rPct) / 100;
            const riskPerShare = Math.max(0.01, Math.abs(entry - sl));
            const rewardPerShare = Math.max(0.0, tp - entry);

            let shares = 0;
            if (riskPerShare > 0) {
                shares = Math.floor(maxRiskMoney / riskPerShare);
            }
            const totalCost = shares * entry;
            const expectedProfit = shares * rewardPerShare;
            const rrRatio = riskPerShare > 0 ? (rewardPerShare / riskPerShare).toFixed(2) : 0;
            const breakevenPrice = entry > 0 ? (entry * 1.0033).toFixed(2) : 0;

            return {
                shares,
                totalCost,
                maxRiskMoney,
                expectedProfit,
                rrRatio,
                breakevenPrice
            };
        },

        get trancheOutput() {
            const budget = parseFloat(this.tranche.budget) || 0;
            const p1 = parseFloat(this.tranche.p1) || 1;
            const p2 = parseFloat(this.tranche.p2) || 1;
            const p3 = parseFloat(this.tranche.p3) || 1;

            const b1 = budget * 0.30;
            const b2 = budget * 0.30;
            const b3 = budget * 0.40;

            const s1 = p1 > 0 ? Math.floor(b1 / p1) : 0;
            const s2 = p2 > 0 ? Math.floor(b2 / p2) : 0;
            const s3 = p3 > 0 ? Math.floor(b3 / p3) : 0;
            const totalShares = s1 + s2 + s3;

            const totalSpent = (s1 * p1) + (s2 * p2) + (s3 * p3);
            const avgCost = totalShares > 0 ? (totalSpent / totalShares) : 0;

            return { totalShares, avgCost };
        },

        get avgDownOutput() {
            const os = parseFloat(this.avgDown.oldShares) || 0;
            const oc = parseFloat(this.avgDown.oldCost) || 0;
            const ns = parseFloat(this.avgDown.newShares) || 0;
            const np = parseFloat(this.avgDown.newPrice) || 0;

            const totalShares = os + ns;
            const totalCost = (os * oc) + (ns * np);
            const newAvgCost = totalShares > 0 ? totalCost / totalShares : 0;
            const reboundPct = np > 0 && newAvgCost > np ? (((newAvgCost - np) / np) * 100).toFixed(1) : 0;

            return { newAvgCost, reboundPct };
        },

        get valuationOutput() {
            const eps = parseFloat(this.valuation.eps) || 0;
            const bvps = parseFloat(this.valuation.bvps) || 0;
            const pe = parseFloat(this.valuation.targetPe) || 15;
            const mp = parseFloat(this.valuation.marketPrice) || 1;
            const dps = parseFloat(this.valuation.dps) || 0;

            const peFairPrice = eps * pe;
            const grahamPrice = (eps > 0 && bvps > 0) ? Math.sqrt(22.5 * eps * bvps) : 0;
            const mosPct = mp > 0 ? (((peFairPrice - mp) / mp) * 100) : 0;
            const netDivYield = mp > 0 ? ((dps * 0.90 / mp) * 100) : 0;

            return { peFairPrice, grahamPrice, mosPct, netDivYield };
        },

        get dcaProjections() {
            const m = parseFloat(this.dca.monthly) || 0;
            const rAnnual = (parseFloat(this.dca.expectedReturnPct) || 10) / 100;
            const divAnnual = (parseFloat(this.dca.dividendYieldPct) || 3.5) / 100;
            const rMonthly = rAnnual / 12;

            const periods = [3, 5, 10];
            return periods.map(y => {
                const n = y * 12;
                const totalInvested = m * n;
                let futureValue = 0;
                if (rMonthly > 0) {
                    futureValue = m * ((Math.pow(1 + rMonthly, n) - 1) / rMonthly);
                } else {
                    futureValue = totalInvested;
                }
                const compoundedGain = Math.max(0, futureValue - totalInvested);
                const gainPct = totalInvested > 0 ? ((compoundedGain / totalInvested) * 100).toFixed(1) : 0;
                const annualDividend = futureValue * divAnnual;

                return {
                    years: y,
                    totalInvested,
                    futureValue,
                    compoundedGain,
                    gainPct,
                    annualDividend
                };
            });
        },

        init() {
            window.addEventListener('hashchange', () => {
                let h = window.location.hash.substring(1);
                if (['prompts', 'sniper', 'paper', 'real', 'valuation'].includes(h)) {
                    this.activeTab = h;
                }
            });
            this.selectPrompt(1);
            this.$nextTick(() => {
                setTimeout(() => {
                    this.renderTvChart(this.chartTicker);
                }, 400);
            });
        },

        toTradingViewSymbol(symbol) {
            if (!symbol) return 'SET:DELTA';
            let s = symbol.trim().toUpperCase();
            if (s.endsWith('.BK')) {
                return 'SET:' + s.replace('.BK', '');
            }
            const thaiList = [
                'PTT', 'PTTEP', 'PTTGC', 'TOP', 'OR', 'ADVANC', 'INTUCH', 'TRUE', 'DELTA', 'HANA',
                'KCE', 'SCB', 'KBANK', 'BBL', 'KTB', 'TTB', 'TISCO', 'KKP', 'CPALL', 'CPAXT',
                'CRC', 'HMPRO', 'GLOBAL', 'BDMS', 'BH', 'BCH', 'CHG', 'AOT', 'BEM', 'BTS',
                'MINT', 'CENTEL', 'AWC', 'CPN', 'LH', 'AP', 'SPALI', 'SIRI', 'ORI', 'SC',
                'GULF', 'GPSC', 'BGRIM', 'EA', 'RATCH', 'EGCO', 'BANPU', 'SCC', 'SCGP', 'IVL'
            ];
            if (thaiList.includes(s)) {
                return 'SET:' + s;
            }
            if (s === 'BTC-USD' || s === 'BTC') {
                return 'BINANCE:BTCUSDT';
            }
            if (s === 'ETH-USD' || s === 'ETH') {
                return 'BINANCE:ETHUSDT';
            }
            return s;
        },

        openChart(symbol) {
            let s = (symbol || this.chartTicker || 'DELTA.BK').trim();
            this.chartTicker = s;
            this.activeTab = 'prompts';
            window.location.hash = 'prompts';
            this.renderTvChart(s);
            this.$nextTick(() => {
                const el = document.getElementById('chart-section');
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        },

        changeChartInterval(interval) {
            this.chartInterval = interval;
            this.renderTvChart(this.chartTicker);
        },

        renderTvChart(symbol) {
            const sym = symbol || this.chartTicker || 'DELTA.BK';
            this.chartTicker = sym;
            const tvSym = this.toTradingViewSymbol(sym);
            const container = document.getElementById('tradingview_chart_container');
            if (!container) return;
            container.innerHTML = '';
            if (typeof TradingView !== 'undefined') {
                new TradingView.widget({
                    "autosize": true,
                    "symbol": tvSym,
                    "interval": this.chartInterval || "D",
                    "timezone": "Asia/Bangkok",
                    "theme": "light",
                    "style": "1",
                    "locale": "th_TH",
                    "toolbar_bg": "#f8fafc",
                    "enable_publishing": false,
                    "allow_symbol_change": true,
                    "studies": [
                        "MASimple@tv-basicstudies",
                        "RSI@tv-basicstudies"
                    ],
                    "container_id": "tradingview_chart_container"
                });
            }
        },

        selectPrompt(id) {
            this.activePromptId = id;
            this.fieldValues = {};
            const p = this.activePrompt;
            p.fields.forEach((f, idx) => {
                this.fieldValues[f.key] = f.default || '';
                if (idx === 0) {
                    this.focusedFieldKey = f.key;
                }
            });
            this.rebuildPromptText();
            if (id === 8 && this.fieldValues['stock']) {
                this.newsTicker = this.fieldValues['stock'];
                this.fetchNewsFor(this.newsTicker);
            }
        },

        resetCurrentFields() {
            this.selectPrompt(this.activePromptId);
        },

        fillQuickTicker(symbol) {
            const p = this.activePrompt;
            if (!p || !p.fields || p.fields.length === 0) return;

            this.newsTicker = symbol;

            let targetKey = this.focusedFieldKey;
            if (!targetKey || !p.fields.some(f => f.key === targetKey)) {
                targetKey = p.fields[0].key;
            }

            if (targetKey === 'stockList') {
                const cur = (this.fieldValues[targetKey] || '').trim();
                if (!cur) {
                    this.fieldValues[targetKey] = symbol;
                } else if (!cur.includes(symbol)) {
                    this.fieldValues[targetKey] = cur + ', ' + symbol;
                }
            } else {
                this.fieldValues[targetKey] = symbol;
                const stockFields = p.fields.filter(f => f.key.startsWith('stock'));
                if (stockFields.length > 1) {
                    const curIdx = stockFields.findIndex(f => f.key === targetKey);
                    const nextField = stockFields[(curIdx + 1) % stockFields.length];
                    this.focusedFieldKey = nextField.key;
                }
            }
            this.rebuildPromptText();
            if (this.activePromptId === 8) {
                this.fetchNewsFor(symbol);
            }
        },

        rebuildPromptText() {
            let text = this.activePrompt.template;
            this.activePrompt.fields.forEach(f => {
                const val = (this.fieldValues[f.key] || '').trim() || '[' + f.label + ']';
                text = text.replace('{' + f.key + '}', val);
            });
            this.customPromptText = text;
        },

        copyPrompt() {
            navigator.clipboard.writeText(this.customPromptText);
            this.copiedPrompt = true;
            setTimeout(() => { this.copiedPrompt = false; }, 2200);
        },

        copyAnalysis() {
            navigator.clipboard.writeText(this.analysisText);
            this.copiedAnalysis = true;
            setTimeout(() => { this.copiedAnalysis = false; }, 2200);
        },

        async runAnalysis() {
            if (!this.customPromptText.trim()) return;
            this.loading = true;

            const tickerInputs = Object.entries(this.fieldValues)
                .filter(([k]) => k.startsWith('stock'))
                .map(([, v]) => v)
                .join(', ');

            try {
                const res = await fetch('{{ route("portfolio.ai.analyze") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        prompt_id: this.activePromptId,
                        prompt: this.customPromptText,
                        tickers: tickerInputs,
                    }),
                });

                const data = await res.json();
                if (data.success) {
                    this.marketData = data.market_data || [];
                    this.analysisText = data.analysis || '';
                    this.modelLabel = data.model_label || 'AI Stock Analyst';
                    this.lastGeneratedAt = data.generated_at || '';
                    if (data.news && data.news.length > 0) {
                        this.liveNews = data.news;
                        this.sentimentData = data.sentiment || null;
                        if (data.primary_sym) {
                            this.newsTicker = data.primary_sym;
                        }
                    }
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        async fetchNewsFor(symbol) {
            let s = (symbol || this.newsTicker || '').trim();
            if (!s) return;
            this.newsTicker = s;
            this.loadingNews = true;
            try {
                const res = await fetch('{{ route("portfolio.ai.news") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({ symbol: s }),
                });
                const d = await res.json();
                if (d.success) {
                    this.liveNews = d.news || [];
                    this.sentimentData = d.sentiment || null;
                    this.newsTicker = d.symbol || s;
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.loadingNews = false;
            }
        },

        analyzeNewsTrend(symbol) {
            let s = (symbol || this.newsTicker || 'DELTA.BK').trim();
            this.selectPrompt(8);
            this.fieldValues['stock'] = s;
            this.newsTicker = s;
            this.rebuildPromptText();
            this.runAnalysis();
        },

        sendToSniper(stock) {
            this.sniper.symbol = stock.symbol;
            this.sniper.entry = stock.price;
            this.sniper.stopLoss = stock.stop_loss || (stock.price * 0.965);
            this.sniper.target = stock.resistance1 || (stock.price * 1.15);

            this.tranche.p1 = stock.support1 || stock.price;
            this.tranche.p2 = stock.fib_levels['61.8% (Golden)'] || (stock.price * 0.95);
            this.tranche.p3 = stock.support2 || (stock.price * 0.90);

            this.activeTab = 'sniper';
            window.location.hash = 'sniper';
        },

        sendToPaperTrade(stock) {
            this.sniper.symbol = stock.symbol;
            this.sniper.entry = stock.price;
            this.sniper.target = stock.resistance1;
            this.sniper.stopLoss = stock.stop_loss;

            this.activeTab = 'paper';
            window.location.hash = 'paper';
        },

        sendSniperToPaperTrade() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("portfolio.ai.paper-trade.store") }}';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrf);

            const fields = {
                symbol: this.sniper.symbol,
                quantity: this.sniperOutput.shares > 0 ? this.sniperOutput.shares : 10,
                entry_price: this.sniper.entry,
                target_price: this.sniper.target,
                stop_loss: this.sniper.stopLoss,
                trade_thesis: 'Sniper Position Sizing R:R 1:' + this.sniperOutput.rrRatio,
            };

            for (const [k, v] of Object.entries(fields)) {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = k;
                inp.value = v;
                form.appendChild(inp);
            }

            document.body.appendChild(form);
            form.submit();
        },

        analyzeSingleHolding(symbol) {
            this.activeTab = 'prompts';
            window.location.hash = 'prompts';
            this.selectPrompt(2);
            this.fillQuickTicker(symbol);
            this.runAnalysis();
        },

        sendFullPortfolioToAi() {
            this.activeTab = 'prompts';
            window.location.hash = 'prompts';
            this.selectPrompt(6);
            this.runAnalysis();
        },

        renderMarkdown(md) {
            if (!md) return '';
            let html = md
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            html = html
                .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="text-brand-600 underline hover:text-brand-700 font-semibold">$1</a>')
                .replace(/^### (.*$)/gim, '<h4 class="text-sm font-bold text-slate-900 mt-4 mb-1.5">$1</h4>')
                .replace(/^## (.*$)/gim, '<h3 class="text-base font-bold text-slate-900 mt-3 mb-2">$1</h3>')
                .replace(/^> (.*$)/gim, '<div class="rounded-xl border border-indigo-200 bg-indigo-50/60 px-3.5 py-2 text-xs text-indigo-700 my-2">$1</div>')
                .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900">$1</strong>')
                .replace(/`(.*?)`/g, '<code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-semibold text-brand-600">$1</code>')
                .replace(/^\s*-\s+(.*$)/gim, '<div class="flex items-start gap-2 my-1"><span class="text-brand-600 font-bold">•</span><span class="flex-1">$1</span></div>')
                .replace(/\n\n/g, '<div class="my-2"></div>')
                .replace(/\n/g, '<br>');

            return html;
        }
    }));
});
</script>
</x-portfolio-layout>
