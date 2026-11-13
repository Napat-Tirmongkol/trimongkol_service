<x-portfolio-layout>

@php
    $thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    $monthLabel = function (string $ym) use ($thaiMonths): string {
        if ($ym === 'all') return 'ทุกช่วงเวลา';
        [$y, $m] = explode('-', $ym);
        return $thaiMonths[(int) $m] . ' ' . ((int) $y + 543);
    };

    $formatThaiDate = function ($date) use ($thaiMonths): string {
        $c = \Illuminate\Support\Carbon::parse($date);
        return $c->day . ' ' . $thaiMonths[$c->month] . ' ' . ($c->year + 543);
    };

    $stationColors = [
        'ปตท.'     => ['badge' => 'bg-blue-500/10 text-blue-400 border-blue-500/30', 'dot' => 'bg-blue-500'],
        'บางจาก'   => ['badge' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30', 'dot' => 'bg-emerald-500'],
        'เชลล์'    => ['badge' => 'bg-amber-500/10 text-amber-400 border-amber-500/30', 'dot' => 'bg-amber-500'],
        'Shell'    => ['badge' => 'bg-amber-500/10 text-amber-400 border-amber-500/30', 'dot' => 'bg-amber-500'],
        'คาลเท็กซ์' => ['badge' => 'bg-rose-500/10 text-rose-400 border-rose-500/30', 'dot' => 'bg-rose-500'],
        'Caltex'   => ['badge' => 'bg-rose-500/10 text-rose-400 border-rose-500/30', 'dot' => 'bg-rose-500'],
        'พีที'      => ['badge' => 'bg-green-500/10 text-green-400 border-green-500/30', 'dot' => 'bg-green-500'],
        'PT'       => ['badge' => 'bg-green-500/10 text-green-400 border-green-500/30', 'dot' => 'bg-green-500'],
        'ซัสโก้'    => ['badge' => 'bg-sky-500/10 text-sky-400 border-sky-500/30', 'dot' => 'bg-sky-500'],
    ];

    $defaultStationColor = ['badge' => 'bg-slate-500/10 text-slate-400 border-slate-500/30', 'dot' => 'bg-slate-400'];
@endphp

<div x-data="fuelTracker()" class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8 space-y-6">

    {{-- Top Header & Month/Vehicle Selector --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M4.5 12h11m-11 0v7.5A2.25 2.25 0 0 0 6.75 21.75h6.5A2.25 2.25 0 0 0 15.5 19.5V12m4 0v5.25a2.25 2.25 0 0 1-2.25 2.25H16.5m3-7.5-2.25-2.25m0 0 1.5-1.5a1.5 1.5 0 0 1 2.122 0l.128.128a1.5 1.5 0 0 1 0 2.122l-1.5 1.5Z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-100">บันทึกค่าน้ำมันรถ</h1>
                    <p class="text-xs sm:text-sm text-slate-400">จดบันทึกการเติมน้ำมัน เลขไมล์ อัตราสิ้นเปลือง (กม./ลิตร) และต้นทุนต่อกิโลเมตร</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Month selector --}}
            <form method="GET" action="{{ route('portfolio.fuel.index') }}" class="flex items-center gap-1.5">
                <input type="hidden" name="vehicle" value="{{ $activeVehicle }}">
                <select name="month" onchange="this.form.submit()"
                        class="rounded-xl border border-slate-700 bg-slate-800/90 px-3 py-2 text-xs sm:text-sm font-semibold text-slate-200 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    <option value="all" @selected($activeMonth === 'all')>ทุกเดือน</option>
                    @foreach ($allMonths as $m)
                        <option value="{{ $m }}" @selected($activeMonth === $m)>
                            {{ $monthLabel($m) }}
                        </option>
                    @endforeach
                </select>
            </form>

            {{-- New Refill Button --}}
            <button type="button" @click="openAddModal()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 px-4 py-2 text-xs sm:text-sm font-semibold text-slate-950 shadow-md transition active:scale-95">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>จดบันทึกการเติม</span>
            </button>
        </div>
    </div>

    {{-- Vehicle Filter Tabs --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
        <a href="{{ route('portfolio.fuel.index', ['month' => $activeMonth, 'vehicle' => 'all']) }}"
           class="whitespace-nowrap rounded-xl px-3.5 py-1.5 text-xs font-semibold transition border {{ $activeVehicle === 'all' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
            ยานพาหนะทั้งหมด
        </a>
        @foreach ($vehicles as $v)
            <a href="{{ route('portfolio.fuel.index', ['month' => $activeMonth, 'vehicle' => $v]) }}"
               class="whitespace-nowrap rounded-xl px-3.5 py-1.5 text-xs font-semibold transition border {{ $activeVehicle === $v ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
                @if($v === 'มอเตอร์ไซค์') 🛵 @elseif($v === 'รถยนต์') 🚗 @else 🚘 @endif
                {{ $v }}
            </a>
        @endforeach
    </div>

    {{-- Scoreboard Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {{-- Card 1: Monthly Cost --}}
        <div class="rounded-2xl border border-slate-800 bg-[#161922] p-4 sm:p-5 flex flex-col justify-between shadow-sm">
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                    <span>ค่าน้ำมัน{{ $activeMonth !== 'all' ? ' (' . $monthLabel($activeMonth) . ')' : '' }}</span>
                    <span class="text-amber-400">฿</span>
                </div>
                <div class="mt-2 text-xl sm:text-2xl font-black text-slate-100">
                    ฿{{ number_format($monthTotalCost, 2) }}
                </div>
            </div>
            @if ($fuelBudgetItem && (float) $fuelBudgetItem->amount > 0)
                @php
                    $budgetPct = min(100, round(($monthTotalCost / (float) $fuelBudgetItem->amount) * 100));
                    $isOver = $monthTotalCost > (float) $fuelBudgetItem->amount;
                @endphp
                <div class="mt-3 pt-2.5 border-t border-slate-800 text-[11px]">
                    <div class="flex justify-between text-slate-400">
                        <span>เทียบงบ ฿{{ number_format($fuelBudgetItem->amount, 0) }}</span>
                        <span class="{{ $isOver ? 'text-rose-400 font-bold' : 'text-emerald-400' }}">{{ $budgetPct }}%</span>
                    </div>
                    <div class="mt-1 h-1.5 w-full rounded-full bg-slate-800 overflow-hidden">
                        <div class="h-full rounded-full {{ $isOver ? 'bg-rose-500' : 'bg-amber-500' }}" style="width: {{ $budgetPct }}%;"></div>
                    </div>
                </div>
            @else
                <div class="mt-3 text-[11px] text-slate-400">
                    เฉลี่ย ฿{{ $logs->count() > 0 ? number_format($monthTotalCost / $logs->count(), 1) : '0' }} / ครั้ง
                </div>
            @endif
        </div>

        {{-- Card 2: Liters pumped --}}
        <div class="rounded-2xl border border-slate-800 bg-[#161922] p-4 sm:p-5 flex flex-col justify-between shadow-sm">
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                    <span>ปริมาณน้ำมัน</span>
                    <span class="text-sky-400">L</span>
                </div>
                <div class="mt-2 text-xl sm:text-2xl font-black text-slate-100">
                    {{ number_format($monthTotalLiters, 2) }} <span class="text-xs font-semibold text-slate-400">ลิตร</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-800 text-[11px] text-slate-400 flex items-center justify-between">
                <span>ราคาเฉลี่ย</span>
                <span class="font-semibold text-slate-200">
                    ฿{{ $monthTotalLiters > 0 ? number_format($monthTotalCost / $monthTotalLiters, 2) : '0.00' }}/ลิตร
                </span>
            </div>
        </div>

        {{-- Card 3: Distance Traveled --}}
        <div class="rounded-2xl border border-slate-800 bg-[#161922] p-4 sm:p-5 flex flex-col justify-between shadow-sm">
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                    <span>ระยะทางที่วิ่ง</span>
                    <span class="text-emerald-400">KM</span>
                </div>
                <div class="mt-2 text-xl sm:text-2xl font-black text-slate-100">
                    {{ number_format($monthTotalDistance, 1) }} <span class="text-xs font-semibold text-slate-400">กม.</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-800 text-[11px] text-slate-400 flex items-center justify-between">
                <span>เลขไมล์ล่าสุด</span>
                <span class="font-semibold text-slate-200">
                    {{ $lastOdometer ? number_format($lastOdometer, 1) . ' กม.' : '-' }}
                </span>
            </div>
        </div>

        {{-- Card 4: Fuel Consumption & Cost/km --}}
        <div class="rounded-2xl border border-slate-800 bg-[#161922] p-4 sm:p-5 flex flex-col justify-between shadow-sm">
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                    <span>อัตราสิ้นเปลือง</span>
                    <span class="text-purple-400">km/L</span>
                </div>
                <div class="mt-2 text-xl sm:text-2xl font-black text-purple-400">
                    {{ $avgEfficiencyKml ? number_format($avgEfficiencyKml, 1) : '-' }} <span class="text-xs font-semibold text-slate-400">กม./ลิตร</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-800 text-[11px] text-slate-400 flex items-center justify-between">
                <span>ต้นทุนการวิ่ง</span>
                <span class="font-bold text-amber-400">
                    {{ $avgCostPerKm ? '฿' . number_format($avgCostPerKm, 2) . '/กม.' : '-' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Main History Logbook --}}
    <div class="rounded-2xl border border-slate-800 bg-[#161922] p-4 sm:p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-800">
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                <h2 class="text-base font-bold text-slate-100">ประวัติการเติมน้ำมัน</h2>
                <span class="rounded-full bg-slate-800 px-2 py-0.5 text-[10px] font-medium text-slate-400">
                    {{ $logs->count() }} รายการ
                </span>
            </div>

            @if($logs->count() > 0)
                <button type="button" @click="openAddModal()"
                        class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition flex items-center gap-1">
                    <span>+ เพิ่มรายการ</span>
                </button>
            @endif
        </div>

        @if ($logs->isEmpty())
            <div class="py-12 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-800/70 text-slate-400 mb-3">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M4.5 12h11m-11 0v7.5A2.25 2.25 0 0 0 6.75 21.75h6.5A2.25 2.25 0 0 0 15.5 19.5V12m4 0v5.25a2.25 2.25 0 0 1-2.25 2.25H16.5m3-7.5-2.25-2.25m0 0 1.5-1.5a1.5 1.5 0 0 1 2.122 0l.128.128a1.5 1.5 0 0 1 0 2.122l-1.5 1.5Z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-200">ยังไม่มีบันทึกการเติมน้ำมันในเดือนนี้</h3>
                <p class="mt-1 text-xs text-slate-400 max-w-sm mx-auto">เริ่มจดบันทึกครั้งแรกเพื่อคำนวณอัตราสิ้นเปลือง (กม./ลิตร) และติดตามค่าน้ำมันอัตโนมัติ</p>
                <button type="button" @click="openAddModal()"
                        class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 px-4 py-2 text-xs font-semibold text-slate-950 shadow transition">
                    + จดบันทึกครั้งแรก
                </button>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($logs as $log)
                    @php
                        $st = $stationColors[$log->gas_station] ?? $defaultStationColor;
                    @endphp
                    <div class="rounded-xl border border-slate-800/80 bg-slate-900/60 p-3.5 sm:p-4 hover:border-slate-700 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        {{-- Left side info --}}
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $st['badge'] }} border">
                                <span class="text-xs font-black">
                                    {{ mb_substr($log->gas_station, 0, 3) }}
                                </span>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-xs font-bold text-slate-200">
                                        {{ $log->vehicle_name }}
                                    </span>
                                    <span class="text-[11px] rounded-md px-1.5 py-0.5 bg-slate-800 text-slate-300 font-medium">
                                        {{ $log->fuel_type }}
                                    </span>
                                    <span class="text-[11px] rounded-md px-1.5 py-0.5 border {{ $st['badge'] }}">
                                        {{ $log->gas_station }}
                                    </span>
                                    @if ($log->is_full_tank)
                                        <span class="text-[10px] rounded-md px-1.5 py-0.2 bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                            เต็มถัง
                                        </span>
                                    @endif
                                    @if ($log->ledger_entry_id)
                                        <span class="text-[10px] text-slate-400 flex items-center gap-0.5" title="ซิงค์ลงสมุดบัญชีแล้ว">
                                            <svg class="h-3 w-3 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                            </svg>
                                            สมุดบัญชี
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400">
                                    <span>📅 {{ $formatThaiDate($log->refill_date) }}</span>
                                    <span>⛽ {{ number_format($log->liters, 2) }} ลิตร</span>
                                    <span>@ ฿{{ number_format($log->price_per_liter, 2) }}/ลิตร</span>
                                    @if ($log->odometer)
                                        <span>📍 ไมล์ {{ number_format($log->odometer, 1) }} กม.</span>
                                    @endif
                                </div>

                                @if ($log->notes)
                                    <div class="mt-1 text-[11px] text-slate-400 italic">
                                        💬 {{ $log->notes }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Right side metrics & actions --}}
                        <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 sm:pt-0 border-t border-slate-800 sm:border-0">
                            {{-- Efficiency badges --}}
                            <div class="text-right">
                                <div class="text-base sm:text-lg font-black text-amber-400">
                                    ฿{{ number_format($log->total_price, 2) }}
                                </div>
                                <div class="flex items-center justify-end gap-1.5 mt-0.5 text-[11px]">
                                    @if ($log->distance)
                                        <span class="font-semibold text-emerald-400">+{{ number_format($log->distance, 1) }} กม.</span>
                                    @endif
                                    @if ($log->efficiency_kml)
                                        <span class="rounded bg-purple-500/10 px-1.5 py-0.5 font-bold text-purple-300 border border-purple-500/20">
                                            {{ number_format($log->efficiency_kml, 1) }} กม./ลิตร
                                        </span>
                                    @endif
                                    @if ($log->cost_per_km)
                                        <span class="text-slate-400">
                                            (฿{{ number_format($log->cost_per_km, 2) }}/กม.)
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-1 pl-2">
                                <button type="button" @click="openEditModal({{ json_encode($log) }})"
                                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition"
                                        title="แก้ไข">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('portfolio.fuel.destroy', $log) }}"
                                      onsubmit="return confirm('ยืนยันลบรายการเติมน้ำมันนี้? (หากซิงค์สมุดบัญชีไว้ ยอดในสมุดจะถูกลบออกด้วย)')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-500/10 hover:text-rose-400 transition"
                                            title="ลบ">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── ADD FUEL MODAL ── --}}
    <div x-show="showAddModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="showAddModal = false"
             class="w-full max-w-lg rounded-2xl border border-slate-700 bg-[#161922] p-5 sm:p-6 shadow-2xl space-y-4 my-8">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-500/20 text-amber-400">⛽</span>
                    <h3 class="text-base font-bold text-slate-100">จดบันทึกการเติมน้ำมัน</h3>
                </div>
                <button type="button" @click="showAddModal = false" class="text-slate-400 hover:text-slate-200">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('portfolio.fuel.store') }}" class="space-y-4">
                @csrf

                {{-- Date & Vehicle --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">วันที่เติมน้ำมัน</label>
                        <input type="date" name="refill_date" x-model="form.refill_date" required
                               class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-medium text-slate-100 focus:border-amber-500 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">ยานพาหนะ</label>
                        <div class="flex gap-1.5">
                            <input type="text" name="vehicle_name" x-model="form.vehicle_name" required
                                   class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-medium text-slate-100 focus:border-amber-500 focus:ring-amber-500">
                        </div>
                        <div class="flex gap-1 mt-1.5">
                            <button type="button" @click="form.vehicle_name = 'มอเตอร์ไซค์'"
                                    :class="form.vehicle_name === 'มอเตอร์ไซค์' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'bg-slate-800 text-slate-400 border-slate-700'"
                                    class="rounded-lg border px-2 py-0.5 text-[10px] font-semibold transition">🛵 มอไซค์</button>
                            <button type="button" @click="form.vehicle_name = 'รถยนต์'"
                                    :class="form.vehicle_name === 'รถยนต์' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'bg-slate-800 text-slate-400 border-slate-700'"
                                    class="rounded-lg border px-2 py-0.5 text-[10px] font-semibold transition">🚗 รถยนต์</button>
                        </div>
                    </div>
                </div>

                {{-- Gas Station Quick Chips --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">ปั๊มน้ำมัน</label>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="st in ['ปตท.', 'บางจาก', 'เชลล์', 'คาลเท็กซ์', 'พีที', 'ซัสโก้']" :key="st">
                            <button type="button" @click="form.gas_station = st"
                                    :class="form.gas_station === st ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                    class="rounded-lg px-2.5 py-1 text-xs transition">
                                <span x-text="st"></span>
                            </button>
                        </template>
                    </div>
                    <input type="hidden" name="gas_station" x-model="form.gas_station">
                </div>

                {{-- Fuel Type Quick Chips --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">ประเภทน้ำมัน</label>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="ft in ['แก๊สโซฮอล์ 95', 'แก๊สโซฮอล์ 91', 'แก๊สโซฮอล์ E20', 'ดีเซล', 'เบนซิน']" :key="ft">
                            <button type="button" @click="form.fuel_type = ft"
                                    :class="form.fuel_type === ft ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                    class="rounded-lg px-2.5 py-1 text-xs transition">
                                <span x-text="ft"></span>
                            </button>
                        </template>
                    </div>
                    <input type="hidden" name="fuel_type" x-model="form.fuel_type">
                </div>

                {{-- Smart Price & Liters Calculator --}}
                <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-3.5 space-y-3">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-amber-400">
                        ⚡ คำนวณค่าน้ำมันอัตโนมัติ
                    </div>

                    {{-- Quick Amount Pills --}}
                    <div>
                        <div class="text-[11px] text-slate-400 mb-1">เลือกยอดเงินยอดนิยม (บาท):</div>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="amt in [50, 80, 100, 120, 150, 200, 500, 1000]" :key="amt">
                                <button type="button" @click="setAmount(amt)"
                                        class="rounded-lg border border-slate-700 bg-slate-800/90 px-2 py-0.5 text-xs font-semibold text-slate-200 hover:border-amber-500 hover:text-amber-400 transition">
                                    ฿<span x-text="amt"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        {{-- Total Price --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-0.5">ยอดรวม (บาท) *</label>
                            <input type="number" step="0.01" min="1" name="total_price" x-model="form.total_price"
                                   @input="onTotalPriceChange()" required placeholder="เช่น 100"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-sm font-bold text-amber-400 focus:border-amber-500 focus:ring-amber-500">
                        </div>

                        {{-- Price Per Liter --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-0.5">ราคาต่อลิตร (บาท) *</label>
                            <input type="number" step="0.01" min="1" name="price_per_liter" x-model="form.price_per_liter"
                                   @input="onPricePerLiterChange()" required placeholder="เช่น 38.50"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-sm font-semibold text-slate-100 focus:border-amber-500 focus:ring-amber-500">
                        </div>

                        {{-- Volume (Liters) --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-0.5">ปริมาณ (ลิตร)</label>
                            <input type="number" step="0.01" min="0.01" name="liters" x-model="form.liters"
                                   @input="onLitersChange()" required placeholder="คำนวณให้อัตโนมัติ"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-sm font-bold text-sky-400 focus:border-amber-500 focus:ring-amber-500">
                        </div>
                    </div>
                </div>

                {{-- Odometer & Live preview --}}
                <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-3.5 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-slate-200">เลขไมล์ปัจจุบัน (กิโลเมตร)</label>
                        @if ($lastOdometer)
                            <span class="text-[11px] text-slate-400">
                                ไมล์ครั้งก่อน: <strong class="text-slate-200">{{ number_format($lastOdometer, 1) }} กม.</strong>
                            </span>
                        @endif
                    </div>
                    <input type="number" step="0.1" name="odometer" x-model="form.odometer" @input="onOdometerChange()"
                           placeholder="เช่น {{ $lastOdometer ? $lastOdometer + 120 : '15400' }}"
                           class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-sm font-semibold text-slate-100 focus:border-amber-500 focus:ring-amber-500">

                    {{-- Live calculation diff preview --}}
                    <div x-show="odometerDiff > 0" x-cloak
                         class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-2 text-xs flex items-center justify-between text-emerald-300">
                        <span>ระยะทางวิ่งเพิ่ม: <strong x-text="'+' + odometerDiff.toFixed(1) + ' กม.'"></strong></span>
                        <span x-show="previewEfficiency > 0">
                            สิ้นเปลือง: <strong x-text="previewEfficiency.toFixed(1) + ' กม./ลิตร'"></strong>
                        </span>
                    </div>
                </div>

                {{-- Switches: Full Tank & Ledger Sync --}}
                <div class="space-y-2 pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_full_tank" value="1" x-model="form.is_full_tank"
                               class="rounded border-slate-700 bg-slate-800 text-amber-500 focus:ring-amber-500">
                        <span class="text-xs font-semibold text-slate-300">เติมเต็มถัง (ใช้ในการคำนวณ กม./ลิตร ที่แม่นยำ)</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="sync_ledger" value="1" x-model="form.sync_ledger"
                               class="rounded border-slate-700 bg-slate-800 text-amber-500 focus:ring-amber-500">
                        <span class="text-xs font-semibold text-slate-300">บันทึกลงสมุดบัญชีรายรับ-รายจ่าย (Ledger) ในงบค่าน้ำมันด้วย</span>
                    </label>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">บันทึกช่วยจำ (ไม่บังคับ)</label>
                    <input type="text" name="notes" x-model="form.notes" placeholder="เช่น วิ่งงานนอกสถานที่, แวะเติมก่อนเดินทาง"
                           class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="showAddModal = false"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-400 hover:bg-slate-800 transition">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-amber-500 hover:bg-amber-400 px-5 py-2 text-xs font-bold text-slate-950 shadow-md transition active:scale-95">
                        บันทึกรายการ
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── EDIT FUEL MODAL ── --}}
    <div x-show="showEditModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
        <div @click.away="showEditModal = false"
             class="w-full max-w-lg rounded-2xl border border-slate-700 bg-[#161922] p-5 sm:p-6 shadow-2xl space-y-4 my-8">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-500/20 text-blue-400">✏️</span>
                    <h3 class="text-base font-bold text-slate-100">แก้ไขรายการเติมน้ำมัน</h3>
                </div>
                <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-200">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>
                </button>
            </div>

            <form method="POST" :action="editActionUrl" class="space-y-4">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">วันที่</label>
                        <input type="date" name="refill_date" x-model="editForm.refill_date" required
                               class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-medium text-slate-100">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">ยานพาหนะ</label>
                        <input type="text" name="vehicle_name" x-model="editForm.vehicle_name" required
                               class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-medium text-slate-100">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">ปั๊มน้ำมัน</label>
                        <input type="text" name="gas_station" x-model="editForm.gas_station" required
                               class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-medium text-slate-100">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">ประเภทน้ำมัน</label>
                        <input type="text" name="fuel_type" x-model="editForm.fuel_type" required
                               class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-medium text-slate-100">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-300 mb-0.5">ยอดรวม (฿)</label>
                        <input type="number" step="0.01" name="total_price" x-model="editForm.total_price" required
                               @input="editForm.liters = (editForm.total_price / editForm.price_per_liter).toFixed(2)"
                               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-sm font-bold text-amber-400">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-300 mb-0.5">ราคา/ลิตร</label>
                        <input type="number" step="0.01" name="price_per_liter" x-model="editForm.price_per_liter" required
                               @input="editForm.liters = (editForm.total_price / editForm.price_per_liter).toFixed(2)"
                               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-sm font-semibold text-slate-100">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-300 mb-0.5">ปริมาณ (ลิตร)</label>
                        <input type="number" step="0.01" name="liters" x-model="editForm.liters" required
                               @input="editForm.total_price = (editForm.liters * editForm.price_per_liter).toFixed(2)"
                               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-sm font-bold text-sky-400">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">เลขไมล์ (กม.)</label>
                    <input type="number" step="0.1" name="odometer" x-model="editForm.odometer"
                           class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-100">
                </div>

                <label class="flex items-center gap-2 cursor-pointer pt-1">
                    <input type="checkbox" name="is_full_tank" value="1" x-model="editForm.is_full_tank"
                           class="rounded border-slate-700 bg-slate-800 text-amber-500 focus:ring-amber-500">
                    <span class="text-xs font-semibold text-slate-300">เติมเต็มถัง</span>
                </label>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">บันทึกช่วยจำ</label>
                    <input type="text" name="notes" x-model="editForm.notes"
                           class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="showEditModal = false"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-400 hover:bg-slate-800">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-blue-600 hover:bg-blue-500 px-5 py-2 text-xs font-bold text-white shadow">
                        อัปเดตข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('fuelTracker', () => ({
        showAddModal: false,
        showEditModal: false,
        lastOdometer: {{ $lastOdometer ? json_encode((float) $lastOdometer) : 'null' }},
        odometerDiff: 0,
        previewEfficiency: 0,
        editActionUrl: '',

        form: {
            refill_date: '{{ \Carbon\Carbon::now()->format('Y-m-d') }}',
            vehicle_name: '{{ $activeVehicle !== 'all' ? $activeVehicle : 'มอเตอร์ไซค์' }}',
            gas_station: 'ปตท.',
            fuel_type: 'แก๊สโซฮอล์ 95',
            total_price: 100,
            price_per_liter: 38.50,
            liters: 2.60,
            odometer: '',
            is_full_tank: true,
            sync_ledger: true,
            notes: ''
        },

        editForm: {
            id: null,
            refill_date: '',
            vehicle_name: '',
            gas_station: '',
            fuel_type: '',
            total_price: 0,
            price_per_liter: 0,
            liters: 0,
            odometer: '',
            is_full_tank: true,
            notes: ''
        },

        init() {
            this.recomputeVolume();
        },

        openAddModal() {
            this.showAddModal = true;
            this.onOdometerChange();
        },

        openEditModal(item) {
            this.editForm = {
                id: item.id,
                refill_date: item.refill_date ? item.refill_date.substring(0, 10) : '',
                vehicle_name: item.vehicle_name,
                gas_station: item.gas_station,
                fuel_type: item.fuel_type,
                total_price: parseFloat(item.total_price),
                price_per_liter: parseFloat(item.price_per_liter),
                liters: parseFloat(item.liters),
                odometer: item.odometer ? parseFloat(item.odometer) : '',
                is_full_tank: !!item.is_full_tank,
                notes: item.notes || ''
            };
            this.editActionUrl = '{{ url('/portfolio/fuel') }}/' + item.id;
            this.showEditModal = true;
        },

        setAmount(amt) {
            this.form.total_price = amt;
            this.recomputeVolume();
        },

        onTotalPriceChange() {
            this.recomputeVolume();
        },

        onPricePerLiterChange() {
            this.recomputeVolume();
        },

        onLitersChange() {
            const liters = parseFloat(this.form.liters) || 0;
            const ppl = parseFloat(this.form.price_per_liter) || 0;
            if (liters > 0 && ppl > 0) {
                this.form.total_price = Math.round(liters * ppl * 100) / 100;
            }
            this.onOdometerChange();
        },

        recomputeVolume() {
            const total = parseFloat(this.form.total_price) || 0;
            const ppl = parseFloat(this.form.price_per_liter) || 0;
            if (total > 0 && ppl > 0) {
                this.form.liters = (Math.round((total / ppl) * 100) / 100).toFixed(2);
            }
            this.onOdometerChange();
        },

        onOdometerChange() {
            const cur = parseFloat(this.form.odometer);
            if (this.lastOdometer !== null && !isNaN(cur) && cur > this.lastOdometer) {
                this.odometerDiff = cur - this.lastOdometer;
                const liters = parseFloat(this.form.liters) || 0;
                if (liters > 0) {
                    this.previewEfficiency = this.odometerDiff / liters;
                } else {
                    this.previewEfficiency = 0;
                }
            } else {
                this.odometerDiff = 0;
                this.previewEfficiency = 0;
            }
        }
    }));
});
</script>

</x-portfolio-layout>
