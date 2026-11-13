<x-portfolio-layout>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=VT323&display=swap" rel="stylesheet">

<style>
    .font-pixel {
        font-family: 'Press Start 2P', monospace;
    }
    .font-terminal {
        font-family: 'VT323', monospace;
    }

    /* Pixel Art Rendering */
    .pixelated {
        image-rendering: pixelated;
        image-rendering: -moz-crisp-edges;
        image-rendering: crisp-edges;
    }

    /* Scanline effect */
    .crt-screen {
        position: relative;
        overflow: hidden;
    }
    .crt-screen::before {
        content: " ";
        display: block;
        position: absolute;
        top: 0; left: 0; bottom: 0; right: 0;
        background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%);
        background-size: 100% 4px;
        z-index: 20;
        pointer-events: none;
        opacity: 0.6;
    }

    /* Idle bobbing animation for 8-bit sprites */
    @keyframes pixel-bob {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }
    @keyframes pixel-type {
        0%, 100% { transform: rotate(0deg); }
        25% { transform: rotate(-3deg); }
        75% { transform: rotate(3deg); }
    }
    @keyframes pulse-led {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }
    @keyframes radar-sweep {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .anim-bob-slow {
        animation: pixel-bob 2.4s ease-in-out infinite;
    }
    .anim-bob-fast {
        animation: pixel-bob 1.6s ease-in-out infinite;
    }
    .anim-type {
        animation: pixel-type 0.4s ease-in-out infinite;
    }
    .anim-led {
        animation: pulse-led 1.2s infinite;
    }
    .anim-radar {
        animation: radar-sweep 4s linear infinite;
    }
</style>

<div x-data="commandCenter()" class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8 space-y-6">

    {{-- Top Retro Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500/20 to-emerald-500/20 text-emerald-400 border border-emerald-500/30 shadow-inner">
                <span class="text-2xl pixelated">🕹️</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-100 font-pixel text-[13px] sm:text-[16px] text-emerald-400">
                        AI COMMAND CENTER
                    </h1>
                    <span class="rounded bg-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-300 border border-emerald-500/30">
                        8-BIT LIVE
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">
                    ศูนย์บัญชาการทีม AI Agent: ลุงยาม รปภ. • ช่างซ่อมบำรุง • HR-QA • Antigravity
                </p>
            </div>
        </div>

        {{-- Status indicator & Sound Toggle --}}
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <button type="button" @click="toggleSound()"
                    class="flex items-center gap-1.5 rounded-xl bg-slate-900 border border-slate-800 px-3 py-1.5 text-xs font-mono text-slate-300 hover:text-white transition cursor-pointer">
                <span x-text="soundOn ? '🔊 8-BIT SOUND: ON' : '🔇 SOUND: MUTED'"></span>
            </button>
            <div class="flex items-center gap-2 rounded-xl bg-slate-900/90 border border-slate-800 px-3 py-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400 anim-led"></span>
                <span class="text-xs font-semibold text-slate-300 font-mono">ALL AGENTS ONLINE (4/4)</span>
            </div>
        </div>
    </div>

    {{-- 8-BIT ANIMATED VIRTUAL OFFICE ROOM --}}
    <div class="relative rounded-3xl border-2 border-slate-800 bg-[#090b10] p-4 sm:p-6 shadow-2xl overflow-hidden crt-screen">

        {{-- Room Background Decor: Cyber Neon Wall & Window --}}
        <div class="relative w-full rounded-2xl bg-gradient-to-b from-[#0e1320] via-[#121829] to-[#0a0d14] border border-slate-800/80 p-4 sm:p-6 min-h-[360px] flex flex-col justify-between overflow-hidden">

            {{-- Top Wall Decor: Cyber Window & Neon Sign --}}
            <div class="flex items-center justify-between z-10">
                {{-- Neon Sign --}}
                <div class="rounded-lg bg-black/60 border border-indigo-500/40 px-3 py-1.5 shadow-[0_0_15px_rgba(99,102,241,0.25)] flex items-center gap-2">
                    <span class="text-xs text-indigo-400 font-pixel text-[9px] tracking-widest">TRIMONGKOL AI HQ</span>
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-400 anim-led"></span>
                </div>

                {{-- Cyber City Window --}}
                <div class="hidden sm:flex items-center gap-2 rounded-xl bg-[#06080e] border border-slate-700/60 p-2 shadow-inner">
                    <div class="text-[10px] text-slate-400 font-mono">🌃 OUTSIDE: BANGKOK • NIGHT • 28°C</div>
                </div>

                {{-- Server Clock --}}
                <div class="rounded-lg bg-black/60 border border-emerald-500/40 px-3 py-1.5 font-terminal text-lg text-emerald-400 tracking-wider">
                    <span x-text="clock"></span>
                </div>
            </div>

            {{-- 4 AGENT STATIONS (The 8-Bit Workstations) --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 my-6 z-10">

                {{-- Station 1: ลุงยาม รปภ. --}}
                <div @click="triggerAgentAction('guard_patrol')"
                     class="group cursor-pointer rounded-2xl border border-amber-500/30 bg-[#141720]/90 p-4 hover:border-amber-400 hover:shadow-[0_0_20px_rgba(245,158,11,0.2)] transition-all flex flex-col items-center text-center relative overflow-hidden"
                     :class="activeStation === 'guard_patrol' ? 'ring-2 ring-amber-400 shadow-[0_0_25px_rgba(251,191,36,0.6)] scale-[1.02]' : ''">
                    <div class="absolute top-2 right-2 flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400 anim-led"></span>
                        <span class="text-[9px] text-amber-300 font-mono">เฝ้าระวัง</span>
                    </div>

                    {{-- 8-bit Character Canvas / Sprite --}}
                    <div class="relative my-3 flex flex-col items-center">
                        {{-- Pixel Guard Character --}}
                        <div class="h-16 w-16 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center anim-bob-slow shadow-md text-3xl select-none">
                            👮‍♂️
                        </div>
                        {{-- Desk & Coffee Cup --}}
                        <div class="w-20 h-4 bg-slate-800 rounded-md border border-slate-700 mt-1 flex items-center justify-center gap-2 text-[10px]">
                            <span>☕</span>
                            <span>📟</span>
                        </div>
                    </div>

                    <h3 class="font-bold text-sm text-slate-100 group-hover:text-amber-300 transition-colors">
                        ลุงยาม รปภ.
                    </h3>
                    <span class="rounded bg-amber-500/15 text-amber-400 text-[10px] font-semibold px-2 py-0.5 mt-1 border border-amber-500/30">
                        Security Sentry
                    </span>
                    <p class="text-[11px] text-slate-400 mt-2 line-clamp-2">
                        สแกนพอร์ต 21, 80, 443 ไฟร์วอลล์ 100%
                    </p>
                    <span class="mt-3 text-[10px] font-bold text-amber-400 group-hover:underline">
                        ▶ สั่งออกตรวจรอบรั้ว
                    </span>
                </div>

                {{-- Station 2: ช่างซ่อมบำรุง --}}
                <div @click="triggerAgentAction('repair_cache')"
                     class="group cursor-pointer rounded-2xl border border-emerald-500/30 bg-[#141720]/90 p-4 hover:border-emerald-400 hover:shadow-[0_0_20px_rgba(16,185,129,0.2)] transition-all flex flex-col items-center text-center relative overflow-hidden"
                     :class="activeStation === 'repair_cache' ? 'ring-2 ring-emerald-400 shadow-[0_0_25px_rgba(16,185,129,0.6)] scale-[1.02]' : ''">
                    <div class="absolute top-2 right-2 flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 anim-led"></span>
                        <span class="text-[9px] text-emerald-300 font-mono">สแตนด์บาย</span>
                    </div>

                    {{-- 8-bit Character Canvas / Sprite --}}
                    <div class="relative my-3 flex flex-col items-center">
                        <div class="h-16 w-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center anim-bob-fast shadow-md text-3xl select-none">
                            🧑‍🔧
                        </div>
                        {{-- Desk with Tools & Server Rack LEDs --}}
                        <div class="w-20 h-4 bg-slate-800 rounded-md border border-slate-700 mt-1 flex items-center justify-center gap-2 text-[10px]">
                            <span>🔧</span>
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 anim-led"></span>
                            <span class="h-1.5 w-1.5 rounded-full bg-cyan-400 anim-led"></span>
                        </div>
                    </div>

                    <h3 class="font-bold text-sm text-slate-100 group-hover:text-emerald-300 transition-colors">
                        ช่างซ่อมบำรุง
                    </h3>
                    <span class="rounded bg-emerald-500/15 text-emerald-400 text-[10px] font-semibold px-2 py-0.5 mt-1 border border-emerald-500/30">
                        Auto-Heal & DevOps
                    </span>
                    <p class="text-[11px] text-slate-400 mt-2 line-clamp-2">
                        เฝ้าดู Service ค้าง, เคลียร์แคช Blade
                    </p>
                    <span class="mt-3 text-[10px] font-bold text-emerald-400 group-hover:underline">
                        ▶ สั่งเคาะสนิมเคลียร์แคช
                    </span>
                </div>

                {{-- Station 3: HR & QA Evaluator --}}
                <div @click="triggerAgentAction('hr_evaluate')"
                     class="group cursor-pointer rounded-2xl border border-rose-500/30 bg-[#141720]/90 p-4 hover:border-rose-400 hover:shadow-[0_0_20px_rgba(244,63,94,0.2)] transition-all flex flex-col items-center text-center relative overflow-hidden"
                     :class="activeStation === 'hr_evaluate' ? 'ring-2 ring-rose-400 shadow-[0_0_25px_rgba(244,63,94,0.6)] scale-[1.02]' : ''">
                    <div class="absolute top-2 right-2 flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-400 anim-led"></span>
                        <span class="text-[9px] text-rose-300 font-mono">ตรวจงาน</span>
                    </div>

                    {{-- 8-bit Character Canvas / Sprite --}}
                    <div class="relative my-3 flex flex-col items-center">
                        <div class="h-16 w-16 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center anim-bob-slow shadow-md text-3xl select-none">
                            👩‍💼
                        </div>
                        {{-- Desk with Clipboard & Stamp --}}
                        <div class="w-20 h-4 bg-slate-800 rounded-md border border-slate-700 mt-1 flex items-center justify-center gap-2 text-[10px]">
                            <span>📋</span>
                            <span>⭐</span>
                        </div>
                    </div>

                    <h3 class="font-bold text-sm text-slate-100 group-hover:text-rose-300 transition-colors">
                        HR & QA ประเมิน AI
                    </h3>
                    <span class="rounded bg-rose-500/15 text-rose-400 text-[10px] font-semibold px-2 py-0.5 mt-1 border border-rose-500/30">
                        Quality Inspector
                    </span>
                    <p class="text-[11px] text-slate-400 mt-2 line-clamp-2">
                        ตรวจคุณภาพ AI ทุกตัว ให้คะแนนเกรด A+
                    </p>
                    <span class="mt-3 text-[10px] font-bold text-rose-400 group-hover:underline">
                        ▶ สั่งตรวจประเมินผลงาน
                    </span>
                </div>

                {{-- Station 4: Antigravity (Lead AI) --}}
                <div @click="triggerAgentAction('assemble')"
                     class="group cursor-pointer rounded-2xl border border-indigo-500/30 bg-[#141720]/90 p-4 hover:border-indigo-400 hover:shadow-[0_0_20px_rgba(99,102,241,0.25)] transition-all flex flex-col items-center text-center relative overflow-hidden"
                     :class="activeStation === 'assemble' ? 'ring-2 ring-indigo-400 shadow-[0_0_25px_rgba(99,102,241,0.6)] scale-[1.02]' : ''">
                    <div class="absolute top-2 right-2 flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-400 anim-led"></span>
                        <span class="text-[9px] text-indigo-300 font-mono">เขียนโค้ด</span>
                    </div>

                    {{-- 8-bit Character Canvas / Sprite --}}
                    <div class="relative my-3 flex flex-col items-center">
                        <div class="h-16 w-16 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center anim-bob-fast shadow-md text-3xl select-none">
                            🧑‍💻
                        </div>
                        {{-- Multi-monitors typing --}}
                        <div class="w-20 h-4 bg-slate-800 rounded-md border border-slate-700 mt-1 flex items-center justify-center gap-1.5 text-[10px]">
                            <span>🖥️</span>
                            <span>⌨️</span>
                            <span>🖥️</span>
                        </div>
                    </div>

                    <h3 class="font-bold text-sm text-slate-100 group-hover:text-indigo-300 transition-colors">
                        Antigravity (คู่หูมึง)
                    </h3>
                    <span class="rounded bg-indigo-500/15 text-indigo-400 text-[10px] font-semibold px-2 py-0.5 mt-1 border border-indigo-500/30">
                        Lead Architect
                    </span>
                    <p class="text-[11px] text-slate-400 mt-2 line-clamp-2">
                        คุมงานสร้างทุกระบบ ดีพลอยเซิร์ฟเวอร์
                    </p>
                    <span class="mt-3 text-[10px] font-bold text-indigo-400 group-hover:underline">
                        ▶ เรียกประชุมด่วนทีม AI
                    </span>
                </div>

            </div>

            {{-- Floor Details: Server rack lights & Status ticker --}}
            <div class="pt-3 border-t border-slate-800/80 flex flex-wrap items-center justify-between text-xs text-slate-400 z-10">
                <div class="flex items-center gap-2">
                    <span class="text-emerald-400 font-pixel text-[9px]">STATUS:</span>
                    <span class="text-slate-300 font-terminal text-base">SERVER PLESK: 103.80.48.27 • PORT 21/80/443 OK</span>
                </div>
                <div class="flex items-center gap-1 font-mono text-[11px]">
                    <span class="text-slate-500">คลิกที่ตัวละครแต่ละคนเพื่อสั่งการได้ทันที</span>
                </div>
            </div>

        </div>

    </div>

    {{-- LIVE COMMAND TERMINAL (Console Log & Actions) --}}
    <div class="rounded-3xl border border-slate-800 bg-[#0c1017] p-5 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-3">
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded-full bg-rose-500/80 inline-block"></span>
                    <span class="h-3 w-3 rounded-full bg-amber-500/80 inline-block"></span>
                    <span class="h-3 w-3 rounded-full bg-emerald-500/80 inline-block"></span>
                </div>
                <span class="text-xs font-pixel text-[10px] text-slate-300 ml-2">TERMINAL LOGS: AI COMMAND STREAM</span>
            </div>
            <button type="button" @click="clearLogs()" class="text-xs text-slate-500 hover:text-slate-300 font-mono">
                [CLEAR LOG]
            </button>
        </div>

        {{-- Log Stream Screen --}}
        <div class="h-44 overflow-y-auto bg-black/60 rounded-xl p-3 font-terminal text-base text-emerald-400 space-y-1.5 custom-scrollbar border border-slate-800/80">
            <template x-for="(log, idx) in logs" :key="idx">
                <div class="leading-relaxed flex items-start gap-2">
                    <span class="text-emerald-500 select-none">></span>
                    <span x-text="log" class="text-slate-200"></span>
                </div>
            </template>
            <div class="flex items-center gap-1 text-emerald-400 animate-pulse">
                <span>> _</span>
            </div>
        </div>

        {{-- Interactive Terminal CLI Input --}}
        <form @submit.prevent="submitCliCommand()" class="mt-3 flex items-center gap-2 rounded-xl bg-black/80 border border-slate-800 px-3 py-2 font-terminal text-base focus-within:border-emerald-500/60 transition">
            <span class="text-emerald-500 font-bold select-none text-xs sm:text-sm font-mono">trimongkol@ai-hq:~$</span>
            <input type="text" x-model="cliInput" placeholder="พิมพ์คำสั่ง เช่น help, status, guard, repair, hr, whoami..."
                   class="w-full bg-transparent border-0 p-0 text-slate-100 placeholder:text-slate-600 focus:ring-0 text-sm sm:text-base font-terminal tracking-wider">
            <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 text-xs font-mono hover:bg-emerald-500/30 transition">
                ENTER ↵
            </button>
        </form>

        {{-- Quick Command Buttons --}}
        <div class="mt-4 flex flex-wrap items-center gap-2.5">
            <button type="button" @click="triggerAgentAction('guard_patrol')"
                    class="rounded-xl bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/30 text-amber-300 px-3.5 py-2 text-xs font-semibold transition active:scale-95 flex items-center gap-1.5">
                <span>🛡️</span>
                <span>สั่งลุงยามเดินตรวจรอบเซิร์ฟเวอร์</span>
            </button>
            <button type="button" @click="triggerAgentAction('repair_cache')"
                    class="rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-300 px-3.5 py-2 text-xs font-semibold transition active:scale-95 flex items-center gap-1.5">
                <span>🔧</span>
                <span>สั่งช่างเคาะสนิมเคลียร์แคช</span>
            </button>
            <button type="button" @click="triggerAgentAction('hr_evaluate')"
                    class="rounded-xl bg-rose-500/15 hover:bg-rose-500/25 border border-rose-500/30 text-rose-300 px-3.5 py-2 text-xs font-semibold transition active:scale-95 flex items-center gap-1.5">
                <span>📋</span>
                <span>สั่ง HR-QA ตรวจประเมินผลงาน AI</span>
            </button>
            <button type="button" @click="triggerAgentAction('assemble')"
                    class="rounded-xl bg-indigo-500/20 hover:bg-indigo-500/30 border border-indigo-500/40 text-indigo-300 px-3.5 py-2 text-xs font-semibold transition active:scale-95 flex items-center gap-1.5">
                <span>⚡</span>
                <span>กดสัญญาณรวมพลศูนย์บัญชาการ</span>
            </button>
            <button type="button" @click="triggerAgentAction('bio_health')"
                    class="rounded-xl bg-cyan-500/15 hover:bg-cyan-500/25 border border-cyan-500/30 text-cyan-300 px-3.5 py-2 text-xs font-semibold transition active:scale-95 flex items-center gap-1.5">
                <span>🏃‍♂️</span>
                <span>สั่ง Bio-Hacker ดึงข้อมูลสุขภาพ Watch 7</span>
            </button>
        </div>
    </div>

    {{-- AGENT DOSSIER CARDS (Stats & Levels) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($agents as $agent)
            <div class="rounded-2xl border border-slate-800 bg-[#141720]/90 p-5 shadow-sm backdrop-blur flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="text-2xl">{{ $agent['avatar'] === 'guard' ? '👮‍♂️' : ($agent['avatar'] === 'mechanic' ? '🧑‍🔧' : ($agent['avatar'] === 'hr' ? '👩‍💼' : ($agent['avatar'] === 'biohacker' ? '🏃‍♂️' : '🧑‍💻'))) }}</span>
                            <div>
                                <h4 class="font-bold text-slate-100 text-sm">{{ $agent['name'] }}</h4>
                                <span class="text-xs text-slate-400">{{ $agent['role'] }}</span>
                            </div>
                        </div>
                        <span class="rounded bg-indigo-500/20 px-2 py-0.5 text-xs font-bold text-indigo-300 border border-indigo-500/30 font-pixel text-[10px]">
                            {{ $agent['level'] }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-300 leading-relaxed mb-3">
                        {{ $agent['duty'] }}
                    </p>

                    {{-- Stats Bars --}}
                    <div class="space-y-1.5 text-[11px] mb-3">
                        @foreach ($agent['stats'] as $statKey => $statVal)
                            <div class="flex items-center justify-between text-slate-400">
                                <span class="uppercase font-mono">{{ $statKey }}</span>
                                <span class="font-bold text-slate-200">{{ $statVal }}%</span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-emerald-400"
                                     style="width: {{ $statVal }}%"></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="pt-2.5 border-t border-slate-800/80 text-[11px] text-slate-400 flex items-center gap-1.5">
                    <span class="text-emerald-400">●</span>
                    <span class="truncate">{{ $agent['last_action'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

</div>

<script>
function commandCenter() {
    return {
        clock: '00:00:00',
        soundOn: true,
        activeStation: null,
        cliInput: '',
        audioCtx: null,
        logs: [
            '[' + new Date().toLocaleTimeString('th-TH') + '] [SYSTEM] ยินดีต้อนรับสู่ AI COMMAND CENTER ROOM 8-BIT',
            '[' + new Date().toLocaleTimeString('th-TH') + '] [ลุงยาม] สแกนพอร์ตเซิร์ฟเวอร์ trimongkol.com... ปลอดภัย 100% ไร้รอยเจาะ',
            '[' + new Date().toLocaleTimeString('th-TH') + '] [ช่างซ่อม] เซอร์วิส Remote Desktop และแคชทำงานปกติ CPU/RAM เสถียร',
            '[' + new Date().toLocaleTimeString('th-TH') + '] [HR-QA] ตรวจสอบโค้ดและระบบ Second Brain, Couple Space, Goals: เกรด A+',
            '[' + new Date().toLocaleTimeString('th-TH') + '] [Antigravity] ทุกเอเจนต์ประจำตำแหน่ง พร้อมรับคำสั่งจากมึงแล้วเว้ย!'
        ],

        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
        },

        updateClock() {
            const d = new Date();
            this.clock = d.toLocaleTimeString('th-TH');
        },

        toggleSound() {
            this.soundOn = !this.soundOn;
            if (this.soundOn) {
                this.playChiptune('cmd');
            }
        },

        playChiptune(type = 'cmd') {
            if (!this.soundOn) return;
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                if (!this.audioCtx) {
                    this.audioCtx = new AudioCtx();
                }
                if (this.audioCtx.state === 'suspended') {
                    this.audioCtx.resume();
                }

                const ctx = this.audioCtx;
                const now = ctx.currentTime;

                if (type === 'guard') {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(160, now);
                    osc.frequency.exponentialRampToValueAtTime(320, now + 0.18);
                    gain.gain.setValueAtTime(0.2, now);
                    gain.gain.linearRampToValueAtTime(0.01, now + 0.25);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.25);
                } else if (type === 'repair') {
                    [0, 0.09].forEach((delay, i) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'square';
                        osc.frequency.setValueAtTime(i === 0 ? 440 : 660, now + delay);
                        gain.gain.setValueAtTime(0.18, now + delay);
                        gain.gain.linearRampToValueAtTime(0.01, now + delay + 0.07);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(now + delay);
                        osc.stop(now + delay + 0.08);
                    });
                } else if (type === 'hr') {
                    [0, 0.08, 0.16].forEach((delay, i) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'triangle';
                        const freqs = [523.25, 659.25, 1046.50];
                        osc.frequency.setValueAtTime(freqs[i], now + delay);
                        gain.gain.setValueAtTime(0.25, now + delay);
                        gain.gain.linearRampToValueAtTime(0.01, now + delay + 0.12);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(now + delay);
                        osc.stop(now + delay + 0.13);
                    });
                } else if (type === 'assemble') {
                    [0, 0.07, 0.14, 0.21].forEach((delay, i) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'square';
                        const freqs = [261.63, 392.00, 523.25, 659.25];
                        osc.frequency.setValueAtTime(freqs[i], now + delay);
                        gain.gain.setValueAtTime(0.2, now + delay);
                        gain.gain.linearRampToValueAtTime(0.01, now + delay + 0.12);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(now + delay);
                        osc.stop(now + delay + 0.14);
                    });
                } else if (type === 'bio_health') {
                    // Upbeat energetic heartbeat blip (high energy)
                    [0, 0.12].forEach((delay, i) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'triangle';
                        osc.frequency.setValueAtTime(i === 0 ? 350 : 520, now + delay);
                        gain.gain.setValueAtTime(0.25, now + delay);
                        gain.gain.linearRampToValueAtTime(0.01, now + delay + 0.1);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(now + delay);
                        osc.stop(now + delay + 0.11);
                    });
                } else {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'square';
                    osc.frequency.setValueAtTime(880, now);
                    gain.gain.setValueAtTime(0.15, now);
                    gain.gain.linearRampToValueAtTime(0.01, now + 0.08);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.08);
                }
            } catch (e) {
                console.warn('Web Audio error:', e);
            }
        },

        clearLogs() {
            this.playChiptune('cmd');
            this.logs = ['[' + new Date().toLocaleTimeString('th-TH') + '] [SYSTEM] เคลียร์บันทึกหน้าจอเรียบร้อย'];
        },

        async triggerAgentAction(actionType) {
            this.activeStation = actionType;
            if (actionType === 'guard_patrol') this.playChiptune('guard');
            else if (actionType === 'repair_cache') this.playChiptune('repair');
            else if (actionType === 'hr_evaluate') this.playChiptune('hr');
            else if (actionType === 'bio_health') this.playChiptune('bio_health');
            else this.playChiptune('assemble');

            setTimeout(() => {
                if (this.activeStation === actionType) this.activeStation = null;
            }, 1800);

            try {
                const res = await fetch('{{ route("portfolio.command-center.ping") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ action: actionType })
                });
                const data = await res.json();
                if (data.success) {
                    this.logs.unshift(data.log);
                    if (this.logs.length > 25) {
                        this.logs.pop();
                    }
                }
            } catch (err) {
                console.error(err);
            }
        },

        async submitCliCommand() {
            const raw = (this.cliInput || '').trim();
            if (!raw) return;
            this.cliInput = '';

            if (raw.toLowerCase() === 'clear' || raw.toLowerCase() === 'cls') {
                this.clearLogs();
                return;
            }

            this.playChiptune('cmd');
            this.logs.unshift('[' + new Date().toLocaleTimeString('th-TH') + '] trimongkol@ai-hq:~$ ' + raw);

            try {
                const res = await fetch('{{ route("portfolio.command-center.ping") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ command: raw })
                });
                const data = await res.json();
                if (data.success) {
                    this.logs.unshift(data.log);
                    if (data.agent === 'ลุงยาม') this.playChiptune('guard');
                    else if (data.agent === 'ช่างซ่อม') this.playChiptune('repair');
                    else if (data.agent === 'HR-QA') this.playChiptune('hr');
                    else if (data.agent === 'Bio-Hacker') this.playChiptune('bio_health');
                    else if (data.agent === 'Team Lead') this.playChiptune('assemble');
                    if (this.logs.length > 25) {
                        this.logs.pop();
                    }
                }
            } catch (err) {
                console.error(err);
            }
        }
    };
}
</script>

</x-portfolio-layout>
