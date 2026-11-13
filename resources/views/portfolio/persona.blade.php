<x-portfolio-layout>

<div x-data="personalProfile()" class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8 space-y-6">

    {{-- Flash status notification --}}
    @if (session('status'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="flex items-center justify-between rounded-2xl border border-indigo-500/30 bg-indigo-500/10 px-4 py-3 text-sm text-indigo-300 backdrop-blur">
            <div class="flex items-center gap-2">
                <span class="text-base">👤</span>
                <span>{{ session('status') }}</span>
            </div>
            <button @click="show = false" class="text-indigo-400 hover:text-indigo-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- Top Header Banner --}}
    <div class="rounded-3xl border border-slate-800 bg-gradient-to-br from-[#141720] to-[#1a1f2c] p-6 sm:p-8 shadow-sm backdrop-blur relative overflow-hidden">
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start sm:items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 text-indigo-400 border border-indigo-500/30 shadow-inner">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-100">User Manual of Me</h1>
                        <span class="rounded-lg bg-indigo-500/20 px-2.5 py-0.5 text-xs font-bold text-indigo-300 border border-indigo-500/30">
                            {{ $profile->mbti ?: 'คู่มือตัวกูเอง' }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-slate-300 font-medium">
                        {{ $profile->headline ?: 'บันทึกลักษณะนิสัย จุดแข็ง-จุดอ่อน และวิธีจัดการตัวเอง' }}
                    </p>
                </div>
            </div>

            <button type="button" @click="openEditModal()"
                    class="inline-flex items-center gap-1.5 self-start sm:self-auto rounded-xl bg-indigo-500 hover:bg-indigo-400 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-md shadow-indigo-950/40 transition active:scale-95">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                </svg>
                <span>แก้ไขคู่มือตัวตน</span>
            </button>
        </div>
    </div>

    {{-- Verified Credentials & Certifications Badges Bar --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="flex items-center gap-3 rounded-2xl border border-slate-800 bg-[#141720]/80 p-3.5 backdrop-blur">
            <span class="text-2xl">🛡️</span>
            <div>
                <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">NCSA Thailand</div>
                <div class="text-xs font-bold text-slate-200">Cybersecurity Foundation</div>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-slate-800 bg-[#141720]/80 p-3.5 backdrop-blur">
            <span class="text-2xl">🌟</span>
            <div>
                <div class="text-[10px] uppercase tracking-wider text-purple-400 font-bold">Google for Education</div>
                <div class="text-xs font-bold text-slate-200">Gemini Certified Educator</div>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-slate-800 bg-[#141720]/80 p-3.5 backdrop-blur">
            <span class="text-2xl">🎓</span>
            <div>
                <div class="text-[10px] uppercase tracking-wider text-cyan-400 font-bold">ม.รังสิต (วท.บ.)</div>
                <div class="text-xs font-bold text-slate-200">นวัตกรรมดิจิทัล (GPA 3.21)</div>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-slate-800 bg-[#141720]/80 p-3.5 backdrop-blur">
            <span class="text-2xl">🎸</span>
            <div>
                <div class="text-[10px] uppercase tracking-wider text-amber-400 font-bold">Music & Community</div>
                <div class="text-xs font-bold text-slate-200">มือเบส & แอดมินเพจ 14k</div>
            </div>
        </div>
    </div>

    {{-- ── 1.5 Folk's Personal Life & Q&A Vault (Safe Zone / ตัวตน & ความชอบ) ── --}}
    <div class="rounded-3xl border border-purple-500/30 bg-gradient-to-br from-[#12101f] via-[#17132a] to-[#0e0c18] p-6 sm:p-8 shadow-2xl relative overflow-hidden space-y-6">
        {{-- Ambient Purple Glow --}}
        <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-purple-500/10 blur-3xl pointer-events-none"></div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-purple-900/40 pb-4">
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-purple-500/20 to-pink-500/20 text-purple-300 border border-purple-500/30 text-2xl shadow-inner">
                    💜
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg sm:text-xl font-extrabold text-white">Folk's Personal Life & Q&A Vault</h2>
                        <span class="rounded bg-purple-500/20 px-2 py-0.5 text-[10px] font-bold text-purple-300 border border-purple-500/30 font-mono">
                            FOLK'S ENCYCLOPEDIA
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-300">
                        คลังรวบรวมตัวตน ความชอบ และเรื่องราวชีวิตของโฟล์ก (Safe Zone สำหรับชีวิตส่วนตัว ไม่ปะปนกับเรื่องงาน)
                    </p>
                </div>
            </div>

            <button type="button" @click="openQaModal()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white font-bold text-xs px-4 py-2.5 transition shadow-lg shadow-purple-950/40 border border-purple-400/30 self-start sm:self-auto">
                <span>➕</span>
                <span>ตอบคำถามเพิ่ม</span>
            </button>
        </div>

        {{-- Core Personal Snapshot Pills --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="rounded-2xl border border-purple-500/20 bg-purple-950/30 p-3.5 flex items-center gap-3">
                <span class="text-2xl">🎂</span>
                <div>
                    <div class="text-[10px] text-purple-400 uppercase font-bold tracking-wider">วันเกิดโฟล์ก</div>
                    <div class="text-xs font-black text-slate-100">6 พ.ย. 2001 (อายุ 25 ปี)</div>
                </div>
            </div>
            <div class="rounded-2xl border border-purple-500/20 bg-purple-950/30 p-3.5 flex items-center gap-3">
                <span class="text-2xl">💜</span>
                <div>
                    <div class="text-[10px] text-purple-400 uppercase font-bold tracking-wider">สีโปรดที่สุด</div>
                    <div class="text-xs font-black text-purple-300">สีม่วง (Purple)</div>
                </div>
            </div>
            <div class="rounded-2xl border border-purple-500/20 bg-purple-950/30 p-3.5 flex items-center gap-3">
                <span class="text-2xl">🐱</span>
                <div>
                    <div class="text-[10px] text-purple-400 uppercase font-bold tracking-wider">สัตว์ที่ชอบ</div>
                    <div class="text-xs font-black text-slate-100">แมว (ทาสแมวตัวจริง)</div>
                </div>
            </div>
            <div class="rounded-2xl border border-purple-500/20 bg-purple-950/30 p-3.5 flex items-center gap-3">
                <span class="text-2xl">🏍️</span>
                <div>
                    <div class="text-[10px] text-purple-400 uppercase font-bold tracking-wider">พาหนะคู่ใจ</div>
                    <div class="text-xs font-black text-slate-100">มอเตอร์ไซค์คู่ชีพ</div>
                </div>
            </div>
        </div>

        {{-- Quick Prompts Bar --}}
        <div class="space-y-2">
            <div class="text-xs text-slate-400 flex items-center gap-1.5 font-medium">
                <span>💡</span>
                <span>คำถามชวนคุย (จิ้มเพื่อตอบสะสมลงคลังได้เลย):</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="openQaModal('เพลงที่เปิดฟังวนซ้ำบ่อยที่สุดช่วงนี้คือเพลงอะไร?', '🎵', 'ดนตรีและศิลปะ')"
                        class="rounded-xl border border-slate-800 bg-slate-900/80 hover:border-purple-500/40 hover:text-purple-300 text-slate-300 px-3 py-1.5 text-xs transition">
                    🎵 เพลงที่ฟังวนซ้ำตอนนี้?
                </button>
                <button type="button" @click="openQaModal('เมนูกาแฟหรือเครื่องดื่มที่ต้องสั่งประจำ?', '☕', 'ของกิน & เครื่องดื่ม')"
                        class="rounded-xl border border-slate-800 bg-slate-900/80 hover:border-purple-500/40 hover:text-purple-300 text-slate-300 px-3 py-1.5 text-xs transition">
                    ☕ กาแฟ/เครื่องดื่มที่ต้องสั่ง?
                </button>
                <button type="button" @click="openQaModal('เส้นทางที่อยากขี่มอเตอร์ไซค์ไปเที่ยวมากที่สุด?', '🗺️', 'ไลฟ์สไตล์')"
                        class="rounded-xl border border-slate-800 bg-slate-900/80 hover:border-purple-500/40 hover:text-purple-300 text-slate-300 px-3 py-1.5 text-xs transition">
                    🗺️ ทริปมอไซค์ในฝัน?
                </button>
                <button type="button" @click="openQaModal('เป้าหมายชีวิตสำคัญที่สุดในวัย 25 ปีเบญจเพสนี้คืออะไร?', '🎯', 'เป้าหมายชีวิต')"
                        class="rounded-xl border border-slate-800 bg-slate-900/80 hover:border-purple-500/40 hover:text-purple-300 text-slate-300 px-3 py-1.5 text-xs transition">
                    🎯 เป้าหมายในวัย 25 ปี?
                </button>
            </div>
        </div>

        {{-- Q&A Cards Bento Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
            @foreach ($qaList as $qa)
                <div class="group relative rounded-2xl border border-slate-800 bg-[#161424] p-4 shadow-sm hover:border-purple-500/40 transition">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">{{ $qa['icon'] ?? '✨' }}</span>
                            <span class="rounded bg-slate-800/80 px-2 py-0.5 text-[10px] font-bold text-purple-300 font-mono">
                                {{ $qa['category'] ?? 'ตัวตนทั่วไป' }}
                            </span>
                        </div>
                        <form method="POST" action="{{ route('portfolio.persona.qa.delete', $qa['id']) }}" onsubmit="return confirm('ยืนยันลบคำถามนี้หรือไม่?')" class="opacity-0 group-hover:opacity-100 transition">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-slate-500 hover:text-rose-400 text-xs p-1" title="ลบคำถาม">✕</button>
                        </form>
                    </div>

                    <div class="text-xs font-bold text-white mb-1.5">
                        {{ $qa['question'] }}
                    </div>
                    <div class="text-xs text-slate-300 leading-relaxed whitespace-pre-line bg-black/20 p-2.5 rounded-xl border border-purple-950/40">
                        {{ $qa['answer'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Cards Grid (Dimensions of Self) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">

        {{-- 1. Core Traits --}}
        <div class="rounded-2xl border border-slate-800 bg-[#141720]/90 p-5 shadow-sm backdrop-blur">
            <div class="flex items-center gap-2 text-indigo-400 font-bold text-sm mb-3">
                <span>🧠</span>
                <span>ลักษณะนิสัยใจคอ (Core Personality)</span>
            </div>
            <div class="text-xs sm:text-sm text-slate-300 whitespace-pre-line leading-relaxed">
                {{ $profile->traits ?: 'ยังไม่ได้ระบุ' }}
            </div>
        </div>

        {{-- 2. Strengths & Weaknesses --}}
        <div class="rounded-2xl border border-slate-800 bg-[#141720]/90 p-5 shadow-sm backdrop-blur space-y-4">
            <div>
                <div class="flex items-center gap-2 text-emerald-400 font-bold text-sm mb-2">
                    <span>💪</span>
                    <span>จุดแข็ง & สิ่งที่ทำได้ดี</span>
                </div>
                <div class="text-xs sm:text-sm text-slate-300 whitespace-pre-line leading-relaxed">
                    {{ $profile->strengths ?: 'ยังไม่ได้ระบุ' }}
                </div>
            </div>
            <div class="pt-3 border-t border-slate-800/80">
                <div class="flex items-center gap-2 text-amber-400 font-bold text-sm mb-2">
                    <span>⚠️</span>
                    <span>สิ่งที่ต้องระวัง / ข้อบกพร่อง</span>
                </div>
                <div class="text-xs sm:text-sm text-slate-300 whitespace-pre-line leading-relaxed">
                    {{ $profile->weaknesses ?: 'ยังไม่ได้ระบุ' }}
                </div>
            </div>
        </div>

        {{-- 3. Energy Boosters --}}
        <div class="rounded-2xl border border-emerald-500/20 bg-[#141720]/90 p-5 shadow-sm backdrop-blur">
            <div class="flex items-center gap-2 text-emerald-400 font-bold text-sm mb-3">
                <span>🔋</span>
                <span>สิ่งที่เติมพลัง & ฮีลใจ (Energy Boosters)</span>
            </div>
            <div class="text-xs sm:text-sm text-slate-300 whitespace-pre-line leading-relaxed">
                {{ $profile->energy_boosters ?: 'ยังไม่ได้ระบุ' }}
            </div>
        </div>

        {{-- 4. Energy Drainers --}}
        <div class="rounded-2xl border border-rose-500/20 bg-[#141720]/90 p-5 shadow-sm backdrop-blur">
            <div class="flex items-center gap-2 text-rose-400 font-bold text-sm mb-3">
                <span>🪫</span>
                <span>สิ่งที่สูบพลัง & ทำให้หงุดหงิด (Energy Drainers)</span>
            </div>
            <div class="text-xs sm:text-sm text-slate-300 whitespace-pre-line leading-relaxed">
                {{ $profile->energy_drainers ?: 'ยังไม่ได้ระบุ' }}
            </div>
        </div>

        {{-- 5. Emergency De-stress Protocol --}}
        <div class="rounded-2xl border border-amber-500/30 bg-[#141720]/90 p-5 shadow-sm backdrop-blur md:col-span-2">
            <div class="flex items-center gap-2 text-amber-400 font-bold text-sm mb-3">
                <span>🚨</span>
                <span>คู่มือฉุกเฉิน: วิธีรับมือเวลานอยด์ / เครียด / สติหลุด (De-stress Protocol)</span>
            </div>
            <div class="text-xs sm:text-sm text-slate-200 whitespace-pre-line leading-relaxed bg-amber-500/10 rounded-xl p-4 border border-amber-500/20">
                {{ $profile->coping_guide ?: 'ยังไม่ได้ระบุ' }}
            </div>
        </div>

        {{-- 6. Life Principles --}}
        <div class="rounded-2xl border border-slate-800 bg-[#141720]/90 p-5 shadow-sm backdrop-blur">
            <div class="flex items-center gap-2 text-indigo-400 font-bold text-sm mb-3">
                <span>📜</span>
                <span>กฎเหล็ก & ปรัชญาชีวิต (Life Principles)</span>
            </div>
            <div class="text-xs sm:text-sm text-slate-300 whitespace-pre-line leading-relaxed">
                {{ $profile->life_principles ?: 'ยังไม่ได้ระบุ' }}
            </div>
        </div>

        {{-- 7. Communication Style & Favorites --}}
        <div class="rounded-2xl border border-slate-800 bg-[#141720]/90 p-5 shadow-sm backdrop-blur space-y-4">
            <div>
                <div class="flex items-center gap-2 text-cyan-400 font-bold text-sm mb-2">
                    <span>💬</span>
                    <span>สไตล์การสื่อสารที่ชอบ</span>
                </div>
                <div class="text-xs sm:text-sm text-slate-300 whitespace-pre-line leading-relaxed">
                    {{ $profile->communication_style ?: 'ยังไม่ได้ระบุ' }}
                </div>
            </div>
            <div class="pt-3 border-t border-slate-800/80">
                <div class="flex items-center gap-2 text-purple-400 font-bold text-sm mb-2">
                    <span>⭐</span>
                    <span>ของโปรด & สิ่งที่ชอบ</span>
                </div>
                <div class="text-xs sm:text-sm text-slate-300 whitespace-pre-line leading-relaxed">
                    {{ $profile->favorite_things ?: 'ยังไม่ได้ระบุ' }}
                </div>
            </div>
        </div>

    </div>

    {{-- ── 8. AI SWOT Strategic Analysis Matrix (CV & Portfolio Evidence) ── --}}
    <div class="rounded-3xl border border-indigo-500/30 bg-gradient-to-br from-[#121624] via-[#151a2e] to-[#0e121d] p-6 sm:p-8 shadow-2xl backdrop-blur relative overflow-hidden space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 text-indigo-400 border border-indigo-500/30 text-2xl shadow-inner">
                    📊
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg sm:text-xl font-extrabold text-white">AI SWOT Strategic Analysis</h2>
                        <span class="rounded bg-indigo-500/20 px-2 py-0.5 text-[10px] font-bold text-indigo-300 border border-indigo-500/30 font-mono">
                            VERIFIED CAREER PROFILE
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-300">
                        วิเคราะห์จุดแข็ง จุดอ่อน โอกาส และความเสี่ยง จากประวัติการทำงานจริง (PTT Digital SOC + RSU Clinic ERP + ม.รังสิต + อ.ป.ว.)
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs font-mono text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1.5 rounded-xl">
                <span>🎯 Strategic Position: DevSecOps Specialist</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
            {{-- S: Strengths --}}
            <div class="rounded-2xl border border-emerald-500/30 bg-[#0f1923]/90 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-emerald-400 font-bold text-sm">
                        <span>💪</span>
                        <span>จุดแข็งภายใน (Strengths)</span>
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-500/80 bg-emerald-500/10 px-2 py-0.5 rounded">Core Assets</span>
                </div>
                <ul class="text-xs text-slate-300 space-y-2 list-disc list-inside leading-relaxed">
                    <li><strong class="text-emerald-300">Dual-Core Hybrid Profile:</strong> ความเชี่ยวชาญคู่ที่หายาก ทั้งสาย Security/SOC (Microsoft Sentinel, MDE, MDI, KQL) และ Full-Stack Web Dev (PHP 8, MySQL, Tailwind, LINE API)</li>
                    <li><strong class="text-emerald-300">Enterprise Scale Experience:</strong> มอนิเตอร์และตอบสนองภัยไซเบอร์ระดับประเทศที่ PTT Digital Solutions ควบคู่กับการสร้าง Enterprise Clinic ERP รองรับผู้ใช้กว่า 35,500 คน</li>
                    <li><strong class="text-emerald-300">System Architecture & AI Synergy:</strong> ออกแบบระบบ Context Persistence ให้ AI เข้าใจงานไว (hot.md, CLAUDE.md) และประยุกต์ใช้ Generative AI (Gemini 2.5) ในงานบริการจริง</li>
                    <li><strong class="text-emerald-300">Creative & Multi-Disciplinary:</strong> ทักษะดนตรี (มือเบสและดนตรีไทย) และการเป็นแอดมินเพจผู้ติดตามหลักหมื่น ช่วยให้มีความคิดสร้างสรรค์ สื่อสารเก่ง และไม่ตื่นตระหนกต่อปัญหา</li>
                </ul>
            </div>

            {{-- W: Weaknesses --}}
            <div class="rounded-2xl border border-amber-500/30 bg-[#1c1917]/80 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-amber-400 font-bold text-sm">
                        <span>⚠️</span>
                        <span>จุดอ่อนที่ต้องระวัง (Weaknesses)</span>
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-500/80 bg-amber-500/10 px-2 py-0.5 rounded">To Improve</span>
                </div>
                <ul class="text-xs text-slate-300 space-y-2 list-disc list-inside leading-relaxed">
                    <li><strong class="text-amber-300">Under-marketing ตัวเอง:</strong> เขียนเรซูเม่ถ่อมตัวเกินจริง (เรียก Clinic ERP ขนาดยักษ์เป็นแค่ระบบยืมคืนอุปกรณ์) ขาดการชูตัวเลขเชิงธุรกิจและผลกระทบจริง</li>
                    <li><strong class="text-amber-300">English Proficiency:</strong> ทักษะภาษาอังกฤษระดับ Limited Working Proficiency เป็นคอขวดสำคัญในการก้าวสู่ตำแหน่งระดับภูมิภาค (Regional / Global Remote)</li>
                    <li><strong class="text-amber-300">Circadian Stress:</strong> การทำงานแบบกะ 24/7 ในงาน SOC หากไม่คุมเวลาพักผ่อนให้ดี จะเกิดภาวะ Sleep Debt สะสมและดึงสมรรถนะการทำงานลง</li>
                    <li><strong class="text-amber-300">Hyper-focus Isolation:</strong> เวลาติดพันการดีบักหรือตามเคส มักจะนั่งแช่คนเดียวและลืมพักสายตาหรือรับประทานอาหาร</li>
                </ul>
            </div>

            {{-- O: Opportunities --}}
            <div class="rounded-2xl border border-cyan-500/30 bg-[#0e1e28]/80 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-cyan-400 font-bold text-sm">
                        <span>🚀</span>
                        <span>โอกาสเติบโตก้าวกระโดด (Opportunities)</span>
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-cyan-500/80 bg-cyan-500/10 px-2 py-0.5 rounded">Growth</span>
                </div>
                <ul class="text-xs text-slate-300 space-y-2 list-disc list-inside leading-relaxed">
                    <li><strong class="text-cyan-300">DevSecOps & Security Automation:</strong> ตลาดต้องการคนเขียนโค้ดและทำระบบตรวจจับความปลอดภัยพร้อมกันสูงมาก สามารถอัปเกรดเป็น Senior Tier 2 หรือ Security Engineer ได้อย่างรวดเร็ว</li>
                    <li><strong class="text-cyan-300">AI-Augmented SOC Analyst:</strong> โอกาสสร้าง Playbook อัตโนมัติและ Agent AI ช่วยกลั่นกรอง Alert ทำให้ออก Action ได้ไวกว่าคนทั่วไปหลายเท่าตัว</li>
                    <li><strong class="text-cyan-300">Global Certifications Path:</strong> เตรียมสอบใบรับรองสากล เช่น Microsoft SC-200 (Security Operations Analyst), AZ-500 หรือ CompTIA Security+ เพื่อปลดล็อกโอกาสเงินเดือนพุ่ง</li>
                    <li><strong class="text-cyan-300">Health-Tech SaaS:</strong> ต่อยอดระบบสถิติสุขภาพ, Smart Clinic ERP, และ IoT Telemetry เป็นแพลตฟอร์มเชิงพาณิชย์</li>
                </ul>
            </div>

            {{-- T: Threats --}}
            <div class="rounded-2xl border border-rose-500/30 bg-[#211116]/80 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-rose-400 font-bold text-sm">
                        <span>⚡</span>
                        <span>ความเสี่ยง & ความท้าทาย (Threats)</span>
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-rose-500/80 bg-rose-500/10 px-2 py-0.5 rounded">External Risks</span>
                </div>
                <ul class="text-xs text-slate-300 space-y-2 list-disc list-inside leading-relaxed">
                    <li><strong class="text-rose-300">L1 SOC Automation Shift:</strong> งานเฝ้าระวังภัย Tier 1 พื้นฐานเริ่มถูกระบบ SOAR และ AI คัดกรองแทนที่ ต้องรีบผันตัวเป็นสาย Threat Hunting / Automation Engineer ทันที</li>
                    <li><strong class="text-rose-300">Rapid Cloud Tech Evolution:</strong> ท่าโจมตีและฟีเจอร์ความปลอดภัยของคลาวด์เปลี่ยนทุกเดือน หากหยุดอัปเดตความรู้ KQL หรือระบบตรวจจับอาจล้าหลังได้ง่าย</li>
                    <li><strong class="text-rose-300">Burnout & Work-Life Balance:</strong> การรับภาระงานหลายด้านพร้อมกัน (เข้ากะ SOC + พัฒนาระบบคลินิก + โปรเจกต์ส่วนตัว) เสี่ยงต่ออาการหมดไฟ</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ── 9. Career & Skills Evolution Timeline (The Origin to Present) ── --}}
    <div class="rounded-3xl border border-slate-800 bg-[#141720]/90 p-6 sm:p-8 shadow-sm backdrop-blur space-y-6">
        <div class="flex items-center gap-3.5 border-b border-slate-800 pb-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500/20 to-orange-500/20 text-amber-400 border border-amber-500/30 text-2xl shadow-inner">
                ⏳
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-white">Career & Skills Evolution Timeline</h2>
                <p class="text-xs text-slate-400">
                    เส้นทางการเติบโตจากเด็กสายกิจกรรม ม.ปลาย สู่ปริญญานิพนธ์ Health Fitt และนักรบไซเบอร์ระดับองค์กร
                </p>
            </div>
        </div>

        <div class="relative border-l-2 border-indigo-500/30 ml-4 pl-6 space-y-8 text-xs sm:text-sm">
            {{-- Stage 1 --}}
            <div class="relative">
                <span class="absolute -left-[33px] top-0 flex h-6 w-6 items-center justify-center rounded-full bg-slate-900 border-2 border-amber-400 text-[10px] text-amber-300">
                    🎸
                </span>
                <div class="text-[11px] font-mono text-amber-400 uppercase tracking-wider font-bold">2559 – 2562 (ม.ปลาย อ.ป.ว.)</div>
                <h3 class="text-base font-bold text-white mt-1">ยุคบ่มเพาะ: นักดนตรี จิตอาสา และผู้สร้างสรรค์สื่อ</h3>
                <p class="text-slate-300 mt-1 leading-relaxed">
                    มือเบสวงสตริงโรงเรียนอ่างทองปัทมโรจน์วิทยาคม, แข่งขันดนตรีไทยระดับเขต (สพม.5), แอดมินเพจโรงเรียนผู้ติดตาม 14,000 คน, จิตอาสาแพทย์ค่ายลูกเสือ, และเริ่มศึกษาการสร้างเกมในค่าย RSU Game Dev 2018
                </p>
            </div>

            {{-- Stage 2 --}}
            <div class="relative">
                <span class="absolute -left-[33px] top-0 flex h-6 w-6 items-center justify-center rounded-full bg-slate-900 border-2 border-cyan-400 text-[10px] text-cyan-300">
                    🎓
                </span>
                <div class="text-[11px] font-mono text-cyan-400 uppercase tracking-wider font-bold">2563 – 2566 (ม.รังสิต · GPA 3.21)</div>
                <h3 class="text-base font-bold text-white mt-1">ยุคจุดประกาย: ปริญญานิพนธ์ RSU Health Fitt</h3>
                <p class="text-slate-300 mt-1 leading-relaxed">
                    สำเร็จการศึกษาวิทยาศาสตรบัณฑิต สาขานวัตกรรมดิจิทัล (เกรดเฉลี่ย 3.21) พัฒนาเว็บแอปพลิเคชัน <strong>RSU Health Fitt</strong> ในโครงการสหกิจศึกษาร่วมกับสำนักงานสวัสดิการสุขภาพ ม.รังสิต เชื่อมต่อ LINE LIFF API คำนวณ BMI, BMR, TDEE และแคลอรี่อาหาร
                </p>
            </div>

            {{-- Stage 3 --}}
            <div class="relative">
                <span class="absolute -left-[33px] top-0 flex h-6 w-6 items-center justify-center rounded-full bg-slate-900 border-2 border-purple-400 text-[10px] text-purple-300">
                    🏥
                </span>
                <div class="text-[11px] font-mono text-purple-400 uppercase tracking-wider font-bold">2565 – 2567 (DODEEP & Clinic Core)</div>
                <h3 class="text-base font-bold text-white mt-1">ยุคขยายสเกล: Enterprise Clinic ERP System</h3>
                <p class="text-slate-300 mt-1 leading-relaxed">
                    ยกระดับสถาปัตยกรรมสู่ระบบบริหารคลินิกขนาดใหญ่ (RSU Medical Clinic Services) ทั้งทะเบียนกลาง <code>sys_persons</code> 35,500+ คน, ตารางเวรพยาบาล Brush-Paint, ระบบคำนวณเงินเดือนภาษีไทย ภ.ง.ด.1, Patient Billing AR Aging และระบบ AI ตอบแชทคนไข้
                </p>
            </div>

            {{-- Stage 4 --}}
            <div class="relative">
                <span class="absolute -left-[33px] top-0 flex h-6 w-6 items-center justify-center rounded-full bg-slate-900 border-2 border-emerald-400 text-[10px] text-emerald-300">
                    🛡️
                </span>
                <div class="text-[11px] font-mono text-emerald-400 uppercase tracking-wider font-bold">2566 – ปัจจุบัน (PTT Digital & Trimongkol)</div>
                <h3 class="text-base font-bold text-white mt-1">ยุคนักรบไซเบอร์ & Life Operating System</h3>
                <p class="text-slate-300 mt-1 leading-relaxed">
                    ปฏิบัติหน้าที่ <strong>SOC Monitoring T1 ที่ PTT Digital Solutions</strong> เชี่ยวชาญการใช้ Microsoft Azure Sentinel, MDE, MDI และ KQL สืบสวนภัยคุกคาม ควบคู่กับการพัฒนาแพลตฟอร์ม <strong>Trimongkol Service</strong> (Bio-Hacker Watch 7 Telemetry + Second Brain P.A.R.A.)
                </p>
            </div>
        </div>
    </div>

    {{-- 8. Bio-Hacker & Health Telemetry (Samsung Galaxy Watch 7 + Health Connect) --}}
    <div class="rounded-3xl border border-cyan-500/30 bg-gradient-to-br from-[#0c121e] to-[#141d2b] p-6 shadow-xl backdrop-blur relative overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 text-2xl shadow-inner">
                    🏃‍♂️
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base sm:text-lg font-bold text-white">Bio-Hacker Health Telemetry</h3>
                        <span class="rounded bg-cyan-500/20 px-2 py-0.5 text-[10px] font-bold text-cyan-300 border border-cyan-500/30 font-mono">
                            WATCH 7 CONNECTED
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">
                        ดึงข้อมูลสุขภาพ ก้าวเดิน หัวใจ การนอน จาก Samsung Galaxy Watch 7 ผ่าน Health Connect Webhook
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 text-xs font-mono text-slate-400">
                <span class="h-2 w-2 rounded-full {{ $health ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' }}"></span>
                <span>{{ $health ? 'สถานะ: ซิงก์ล่าสุด ' . $health->date->format('d/m/Y') : 'สถานะ: รอข้อมูลชุดแรกจากมือถือ' }}</span>
            </div>
        </div>

        {{-- Latest Health Stats Grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
            <div class="rounded-2xl bg-slate-900/80 border border-slate-800/80 p-3.5">
                <div class="text-[11px] text-slate-400 flex items-center gap-1.5">
                    <span>👟</span>
                    <span>ก้าวเดิน</span>
                </div>
                <div class="mt-1 text-lg font-black text-white">
                    {{ $health && $health->steps ? number_format($health->steps) : '—' }}
                    <span class="text-xs font-normal text-slate-400">ก้าว</span>
                </div>
            </div>

            <div class="rounded-2xl bg-slate-900/80 border border-slate-800/80 p-3.5">
                <div class="text-[11px] text-slate-400 flex items-center gap-1.5">
                    <span>❤️</span>
                    <span>อัตราเต้นหัวใจ</span>
                </div>
                <div class="mt-1 text-lg font-black text-rose-400">
                    {{ $health && $health->heart_rate_avg ? $health->heart_rate_avg : '—' }}
                    <span class="text-xs font-normal text-slate-400">bpm</span>
                </div>
            </div>

            <div class="rounded-2xl bg-slate-900/80 border border-slate-800/80 p-3.5">
                <div class="text-[11px] text-slate-400 flex items-center gap-1.5">
                    <span>🌙</span>
                    <span>การนอนหลับ</span>
                </div>
                <div class="mt-1 text-lg font-black text-purple-400">
                    {{ $health && $health->sleep_minutes ? round($health->sleep_minutes / 60, 1) : '—' }}
                    <span class="text-xs font-normal text-slate-400">ชม.</span>
                </div>
            </div>

            <div class="rounded-2xl bg-slate-900/80 border border-slate-800/80 p-3.5">
                <div class="text-[11px] text-slate-400 flex items-center gap-1.5">
                    <span>🔥</span>
                    <span>แคลอรีที่เผาผลาญ</span>
                </div>
                <div class="mt-1 text-lg font-black text-amber-400">
                    {{ $health && $health->active_calories ? number_format($health->active_calories) : '—' }}
                    <span class="text-xs font-normal text-slate-400">kcal</span>
                </div>
            </div>
        </div>

        {{-- Mobile Setup Instruction Drawer / Box --}}
        <div class="mt-4 rounded-2xl bg-black/50 border border-slate-800/80 p-4 space-y-2.5 font-mono text-xs">
            <div class="flex items-center justify-between text-slate-300 font-bold font-sans">
                <span class="flex items-center gap-1.5 text-cyan-300">
                    <span>📱</span>
                    <span>วิธีตั้งค่ามือถือ Android เพื่อส่งข้อมูลขึ้นเว็บอัตโนมัติ:</span>
                </span>
                <span class="text-[11px] text-slate-400 font-mono">1 นาทีเสร็จ</span>
            </div>
            <ol class="list-decimal list-inside space-y-1.5 text-slate-300 font-sans text-xs leading-relaxed">
                <li>เปิด <strong>Google Play Store</strong> บนมือถือ โหลดแอปฟรี <strong>Health Connect Webhook</strong> (หรือ HC Webhook)</li>
                <li>เปิดแอปแล้วกดยืนยันสิทธิ์ <strong>Health Connect</strong> ให้แอปอ่านข้อมูล (Steps, Sleep, Heart Rate, Calories)</li>
                <li>ใส่ช่อง <strong>Webhook URL</strong>: <code class="rounded bg-slate-900 px-1.5 py-0.5 text-cyan-300 font-mono select-all">https://trimongkol.com/api/health/webhook</code></li>
                <li>ในหัวข้อ Header หรือ Token: ใส่ Header <code class="rounded bg-slate-900 px-1.5 py-0.5 text-amber-300 font-mono select-all">X-Health-Token: {{ config('services.health_webhook.secret') ?: '<HEALTH_WEBHOOK_SECRET>' }}</code> หรือ <code class="rounded bg-slate-900 px-1.5 py-0.5 text-amber-300 font-mono select-all">Authorization: Bearer {{ config('services.health_webhook.secret') ?: '<HEALTH_WEBHOOK_SECRET>' }}</code></li>
                <li>เลือกความถี่ Sync เป็น <strong>Hourly (ทุกชั่วโมง)</strong> หรือ Daily แล้วกดปุ่ม <strong>Test / Sync Now</strong> ได้ทันที!</li>
            </ol>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
         @keydown.escape.window="closeModal()">
        <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-3xl border border-slate-800 bg-[#141720] p-6 shadow-2xl transition-all"
             @click.outside="closeModal()">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800 sticky top-0 bg-[#141720] z-10">
                <h3 class="text-lg font-bold text-slate-100">แก้ไขคู่มือตัวตน (User Manual of Me)</h3>
                <button type="button" @click="closeModal()" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-200 hover:bg-slate-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('portfolio.persona.update') }}" method="POST" class="mt-4 space-y-4">
                @csrf

                {{-- Headline & MBTI --}}
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 mb-1">คำจำกัดความตัวเอง (Headline)</label>
                        <input type="text" name="headline" x-model="form.headline"
                               class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">MBTI / Type</label>
                        <input type="text" name="mbti" x-model="form.mbti" placeholder="เช่น ISTP, INTJ"
                               class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                {{-- Core Traits --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">🧠 ลักษณะนิสัยใจคอ (Core Personality)</label>
                    <textarea name="traits" x-model="form.traits" rows="3"
                              class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                {{-- Strengths & Weaknesses --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">💪 จุดแข็ง & สิ่งที่ทำได้ดี</label>
                        <textarea name="strengths" x-model="form.strengths" rows="3"
                                  class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">⚠️ จุดที่ต้องระวัง / จุดอ่อน</label>
                        <textarea name="weaknesses" x-model="form.weaknesses" rows="3"
                                  class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                {{-- Energy Boosters & Drainers --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">🔋 สิ่งที่เติมพลัง & ฮีลใจ</label>
                        <textarea name="energy_boosters" x-model="form.energy_boosters" rows="3"
                                  class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">🪫 สิ่งที่สูบพลัง & หงุดหงิด</label>
                        <textarea name="energy_drainers" x-model="form.energy_drainers" rows="3"
                                  class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                {{-- Coping Guide --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">🚨 คู่มือฉุกเฉินเวลานอยด์/เครียด (Coping Guide)</label>
                    <textarea name="coping_guide" x-model="form.coping_guide" rows="3"
                              class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                {{-- Principles & Communication --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">📜 กฎเหล็ก & ปรัชญาชีวิต</label>
                        <textarea name="life_principles" x-model="form.life_principles" rows="3"
                                  class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">💬 สไตล์การสื่อสารที่ชอบ</label>
                        <textarea name="communication_style" x-model="form.communication_style" rows="3"
                                  class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                {{-- Favorite Things --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">⭐ ของโปรด & สิ่งที่รักเป็นพิเศษ</label>
                    <textarea name="favorite_things" x-model="form.favorite_things" rows="2"
                              class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                {{-- Submit Buttons --}}
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" @click="closeModal()"
                            class="rounded-xl border border-slate-700 px-4 py-2 text-xs sm:text-sm font-semibold text-slate-300 hover:bg-slate-800">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-indigo-500 hover:bg-indigo-400 px-5 py-2 text-xs sm:text-sm font-semibold text-white shadow-md transition">
                        บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Q&A Answer / Add Modal ── --}}
    <div x-show="qaModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         @keydown.escape.window="closeQaModal()">
        <div class="w-full max-w-lg rounded-3xl border border-purple-500/30 bg-[#161325] p-6 sm:p-7 shadow-2xl space-y-5"
             @click.outside="closeQaModal()">
            <div class="flex items-center justify-between border-b border-purple-900/40 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">💜</span>
                    <h3 class="text-base sm:text-lg font-bold text-white">บันทึกคำตอบสู่คลังตัวตนของโฟล์ก</h3>
                </div>
                <button type="button" @click="closeQaModal()" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <form method="POST" action="{{ route('portfolio.persona.qa.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-purple-300 mb-1">คำถาม</label>
                    <input type="text" name="question" x-model="qaForm.question" required
                           placeholder="เช่น ชอบสีอะไร? หรือ เพลงที่ฟังวนซ้ำตอนนี้?"
                           class="w-full rounded-xl border border-purple-900/50 bg-[#0f0d1a] px-3.5 py-2.5 text-xs sm:text-sm text-slate-100 focus:border-purple-500 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-purple-300 mb-1">คำตอบของโฟล์ก</label>
                    <textarea name="answer" x-model="qaForm.answer" rows="3" required
                              placeholder="ตอบตามฟีลตัวเองได้เลย เช่น สีม่วง เพราะมันดูลึกลับและเท่ดี"
                              class="w-full rounded-xl border border-purple-900/50 bg-[#0f0d1a] px-3.5 py-2.5 text-xs sm:text-sm text-slate-100 focus:border-purple-500 focus:ring-purple-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">ไอคอนแสดงผล</label>
                        <select name="icon" x-model="qaForm.icon"
                                class="w-full rounded-xl border border-purple-900/50 bg-[#0f0d1a] px-3 py-2 text-xs text-slate-200">
                            <option value="💜">💜 สีม่วง / ความชอบ</option>
                            <option value="🐱">🐱 แมว / สัตว์เลี้ยง</option>
                            <option value="🎂">🎂 วันเกิด</option>
                            <option value="🏍️">🏍️ มอเตอร์ไซค์</option>
                            <option value="🎸">🎸 ดนตรี / เบส</option>
                            <option value="🎵">🎵 เพลงโปรด</option>
                            <option value="☕">☕ กาแฟ / เครื่องดื่ม</option>
                            <option value="🍲">🍲 ของกิน</option>
                            <option value="🎮">🎮 เกม</option>
                            <option value="🏖️">🏖️ พักผ่อน / ท่องเที่ยว</option>
                            <option value="🎯">🎯 เป้าหมายชีวิต</option>
                            <option value="✨">✨ อื่นๆ</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">หมวดหมู่</label>
                        <select name="category" x-model="qaForm.category"
                                class="w-full rounded-xl border border-purple-900/50 bg-[#0f0d1a] px-3 py-2 text-xs text-slate-200">
                            <option value="ความชอบส่วนตัว">ความชอบส่วนตัว</option>
                            <option value="ไลฟ์สไตล์">ไลฟ์สไตล์</option>
                            <option value="ของกิน & เครื่องดื่ม">ของกิน & เครื่องดื่ม</option>
                            <option value="ดนตรีและศิลปะ">ดนตรีและศิลปะ</option>
                            <option value="เป้าหมายชีวิต">เป้าหมายชีวิต</option>
                            <option value="ข้อมูลสำคัญ">ข้อมูลสำคัญ</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-purple-900/40">
                    <button type="button" @click="closeQaModal()"
                            class="rounded-xl border border-slate-700 px-4 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-800">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 px-5 py-2 text-xs font-semibold text-white shadow-md transition">
                        บันทึกคำตอบ 💜
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function personalProfile() {
    return {
        modalOpen: false,
        qaModalOpen: false,
        form: {
            headline: @json($profile->headline ?? ''),
            mbti: @json($profile->mbti ?? ''),
            traits: @json($profile->traits ?? ''),
            strengths: @json($profile->strengths ?? ''),
            weaknesses: @json($profile->weaknesses ?? ''),
            energy_boosters: @json($profile->energy_boosters ?? ''),
            energy_drainers: @json($profile->energy_drainers ?? ''),
            coping_guide: @json($profile->coping_guide ?? ''),
            life_principles: @json($profile->life_principles ?? ''),
            communication_style: @json($profile->communication_style ?? ''),
            favorite_things: @json($profile->favorite_things ?? '')
        },
        qaForm: {
            question: '',
            answer: '',
            icon: '💜',
            category: 'ความชอบส่วนตัว'
        },

        openEditModal() {
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
        },

        openQaModal(q = '', icon = '💜', cat = 'ความชอบส่วนตัว') {
            this.qaForm.question = q;
            this.qaForm.answer = '';
            this.qaForm.icon = icon;
            this.qaForm.category = cat;
            this.qaModalOpen = true;
        },

        closeQaModal() {
            this.qaModalOpen = false;
        }
    };
}
</script>

</x-portfolio-layout>
