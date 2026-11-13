<x-portfolio-layout>

@php
    $formatThaiDate = function ($date): ?string {
        if (!$date) return null;
        $thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $c = \Carbon\Carbon::parse($date);
        return $c->day . ' ' . $thaiMonths[$c->month] . ' ' . ($c->year + 543);
    };
@endphp

<div x-data="coupleSpace()" class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8 space-y-6">

    {{-- Flash status notification --}}
    @if (session('status'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="flex items-center justify-between rounded-2xl border border-pink-500/30 bg-pink-500/10 px-4 py-3 text-sm text-pink-300 backdrop-blur">
            <div class="flex items-center gap-2">
                <span class="text-base">💕</span>
                <span>{{ session('status') }}</span>
            </div>
            <button @click="show = false" class="text-pink-400 hover:text-pink-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- Top Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-pink-500/20 to-rose-500/20 text-pink-400 border border-pink-500/30 shadow-inner">
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold tracking-tight text-slate-100">Couple Space</h1>
                        <span class="rounded-lg bg-pink-500/15 px-2 py-0.5 text-[11px] font-semibold text-pink-300 border border-pink-500/30">
                            พื้นที่เราสองคน 💕
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-400">
                        จดบันทึกไอเดียเดต ร้านอร่อยที่อยากลอง ทริปในฝัน และกระปุกเงินเก็บกองกลาง
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button type="button" @click="openAddModal()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-pink-500 to-rose-500 hover:from-pink-400 hover:to-rose-400 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-md shadow-pink-950/40 transition active:scale-95">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>เพิ่มรายการใหม่</span>
            </button>
        </div>
    </div>

    {{-- Scoreboard Cards --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
        {{-- Card 1: Completed Memories --}}
        <div class="rounded-2xl border border-pink-500/20 bg-[#141720]/90 p-4 shadow-sm backdrop-blur">
            <div class="flex items-center justify-between text-xs text-pink-400 font-semibold mb-1">
                <span>ความทรงจำที่ทำแล้ว</span>
                <span>💕</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl sm:text-3xl font-extrabold text-slate-100">{{ $doneCount }}</span>
                <span class="text-xs text-slate-400">/ {{ $totalCount }} กิจกรรม</span>
            </div>
            <div class="mt-2.5 h-1.5 w-full rounded-full bg-slate-800 overflow-hidden">
                <div class="h-full rounded-full bg-pink-400 transition-all duration-500"
                     style="width: {{ $totalCount > 0 ? round(($doneCount / $totalCount) * 100) : 0 }}%"></div>
            </div>
        </div>

        {{-- Card 2: Food & Cafes --}}
        <div class="rounded-2xl border border-amber-500/20 bg-[#141720]/90 p-4 shadow-sm backdrop-blur">
            <div class="flex items-center justify-between text-xs text-amber-400 font-semibold mb-1">
                <span>ร้านอาหาร & คาเฟ่</span>
                <span>🍽️</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-bold text-slate-100">{{ $foodCount }}</span>
                <span class="text-xs text-slate-400">ร้านในลิสต์</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-2 truncate">นัดไปกินด้วยกันวันหยุด</p>
        </div>

        {{-- Card 3: Trips --}}
        <div class="rounded-2xl border border-emerald-500/20 bg-[#141720]/90 p-4 shadow-sm backdrop-blur">
            <div class="flex items-center justify-between text-xs text-emerald-400 font-semibold mb-1">
                <span>ทริปเที่ยวคู่</span>
                <span>🏍️</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-bold text-slate-100">{{ $tripCount }}</span>
                <span class="text-xs text-slate-400">ทริปในฝัน</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-2 truncate">วางแพลนออกเดินทาง</p>
        </div>

        {{-- Card 4: Shared Savings --}}
        <div class="rounded-2xl border border-cyan-500/20 bg-[#141720]/90 p-4 shadow-sm backdrop-blur">
            <div class="flex items-center justify-between text-xs text-cyan-400 font-semibold mb-1">
                <span>กระปุกเงินเก็บกองกลาง</span>
                <span>💰</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-xl sm:text-2xl font-bold text-cyan-300">{{ number_format($totalActualSavings) }}</span>
                <span class="text-[11px] text-slate-400">/ {{ number_format($totalTargetSavings) }} ฿</span>
            </div>
            <div class="mt-2.5 h-1.5 w-full rounded-full bg-slate-800 overflow-hidden">
                <div class="h-full rounded-full bg-cyan-400 transition-all duration-500"
                     style="width: {{ $totalTargetSavings > 0 ? round(($totalActualSavings / $totalTargetSavings) * 100) : 0 }}%"></div>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-none border-b border-slate-800 pb-3">
        <a href="{{ route('portfolio.couple.index') }}"
           class="whitespace-nowrap rounded-xl px-3.5 py-1.5 text-xs font-semibold transition border {{ $activeType === 'all' ? 'bg-pink-500/20 text-pink-300 border-pink-500/40' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
            ทั้งหมด ({{ $totalCount }})
        </a>
        <a href="{{ route('portfolio.couple.index', ['type' => 'food']) }}"
           class="whitespace-nowrap rounded-xl px-3.5 py-1.5 text-xs font-semibold transition border {{ $activeType === 'food' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
            🍽️ ร้านอาหาร ({{ $foodCount }})
        </a>
        <a href="{{ route('portfolio.couple.index', ['type' => 'trip']) }}"
           class="whitespace-nowrap rounded-xl px-3.5 py-1.5 text-xs font-semibold transition border {{ $activeType === 'trip' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
            🏍️ ทริปเที่ยว ({{ $tripCount }})
        </a>
        <a href="{{ route('portfolio.couple.index', ['type' => 'wishlist']) }}"
           class="whitespace-nowrap rounded-xl px-3.5 py-1.5 text-xs font-semibold transition border {{ $activeType === 'wishlist' ? 'bg-rose-500/20 text-rose-300 border-rose-500/40' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
            🎁 Wishlist ({{ $wishlistCount }})
        </a>
        <a href="{{ route('portfolio.couple.index', ['type' => 'savings']) }}"
           class="whitespace-nowrap rounded-xl px-3.5 py-1.5 text-xs font-semibold transition border {{ $activeType === 'savings' ? 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
            💰 กระปุกเงิน ({{ $savingsCount }})
        </a>
        <a href="{{ route('portfolio.couple.index', ['type' => 'movie']) }}"
           class="whitespace-nowrap rounded-xl px-3.5 py-1.5 text-xs font-semibold transition border {{ $activeType === 'movie' ? 'bg-purple-500/20 text-purple-300 border-purple-500/40' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
            🎬 หนัง/ซีรีส์ ({{ $movieCount }})
        </a>
        <a href="{{ route('portfolio.couple.index', ['type' => 'done']) }}"
           class="whitespace-nowrap rounded-xl px-3.5 py-1.5 text-xs font-semibold transition border {{ $activeType === 'done' ? 'bg-pink-500/25 text-pink-300 border-pink-500/50' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
            ✨ ทำแล้ว ({{ $doneCount }})
        </a>
    </div>

    {{-- Cards Grid --}}
    @if ($items->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-800 bg-[#141720]/50 p-12 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-pink-500/10 text-pink-400 border border-pink-500/20 mb-4">
                <span class="text-2xl">💕</span>
            </div>
            <h3 class="text-lg font-bold text-slate-200">ยังไม่มีรายการในหมวดนี้</h3>
            <p class="mt-1 text-sm text-slate-400 max-w-sm mx-auto">
                เพิ่มสิ่งของที่อยากได้ ร้านอาหารที่อยากลอง หรือทริปที่อยากไปเที่ยวกับแฟนได้เลยครับ
            </p>
            <div class="mt-6 flex justify-center">
                <button type="button" @click="openAddModal()"
                        class="inline-flex items-center gap-2 rounded-xl bg-pink-500 hover:bg-pink-400 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition active:scale-95">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span>เพิ่มรายการแรก</span>
                </button>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($items as $item)
                <div class="group relative flex flex-col justify-between rounded-2xl border bg-[#141720]/90 p-5 shadow-sm backdrop-blur transition-all duration-200 {{ $item->is_done ? 'border-pink-500/25 bg-[#141720]/50 opacity-80' : 'border-slate-800 hover:border-slate-700' }}">

                    <div>
                        {{-- Top Header Row --}}
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="rounded-lg border px-2.5 py-0.5 text-[10px] font-bold {{ $item->typeBadgeColor() }}">
                                {{ $item->typeLabel() }}
                            </span>

                            <div class="flex items-center gap-1.5">
                                {{-- Done toggle button --}}
                                <button type="button" @click="toggleDone({{ $item->id }})"
                                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold transition {{ $item->is_done ? 'bg-pink-500/20 text-pink-300 border border-pink-500/30' : 'bg-slate-800 text-slate-400 hover:text-pink-300 border border-slate-700' }}"
                                        title="{{ $item->is_done ? 'ทำแล้ว 💕' : 'ยังไม่ได้ทำ' }}">
                                    <span>{{ $item->is_done ? '💕 สำเร็จแล้ว' : '🤍 รอทำ' }}</span>
                                </button>

                                {{-- Delete button --}}
                                <form method="POST" action="{{ route('portfolio.couple.destroy', $item) }}"
                                      onsubmit="return confirm('ยืนยันลบรายการนี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg p-1 text-slate-500 hover:text-rose-400 transition" title="ลบ">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Title --}}
                        <h3 class="text-base font-bold {{ $item->is_done ? 'line-through text-slate-400' : 'text-slate-100 group-hover:text-pink-300' }} transition-colors">
                            {{ $item->title }}
                        </h3>

                        {{-- Location if present --}}
                        @if ($item->location)
                            <div class="mt-1 flex items-center gap-1 text-xs text-amber-400">
                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                <span>{{ $item->location }}</span>
                            </div>
                        @endif

                        {{-- Description --}}
                        @if ($item->description)
                            <p class="mt-1.5 text-xs text-slate-400 leading-relaxed">
                                {{ $item->description }}
                            </p>
                        @endif

                        {{-- Savings progress if savings type --}}
                        @if ($item->type === 'savings' || $item->target_amount > 0)
                            <div class="mt-3 rounded-xl bg-slate-800/60 p-3 border border-slate-700/60">
                                <div class="flex items-center justify-between text-xs font-semibold mb-1">
                                    <span class="text-slate-300">ยอดเก็บแล้ว: <span class="text-cyan-400 font-bold">{{ number_format($item->saved_amount) }}</span> ฿</span>
                                    <span class="text-slate-400">เป้า: {{ number_format($item->target_amount) }} ฿ ({{ $item->savingsPercent() }}%)</span>
                                </div>
                                <div class="h-2 w-full rounded-full bg-slate-900 overflow-hidden">
                                    <div class="h-full rounded-full bg-cyan-400 transition-all duration-300"
                                         style="width: {{ $item->savingsPercent() }}%"></div>
                                </div>
                                {{-- Quick Add Money buttons --}}
                                <div class="mt-2 flex items-center gap-1.5">
                                    <button type="button" @click="addSavings({{ $item->id }}, {{ (float)$item->saved_amount + 500 }})"
                                            class="rounded bg-slate-700 hover:bg-cyan-500/20 hover:text-cyan-300 px-2 py-0.5 text-[10px] font-semibold text-slate-300 transition">
                                        +500 ฿
                                    </button>
                                    <button type="button" @click="addSavings({{ $item->id }}, {{ (float)$item->saved_amount + 1000 }})"
                                            class="rounded bg-slate-700 hover:bg-cyan-500/20 hover:text-cyan-300 px-2 py-0.5 text-[10px] font-semibold text-slate-300 transition">
                                        +1,000 ฿
                                    </button>
                                    <button type="button" @click="addSavings({{ $item->id }}, {{ (float)$item->target_amount }})"
                                            class="rounded bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 px-2 py-0.5 text-[10px] font-bold transition">
                                        ครบเป้า! 🎉
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Footer info --}}
                    <div class="mt-3 pt-2.5 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500">
                        @if ($item->target_date)
                            <span>แพลนไว้: {{ $formatThaiDate($item->target_date) }}</span>
                        @else
                            <span>บันทึกเมื่อ: {{ $item->created_at->diffForHumans() }}</span>
                        @endif

                        @if ($item->is_done && $item->done_at)
                            <span class="text-pink-400 font-medium">ไปทำมาเมื่อ {{ $formatThaiDate($item->done_at) }}</span>
                        @endif
                    </div>

                </div>
            @endforeach
        </div>
    @endif

    {{-- Add Modal --}}
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
         @keydown.escape.window="closeModal()">
        <div class="relative w-full max-w-lg rounded-3xl border border-slate-800 bg-[#141720] p-6 shadow-2xl transition-all"
             @click.outside="closeModal()">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <h3 class="text-lg font-bold text-slate-100">เพิ่มรายการใน Couple Space 💕</h3>
                <button type="button" @click="closeModal()" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-200 hover:bg-slate-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('portfolio.couple.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf

                {{-- Type Selection --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">หมวดหมู่ <span class="text-pink-400">*</span></label>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <label class="cursor-pointer rounded-xl border p-2 text-center transition flex flex-col items-center justify-center"
                               :class="form.type === 'food' ? 'border-amber-500 bg-amber-500/15 text-amber-300' : 'border-slate-700 bg-slate-800/60 text-slate-400'">
                            <input type="radio" name="type" value="food" x-model="form.type" class="sr-only">
                            <span class="text-xs font-bold">🍽️ ร้านอาหาร</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border p-2 text-center transition flex flex-col items-center justify-center"
                               :class="form.type === 'trip' ? 'border-emerald-500 bg-emerald-500/15 text-emerald-300' : 'border-slate-700 bg-slate-800/60 text-slate-400'">
                            <input type="radio" name="type" value="trip" x-model="form.type" class="sr-only">
                            <span class="text-xs font-bold">🏍️ ทริปเที่ยว</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border p-2 text-center transition flex flex-col items-center justify-center"
                               :class="form.type === 'wishlist' ? 'border-rose-500 bg-rose-500/15 text-rose-300' : 'border-slate-700 bg-slate-800/60 text-slate-400'">
                            <input type="radio" name="type" value="wishlist" x-model="form.type" class="sr-only">
                            <span class="text-xs font-bold">🎁 Wishlist</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border p-2 text-center transition flex flex-col items-center justify-center"
                               :class="form.type === 'date_idea' ? 'border-pink-500 bg-pink-500/15 text-pink-300' : 'border-slate-700 bg-slate-800/60 text-slate-400'">
                            <input type="radio" name="type" value="date_idea" x-model="form.type" class="sr-only">
                            <span class="text-xs font-bold">✨ ไอเดียเดต</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border p-2 text-center transition flex flex-col items-center justify-center"
                               :class="form.type === 'savings' ? 'border-cyan-500 bg-cyan-500/15 text-cyan-300' : 'border-slate-700 bg-slate-800/60 text-slate-400'">
                            <input type="radio" name="type" value="savings" x-model="form.type" class="sr-only">
                            <span class="text-xs font-bold">💰 กระปุกเงิน</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border p-2 text-center transition flex flex-col items-center justify-center"
                               :class="form.type === 'movie' ? 'border-purple-500 bg-purple-500/15 text-purple-300' : 'border-slate-700 bg-slate-800/60 text-slate-400'">
                            <input type="radio" name="type" value="movie" x-model="form.type" class="sr-only">
                            <span class="text-xs font-bold">🎬 หนัง/ซีรีส์</span>
                        </label>
                    </div>
                </div>

                {{-- Title --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">ชื่อกิจกรรม / สิ่งของ / ร้าน <span class="text-pink-400">*</span></label>
                    <input type="text" name="title" x-model="form.title" required
                           placeholder="เช่น ชาบูหม่าล่าสายพาน, ทริปนอนดูดาวเขาค้อ"
                           class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-sm text-slate-100 placeholder-slate-500 focus:border-pink-500 focus:ring-pink-500">
                </div>

                {{-- Location & Target Date --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">สถานที่ / พิกัด</label>
                        <input type="text" name="location" x-model="form.location"
                               placeholder="เช่น สยาม, เชียงใหม่"
                               class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-pink-500 focus:ring-pink-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">วันที่แพลนจะไป (ถ้ามี)</label>
                        <input type="date" name="target_date" x-model="form.target_date"
                               class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-pink-500 focus:ring-pink-500">
                    </div>
                </div>

                {{-- Amounts if savings or wishlist --}}
                <div x-show="form.type === 'savings' || form.type === 'wishlist'" class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">เป้าหมายเงิน (บาท)</label>
                        <input type="number" step="0.01" name="target_amount" x-model="form.target_amount"
                               placeholder="เช่น 10000"
                               class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-pink-500 focus:ring-pink-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">หยอดเงินแล้ว (บาท)</label>
                        <input type="number" step="0.01" name="saved_amount" x-model="form.saved_amount"
                               placeholder="เช่น 2000"
                               class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-pink-500 focus:ring-pink-500">
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">รายละเอียดเพิ่มเติม</label>
                    <textarea name="description" x-model="form.description" rows="2"
                              placeholder="เช่น เมนูที่อยากกิน, สิ่งที่ต้องเตรียม"
                              class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 placeholder-slate-500 focus:border-pink-500 focus:ring-pink-500"></textarea>
                </div>

                {{-- Submit Buttons --}}
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" @click="closeModal()"
                            class="rounded-xl border border-slate-700 px-4 py-2 text-xs sm:text-sm font-semibold text-slate-300 hover:bg-slate-800">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-pink-500 hover:bg-pink-400 px-5 py-2 text-xs sm:text-sm font-semibold text-white shadow-md transition">
                        บันทึกรายการ
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function coupleSpace() {
    return {
        modalOpen: false,
        form: {
            type: 'food',
            title: '',
            location: '',
            target_date: '',
            target_amount: '',
            saved_amount: '',
            description: ''
        },

        openAddModal() {
            this.form = {
                type: 'food',
                title: '',
                location: '',
                target_date: '',
                target_amount: '',
                saved_amount: '',
                description: ''
            };
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
        },

        async toggleDone(itemId) {
            try {
                const res = await fetch(`/portfolio/couple/${itemId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (err) {
                console.error(err);
                window.location.reload();
            }
        },

        async addSavings(itemId, newAmount) {
            try {
                const res = await fetch(`/portfolio/couple/${itemId}/savings`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ saved_amount: newAmount })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (err) {
                console.error(err);
            }
        }
    };
}
</script>

</x-portfolio-layout>
