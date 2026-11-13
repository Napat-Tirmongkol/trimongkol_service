<x-portfolio-layout>

@php
    $typeBadgeColors = [
        'inbox'    => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
        'project'  => 'bg-rose-500/15 text-rose-400 border-rose-500/30',
        'area'     => 'bg-blue-500/15 text-blue-400 border-blue-500/30',
        'resource' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
        'archive'  => 'bg-slate-500/15 text-slate-400 border-slate-500/30',
    ];

    $cardBorderColors = [
        'amber'   => 'hover:border-amber-500/40 border-slate-800',
        'rose'    => 'hover:border-rose-500/40 border-slate-800',
        'blue'    => 'hover:border-blue-500/40 border-slate-800',
        'emerald' => 'hover:border-emerald-500/40 border-slate-800',
        'indigo'  => 'hover:border-indigo-500/40 border-slate-800',
        'slate'   => 'hover:border-slate-700 border-slate-800',
    ];
@endphp

<div x-data="secondBrain()" class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8 space-y-6">

    {{-- Flash status notification --}}
    @if (session('status'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="flex items-center justify-between rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300 backdrop-blur">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-400 hover:text-emerald-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- Top Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500/20 to-indigo-500/20 text-amber-300 border border-amber-500/30 shadow-inner">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold tracking-tight text-slate-100">Second Brain</h1>
                        <span class="rounded-lg bg-amber-500/15 px-2 py-0.5 text-[11px] font-semibold text-amber-300 border border-amber-500/30">
                            P.A.R.A. Framework
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-400">
                        คลังสมองที่สอง: บันทึกความคิด โปรเจกต์ คลังความรู้ และมิติชีวิตไว้ในที่เดียว
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Search Bar --}}
            <form method="GET" action="{{ route('portfolio.brain.index') }}" class="relative flex items-center">
                <input type="hidden" name="type" value="{{ $activeType }}">
                <input type="hidden" name="category" value="{{ $activeCategory }}">
                <input type="text" name="q" value="{{ $searchQuery }}" placeholder="ค้นหาบันทึกหรือ #แท็ก..."
                       class="w-44 sm:w-56 rounded-xl border border-slate-700 bg-slate-800/90 pl-8 pr-3 py-1.5 text-xs text-slate-100 placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500">
                <svg class="absolute left-2.5 h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </form>

            {{-- New Note Button --}}
            <button type="button" @click="openAddModal()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 px-4 py-2 text-xs sm:text-sm font-semibold text-slate-950 shadow-md transition active:scale-95">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>จดโน้ตใหม่</span>
            </button>
        </div>
    </div>

    {{-- Quick Capture Bar (Type & Hit Enter) --}}
    <form method="POST" action="{{ route('portfolio.brain.store') }}"
          class="rounded-2xl border border-slate-800 bg-[#141720]/90 p-2.5 shadow-sm backdrop-blur flex items-center gap-2">
        @csrf
        <input type="hidden" name="type" value="inbox">
        <input type="hidden" name="category" value="idea">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-400">
            <span class="text-sm">⚡</span>
        </div>
        <input type="text" name="title" required
               placeholder="Quick Capture: พิมพ์ไอเดีย ความคิด หรือลิงก์ด่วน... (กด Enter เพื่อเซฟลง Inbox ทันที)"
               class="flex-1 bg-transparent border-0 px-2 py-1 text-xs sm:text-sm text-slate-100 placeholder-slate-500 focus:ring-0 focus:outline-none">
        <button type="submit"
                class="shrink-0 rounded-xl bg-slate-800 hover:bg-amber-500/20 hover:text-amber-300 text-slate-300 px-3 py-1.5 text-xs font-semibold border border-slate-700 transition">
            เซฟด่วน
        </button>
    </form>

    {{-- PARA Scoreboard Summary Cards --}}
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5 sm:gap-3">
        {{-- Inbox --}}
        <a href="{{ route('portfolio.brain.index', ['type' => 'inbox']) }}"
           class="rounded-xl border p-3 transition {{ $activeType === 'inbox' ? 'border-amber-500/50 bg-amber-500/10' : 'border-slate-800 bg-[#141720]/80 hover:border-slate-700' }}">
            <div class="flex items-center justify-between text-xs text-amber-400 font-semibold mb-1">
                <span>⚡ Inbox</span>
                <span class="rounded-md bg-amber-500/20 px-1.5 py-0.5 text-[10px]">{{ $inboxCount }}</span>
            </div>
            <p class="text-[11px] text-slate-400 truncate">ไอเดียสดใหม่ยังไม่จัดหมวด</p>
        </a>

        {{-- Projects --}}
        <a href="{{ route('portfolio.brain.index', ['type' => 'project']) }}"
           class="rounded-xl border p-3 transition {{ $activeType === 'project' ? 'border-rose-500/50 bg-rose-500/10' : 'border-slate-800 bg-[#141720]/80 hover:border-slate-700' }}">
            <div class="flex items-center justify-between text-xs text-rose-400 font-semibold mb-1">
                <span>🎯 Projects</span>
                <span class="rounded-md bg-rose-500/20 px-1.5 py-0.5 text-[10px]">{{ $projectCount }}</span>
            </div>
            <p class="text-[11px] text-slate-400 truncate">งานที่มีเป้าหมาย & กำหนดการ</p>
        </a>

        {{-- Areas --}}
        <a href="{{ route('portfolio.brain.index', ['type' => 'area']) }}"
           class="rounded-xl border p-3 transition {{ $activeType === 'area' ? 'border-blue-500/50 bg-blue-500/10' : 'border-slate-800 bg-[#141720]/80 hover:border-slate-700' }}">
            <div class="flex items-center justify-between text-xs text-blue-400 font-semibold mb-1">
                <span>🌐 Areas</span>
                <span class="rounded-md bg-blue-500/20 px-1.5 py-0.5 text-[10px]">{{ $areaCount }}</span>
            </div>
            <p class="text-[11px] text-slate-400 truncate">มิติชีวิตที่ดูแลระยะยาว</p>
        </a>

        {{-- Resources --}}
        <a href="{{ route('portfolio.brain.index', ['type' => 'resource']) }}"
           class="rounded-xl border p-3 transition {{ $activeType === 'resource' ? 'border-emerald-500/50 bg-emerald-500/10' : 'border-slate-800 bg-[#141720]/80 hover:border-slate-700' }}">
            <div class="flex items-center justify-between text-xs text-emerald-400 font-semibold mb-1">
                <span>📚 Resources</span>
                <span class="rounded-md bg-emerald-500/20 px-1.5 py-0.5 text-[10px]">{{ $resourceCount }}</span>
            </div>
            <p class="text-[11px] text-slate-400 truncate">คลังความรู้ เทคนิค & คำสั่ง</p>
        </a>

        {{-- Archives --}}
        <a href="{{ route('portfolio.brain.index', ['type' => 'archive']) }}"
           class="rounded-xl border p-3 transition col-span-2 sm:col-span-1 {{ $activeType === 'archive' ? 'border-slate-500/50 bg-slate-500/10' : 'border-slate-800 bg-[#141720]/80 hover:border-slate-700' }}">
            <div class="flex items-center justify-between text-xs text-slate-300 font-semibold mb-1">
                <span>🗄️ Archives</span>
                <span class="rounded-md bg-slate-700 px-1.5 py-0.5 text-[10px]">{{ $archiveCount }}</span>
            </div>
            <p class="text-[11px] text-slate-400 truncate">จัดเก็บไว้อ้างอิง</p>
        </a>
    </div>

    {{-- Filter Toolbar: Type Tabs & Tag Chips --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-3">
        <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-none pb-1 sm:pb-0">
            <a href="{{ route('portfolio.brain.index') }}"
               class="whitespace-nowrap rounded-xl px-3 py-1.5 text-xs font-semibold transition border {{ $activeType === 'all' && empty($activeTag) ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
                โน้ตทั้งหมด ({{ $totalNotes }})
            </a>
            @foreach (['inbox' => '⚡ Inbox', 'project' => '🎯 Projects', 'area' => '🌐 Areas', 'resource' => '📚 Resources', 'archive' => '🗄️ Archives'] as $tKey => $tLabel)
                <a href="{{ route('portfolio.brain.index', ['type' => $tKey]) }}"
                   class="whitespace-nowrap rounded-xl px-3 py-1.5 text-xs font-semibold transition border {{ $activeType === $tKey ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
                    {{ $tLabel }}
                </a>
            @endforeach
            <a href="{{ route('portfolio.persona.index') }}"
               class="whitespace-nowrap rounded-xl px-3 py-1.5 text-xs font-semibold transition border border-indigo-500/40 bg-indigo-500/15 text-indigo-300 hover:bg-indigo-500/25 flex items-center gap-1.5">
                <span>👤</span>
                <span>Personal Profile (นิสัย & ตัวตน)</span>
            </a>
        </div>

        {{-- Popular Tag Filter Pills --}}
        @if (!empty($tagsList))
            <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-none">
                <span class="text-[11px] text-slate-500 whitespace-nowrap">แท็ก:</span>
                @foreach (array_slice($tagsList, 0, 5, true) as $tag => $count)
                    <a href="{{ route('portfolio.brain.index', ['tag' => ltrim($tag, '#')]) }}"
                       class="rounded-lg px-2 py-0.5 text-[11px] font-medium transition {{ $activeTag === ltrim($tag, '#') ? 'bg-indigo-500 text-white' : 'bg-slate-800 text-slate-400 hover:text-slate-200' }}">
                        {{ $tag }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Notes Grid --}}
    @if ($allNotes->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-800 bg-[#141720]/50 p-12 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20 mb-4">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-200">ยังไม่มีบันทึกในหมวดนี้</h3>
            <p class="mt-1 text-sm text-slate-400 max-w-sm mx-auto">
                เริ่มต้นเขียนไอเดีย วัตถุดิบความรู้ หรือสิ่งที่ต้องทำลงใน Second Brain ได้เลยครับ
            </p>
            <div class="mt-6 flex justify-center">
                <button type="button" @click="openAddModal()"
                        class="inline-flex items-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-md transition active:scale-95">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span>สร้างโน้ตฉบับแรก</span>
                </button>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($allNotes as $note)
                @php
                    $badgeClass = $typeBadgeColors[$note->type] ?? 'bg-slate-500/15 text-slate-300 border-slate-500/30';
                @endphp
                <div class="group relative flex flex-col justify-between rounded-2xl border bg-[#141720]/90 p-5 shadow-sm backdrop-blur transition-all duration-200 {{ $note->is_pinned ? 'border-amber-500/40 ring-1 ring-amber-500/20' : 'border-slate-800 hover:border-slate-700' }}">

                    {{-- Top Card Bar --}}
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2">
                                <span class="rounded-lg border px-2 py-0.5 text-[10px] font-bold {{ $badgeClass }}">
                                    {{ $note->typeLabel() }}
                                </span>
                                <span class="rounded-lg bg-slate-800/80 px-2 py-0.5 text-[10px] text-slate-400">
                                    {{ $note->categoryLabel() }}
                                </span>
                            </div>

                            {{-- Pin & Actions --}}
                            <div class="flex items-center gap-1">
                                <button type="button" @click="togglePin({{ $note->id }})"
                                        class="rounded-lg p-1 transition {{ $note->is_pinned ? 'text-amber-400 bg-amber-500/15' : 'text-slate-500 hover:text-slate-300' }}"
                                        title="{{ $note->is_pinned ? 'ปลดหมุด' : 'ปักหมุด' }}">
                                    <svg class="h-4 w-4" fill="{{ $note->is_pinned ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                                    </svg>
                                </button>
                                <button type="button" @click="openEditModal({{ json_encode($note) }})"
                                        class="rounded-lg p-1 text-slate-400 hover:text-slate-200 transition" title="แก้ไข">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('portfolio.brain.destroy', $note) }}"
                                      onsubmit="return confirm('ยืนยันลบโน้ตนี้หรือไม่?')">
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
                        <h3 class="text-base font-bold text-slate-100 group-hover:text-amber-300 transition-colors">
                            {{ $note->title }}
                        </h3>

                        {{-- Content --}}
                        @if ($note->content)
                            <div class="mt-2 text-xs sm:text-sm text-slate-300/90 whitespace-pre-line leading-relaxed">
                                {{ $note->content }}
                            </div>
                        @endif

                        {{-- URL link if present --}}
                        @if ($note->url)
                            <div class="mt-2.5">
                                <a href="{{ $note->url }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-400 hover:text-indigo-300 underline">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 0 0-5.656 0l-4 4a4 4 0 1 0 5.656 5.656l1.102-1.101m-.758-4.899a4 4 0 0 0 5.656 0l4-4a4 4 0 0 0-5.656-5.656l-1.1 1.1"/></svg>
                                    <span class="truncate max-w-xs">{{ $note->url }}</span>
                                </a>
                            </div>
                        @endif
                    </div>

                    {{-- Tags & Footer --}}
                    <div class="mt-4 pt-3 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if (is_array($note->tags))
                                @foreach ($note->tags as $t)
                                    <a href="{{ route('portfolio.brain.index', ['tag' => ltrim($t, '#')]) }}"
                                       class="rounded bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-amber-300 px-2 py-0.5 text-[10px] font-medium transition">
                                        {{ $t }}
                                    </a>
                                @endforeach
                            @endif
                        </div>
                        <span class="text-[10px] text-slate-500">
                            {{ $note->updated_at->diffForHumans() }}
                        </span>
                    </div>

                </div>
            @endforeach
        </div>
    @endif

    {{-- Add / Edit Modal --}}
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
         @keydown.escape.window="closeModal()">
        <div class="relative w-full max-w-lg rounded-3xl border border-slate-800 bg-[#141720] p-6 shadow-2xl transition-all"
             @click.outside="closeModal()">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <h3 class="text-lg font-bold text-slate-100" x-text="isEditMode ? 'แก้ไขโน้ต' : 'จดโน้ตใหม่ใน Second Brain'"></h3>
                <button type="button" @click="closeModal()" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-200 hover:bg-slate-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="formAction" method="POST" class="mt-4 space-y-4">
                @csrf
                <template x-if="isEditMode">
                    <input type="hidden" name="_method" value="PATCH">
                </template>

                {{-- PARA Type selection --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">หมวดหมู่ P.A.R.A. <span class="text-rose-400">*</span></label>
                    <div class="grid grid-cols-5 gap-1.5 text-center">
                        <label class="cursor-pointer rounded-xl border p-2 text-center transition flex flex-col items-center justify-center"
                               :class="form.type === 'inbox' ? 'border-amber-500 bg-amber-500/15 text-amber-300' : 'border-slate-700 bg-slate-800/60 text-slate-400 hover:border-slate-600'">
                            <input type="radio" name="type" value="inbox" x-model="form.type" class="sr-only">
                            <span class="text-xs font-bold">⚡ Inbox</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border p-2 text-center transition flex flex-col items-center justify-center"
                               :class="form.type === 'project' ? 'border-rose-500 bg-rose-500/15 text-rose-300' : 'border-slate-700 bg-slate-800/60 text-slate-400 hover:border-slate-600'">
                            <input type="radio" name="type" value="project" x-model="form.type" class="sr-only">
                            <span class="text-xs font-bold">🎯 Project</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border p-2 text-center transition flex flex-col items-center justify-center"
                               :class="form.type === 'area' ? 'border-blue-500 bg-blue-500/15 text-blue-300' : 'border-slate-700 bg-slate-800/60 text-slate-400 hover:border-slate-600'">
                            <input type="radio" name="type" value="area" x-model="form.type" class="sr-only">
                            <span class="text-xs font-bold">🌐 Area</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border p-2 text-center transition flex flex-col items-center justify-center"
                               :class="form.type === 'resource' ? 'border-emerald-500 bg-emerald-500/15 text-emerald-300' : 'border-slate-700 bg-slate-800/60 text-slate-400 hover:border-slate-600'">
                            <input type="radio" name="type" value="resource" x-model="form.type" class="sr-only">
                            <span class="text-xs font-bold">📚 Resource</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border p-2 text-center transition flex flex-col items-center justify-center"
                               :class="form.type === 'archive' ? 'border-slate-500 bg-slate-500/15 text-slate-300' : 'border-slate-700 bg-slate-800/60 text-slate-400 hover:border-slate-600'">
                            <input type="radio" name="type" value="archive" x-model="form.type" class="sr-only">
                            <span class="text-xs font-bold">🗄️ Archive</span>
                        </label>
                    </div>
                </div>

                {{-- Title --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">หัวข้อโน้ต / ไอเดีย <span class="text-rose-400">*</span></label>
                    <input type="text" name="title" x-model="form.title" required
                           placeholder="เช่น ไอเดียทริปน่าน, วิธีเซ็ต Cloudflare CDN"
                           class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-sm text-slate-100 placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500">
                </div>

                {{-- Content --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">เนื้อหา / บันทึกรายละเอียด</label>
                    <textarea name="content" x-model="form.content" rows="4"
                              placeholder="จดรายละเอียด ความรู้ ลิสต์ข้อความ หรือติด #แฮชแท็ก ในข้อความได้เลย"
                              class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500"></textarea>
                </div>

                {{-- Category & Tags --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">มิติเนื้อหา</label>
                        <select name="category" x-model="form.category"
                                class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3 py-2 text-xs sm:text-sm text-slate-100 focus:border-amber-500 focus:ring-amber-500">
                            <option value="tech">💻 เทคโนโลยี & โค้ด</option>
                            <option value="finance">💰 การเงิน & การลงทุน</option>
                            <option value="travel">🏍️ ท่องเที่ยว & มอเตอร์ไซค์</option>
                            <option value="health">🏃 สุขภาพ & กายภาพ</option>
                            <option value="work">💼 งาน & ธุรกิจ</option>
                            <option value="idea">💡 ไอเดียสร้างสรรค์</option>
                            <option value="general">📌 ทั่วไป</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">แท็ก (คั่นด้วยจุลภาค)</label>
                        <input type="text" name="tags" x-model="form.tags"
                               placeholder="#ai, #trip, #ideas"
                               class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3 py-2 text-xs sm:text-sm text-slate-100 focus:border-amber-500 focus:ring-amber-500">
                    </div>
                </div>

                {{-- Reference URL --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">ลิงก์อ้างอิงภายนอก (ถ้ามี)</label>
                    <input type="url" name="url" x-model="form.url"
                           placeholder="https://..."
                           class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-amber-500 focus:ring-amber-500">
                </div>

                {{-- Pin Checkbox --}}
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_pinned" id="modal_is_pinned" value="1"
                           x-model="form.is_pinned"
                           class="h-4 w-4 rounded border-slate-700 bg-slate-800 text-amber-500 focus:ring-amber-500">
                    <label for="modal_is_pinned" class="text-xs font-semibold text-slate-300 cursor-pointer">
                        📌 ปักหมุดโน้ตนี้ไว้บนสุด
                    </label>
                </div>

                {{-- Submit Buttons --}}
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" @click="closeModal()"
                            class="rounded-xl border border-slate-700 px-4 py-2 text-xs sm:text-sm font-semibold text-slate-300 hover:bg-slate-800">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-amber-500 hover:bg-amber-400 px-5 py-2 text-xs sm:text-sm font-semibold text-slate-950 shadow-md transition">
                        <span x-text="isEditMode ? 'บันทึกการแก้ไข' : 'บันทึกเข้า Second Brain'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function secondBrain() {
    return {
        modalOpen: false,
        isEditMode: false,
        formAction: '{{ route("portfolio.brain.store") }}',
        form: {
            id: null,
            title: '',
            content: '',
            type: 'inbox',
            category: 'idea',
            tags: '',
            url: '',
            is_pinned: false
        },

        openAddModal() {
            this.isEditMode = false;
            this.formAction = '{{ route("portfolio.brain.store") }}';
            this.form = {
                id: null,
                title: '',
                content: '',
                type: 'inbox',
                category: 'idea',
                tags: '',
                url: '',
                is_pinned: false
            };
            this.modalOpen = true;
        },

        openEditModal(note) {
            this.isEditMode = true;
            this.formAction = `/portfolio/brain/${note.id}`;
            this.form = {
                id: note.id,
                title: note.title || '',
                content: note.content || '',
                type: note.type || 'inbox',
                category: note.category || 'general',
                tags: Array.isArray(note.tags) ? note.tags.join(', ') : '',
                url: note.url || '',
                is_pinned: Boolean(note.is_pinned)
            };
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
        },

        async togglePin(noteId) {
            try {
                const res = await fetch(`/portfolio/brain/${noteId}/pin`, {
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
        }
    };
}
</script>

</x-portfolio-layout>
