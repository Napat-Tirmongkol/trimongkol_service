<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\BudgetItem;
use App\Models\Portfolio\FuelLog;
use App\Models\Portfolio\LedgerEntry;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FuelController extends Controller
{
    private function ensureTableExists(): void
    {
        if (!Schema::hasTable('portfolio_fuel_logs')) {
            Schema::create('portfolio_fuel_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->date('refill_date');
                $table->string('vehicle_name', 100)->default('มอเตอร์ไซค์');
                $table->decimal('odometer', 10, 2)->nullable();
                $table->decimal('distance', 10, 2)->nullable();
                $table->decimal('liters', 8, 2);
                $table->decimal('price_per_liter', 8, 2);
                $table->decimal('total_price', 10, 2);
                $table->string('fuel_type', 50)->default('แก๊สโซฮอล์ 95');
                $table->string('gas_station', 50)->default('ปตท.');
                $table->boolean('is_full_tank')->default(true);
                $table->decimal('efficiency_kml', 8, 2)->nullable();
                $table->decimal('cost_per_km', 8, 2)->nullable();
                $table->unsignedBigInteger('ledger_entry_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index('refill_date');
            });
        }
    }

    public function index(Request $request): View
    {
        $this->ensureTableExists();

        $userId = Auth::id();
        $now = Carbon::now();
        $activeMonth = $request->query('month', $now->format('Y-m'));

        // All distinct months available in fuel logs
        $existingMonths = FuelLog::forUser($userId)
            ->selectRaw('DATE_FORMAT(refill_date, "%Y-%m") as ym')
            ->distinct()
            ->orderBy('ym', 'desc')
            ->pluck('ym')
            ->toArray();

        $allMonths = array_values(array_unique(array_merge([$now->format('Y-m')], $existingMonths)));
        rsort($allMonths);

        // Distinct vehicles recorded by user
        $recordedVehicles = FuelLog::forUser($userId)
            ->distinct()
            ->pluck('vehicle_name')
            ->filter()
            ->values()
            ->toArray();

        $vehicles = array_values(array_unique(array_merge(['มอเตอร์ไซค์', 'รถยนต์'], $recordedVehicles)));
        $activeVehicle = $request->query('vehicle', 'all');

        $query = FuelLog::forUser($userId)
            ->orderBy('refill_date', 'desc')
            ->orderBy('id', 'desc');

        if ($activeMonth !== 'all') {
            $query->whereRaw('DATE_FORMAT(refill_date, "%Y-%m") = ?', [$activeMonth]);
        }

        if ($activeVehicle !== 'all') {
            $query->where('vehicle_name', $activeVehicle);
        }

        $logs = $query->get();

        // Month stats
        $monthTotalCost = $logs->sum('total_price');
        $monthTotalLiters = $logs->sum('liters');
        $monthTotalDistance = $logs->sum('distance');

        $efficiencyLogs = $logs->whereNotNull('efficiency_kml')->where('efficiency_kml', '>', 0);
        $avgEfficiencyKml = $efficiencyLogs->count() > 0 ? round($efficiencyLogs->avg('efficiency_kml'), 2) : null;

        $costKmLogs = $logs->whereNotNull('cost_per_km')->where('cost_per_km', '>', 0);
        $avgCostPerKm = $costKmLogs->count() > 0 ? round($costKmLogs->avg('cost_per_km'), 2) : null;

        // Latest log for vehicle to get last odometer
        $latestLog = FuelLog::forUser($userId)
            ->whereNotNull('odometer')
            ->when($activeVehicle !== 'all', fn ($q) => $q->where('vehicle_name', $activeVehicle))
            ->orderBy('refill_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $lastOdometer = $latestLog ? (float) $latestLog->odometer : null;

        // Fuel budget item in active month (if exists)
        $fuelBudgetItem = null;
        if (Schema::hasTable('portfolio_budget_items') && $activeMonth !== 'all') {
            $fuelBudgetItem = BudgetItem::forUser($userId)
                ->where('month', $activeMonth)
                ->where(function ($q) {
                    $q->where('label', 'like', '%น้ำมัน%')
                      ->orWhere('label', 'like', '%เดินทาง%');
                })
                ->first();
        }

        return view('portfolio.fuel', compact(
            'logs',
            'activeMonth',
            'allMonths',
            'vehicles',
            'activeVehicle',
            'monthTotalCost',
            'monthTotalLiters',
            'monthTotalDistance',
            'avgEfficiencyKml',
            'avgCostPerKm',
            'lastOdometer',
            'fuelBudgetItem'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureTableExists();

        $userId = Auth::id();

        $validated = $request->validate([
            'refill_date'     => 'required|date',
            'vehicle_name'    => 'required|string|max:100',
            'odometer'        => 'nullable|numeric|min:0',
            'liters'          => 'nullable|numeric|min:0.01',
            'price_per_liter' => 'nullable|numeric|min:0.01',
            'total_price'     => 'nullable|numeric|min:0.01',
            'fuel_type'       => 'nullable|string|max:50',
            'gas_station'     => 'nullable|string|max:50',
            'is_full_tank'    => 'nullable|boolean',
            'sync_ledger'     => 'nullable|boolean',
            'notes'           => 'nullable|string|max:500',
        ]);

        $pricePerLiter = isset($validated['price_per_liter']) ? (float) $validated['price_per_liter'] : 0.0;
        $totalPrice    = isset($validated['total_price']) ? (float) $validated['total_price'] : 0.0;
        $liters        = isset($validated['liters']) ? (float) $validated['liters'] : 0.0;

        // Auto-compute missing values
        if ($totalPrice > 0 && $pricePerLiter > 0 && $liters <= 0) {
            $liters = round($totalPrice / $pricePerLiter, 2);
        } elseif ($liters > 0 && $pricePerLiter > 0 && $totalPrice <= 0) {
            $totalPrice = round($liters * $pricePerLiter, 2);
        } elseif ($totalPrice > 0 && $liters > 0 && $pricePerLiter <= 0) {
            $pricePerLiter = round($totalPrice / $liters, 2);
        }

        if ($totalPrice <= 0) {
            return back()->withInput()->with('error', 'กรุณาระบุจำนวนเงิน หรือ ลิตรและราคาต่อลิตร');
        }

        $refillDate = Carbon::parse($validated['refill_date']);
        $month = $refillDate->format('Y-m');
        $vehicleName = trim($validated['vehicle_name']) ?: 'มอเตอร์ไซค์';
        $gasStation = trim($validated['gas_station'] ?? '') ?: 'ปตท.';
        $fuelType = trim($validated['fuel_type'] ?? '') ?: 'แก๊สโซฮอล์ 95';
        $isFullTank = $request->boolean('is_full_tank', true);
        $syncLedger = $request->boolean('sync_ledger', true);

        $ledgerEntryId = null;
        if ($syncLedger && Schema::hasTable('portfolio_ledger')) {
            // Find existing fuel budget item for this month
            $budgetItem = null;
            if (Schema::hasTable('portfolio_budget_items')) {
                $budgetItem = BudgetItem::forUser($userId)
                    ->where('month', $month)
                    ->where(function ($q) {
                        $q->where('label', 'like', '%น้ำมัน%')
                          ->orWhere('label', 'like', '%เดินทาง%');
                    })
                    ->first();
            }

            $odoText = !empty($validated['odometer']) ? "เลขไมล์ {$validated['odometer']} กม. " : '';
            $entry = LedgerEntry::create([
                'user_id'        => $userId,
                'date'           => $refillDate->format('Y-m-d'),
                'month'          => $month,
                'type'           => LedgerEntry::TYPE_EXPENSE,
                'amount'         => $totalPrice,
                'label'          => "เติมน้ำมัน {$vehicleName} ({$gasStation})",
                'budget_item_id' => $budgetItem?->id,
                'notes'          => trim("{$odoText}{$liters} ลิตร @ {$pricePerLiter} ฿ " . ($validated['notes'] ?? '')),
            ]);

            $ledgerEntryId = $entry->id;

            if ($budgetItem) {
                $this->syncActualBudgetItem($budgetItem->id);
            }
        }

        $fuelLog = FuelLog::create([
            'user_id'         => $userId,
            'refill_date'     => $refillDate->format('Y-m-d'),
            'vehicle_name'    => $vehicleName,
            'odometer'        => !empty($validated['odometer']) ? (float) $validated['odometer'] : null,
            'liters'          => $liters,
            'price_per_liter' => $pricePerLiter,
            'total_price'     => $totalPrice,
            'fuel_type'       => $fuelType,
            'gas_station'     => $gasStation,
            'is_full_tank'    => $isFullTank,
            'ledger_entry_id' => $ledgerEntryId,
            'notes'           => $validated['notes'] ?? null,
        ]);

        $this->recalculateVehicleMetrics($userId, $vehicleName);

        return redirect()
            ->route('portfolio.fuel.index', ['month' => $month, 'vehicle' => $vehicleName])
            ->with('status', "บันทึกค่าน้ำมัน {$vehicleName} ฿" . number_format($totalPrice, 2) . " เรียบร้อยแล้ว");
    }

    public function update(Request $request, FuelLog $log): RedirectResponse
    {
        $this->ensureTableExists();

        if ($log->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'refill_date'     => 'required|date',
            'vehicle_name'    => 'required|string|max:100',
            'odometer'        => 'nullable|numeric|min:0',
            'liters'          => 'required|numeric|min:0.01',
            'price_per_liter' => 'required|numeric|min:0.01',
            'total_price'     => 'required|numeric|min:0.01',
            'fuel_type'       => 'nullable|string|max:50',
            'gas_station'     => 'nullable|string|max:50',
            'is_full_tank'    => 'nullable|boolean',
            'notes'           => 'nullable|string|max:500',
        ]);

        $oldVehicle = $log->vehicle_name;
        $refillDate = Carbon::parse($validated['refill_date']);
        $totalPrice = (float) $validated['total_price'];
        $vehicleName = trim($validated['vehicle_name']) ?: 'มอเตอร์ไซค์';

        $log->update([
            'refill_date'     => $refillDate->format('Y-m-d'),
            'vehicle_name'    => $vehicleName,
            'odometer'        => !empty($validated['odometer']) ? (float) $validated['odometer'] : null,
            'liters'          => (float) $validated['liters'],
            'price_per_liter' => (float) $validated['price_per_liter'],
            'total_price'     => $totalPrice,
            'fuel_type'       => trim($validated['fuel_type'] ?? '') ?: 'แก๊สโซฮอล์ 95',
            'gas_station'     => trim($validated['gas_station'] ?? '') ?: 'ปตท.',
            'is_full_tank'    => $request->boolean('is_full_tank', true),
            'notes'           => $validated['notes'] ?? null,
        ]);

        // Sync associated ledger entry if present
        if ($log->ledger_entry_id && Schema::hasTable('portfolio_ledger')) {
            $ledger = LedgerEntry::find($log->ledger_entry_id);
            if ($ledger) {
                $month = $refillDate->format('Y-m');
                $ledger->update([
                    'date'   => $refillDate->format('Y-m-d'),
                    'month'  => $month,
                    'amount' => $totalPrice,
                    'label'  => "เติมน้ำมัน {$vehicleName} ({$log->gas_station})",
                    'notes'  => trim(($log->odometer ? "เลขไมล์ {$log->odometer} กม. " : '') . "{$log->liters} ลิตร @ {$log->price_per_liter} ฿ " . ($log->notes ?? '')),
                ]);

                if ($ledger->budget_item_id) {
                    $this->syncActualBudgetItem($ledger->budget_item_id);
                }
            }
        }

        $this->recalculateVehicleMetrics($log->user_id, $vehicleName);
        if ($oldVehicle !== $vehicleName) {
            $this->recalculateVehicleMetrics($log->user_id, $oldVehicle);
        }

        return redirect()
            ->route('portfolio.fuel.index', ['month' => $refillDate->format('Y-m'), 'vehicle' => $vehicleName])
            ->with('status', 'อัปเดตรายการเติมน้ำมันแล้ว');
    }

    public function destroy(FuelLog $log): RedirectResponse
    {
        $this->ensureTableExists();

        if ($log->user_id !== Auth::id()) {
            abort(403);
        }

        $userId = $log->user_id;
        $vehicleName = $log->vehicle_name;
        $month = Carbon::parse($log->refill_date)->format('Y-m');

        if ($log->ledger_entry_id && Schema::hasTable('portfolio_ledger')) {
            $ledger = LedgerEntry::find($log->ledger_entry_id);
            if ($ledger) {
                $budgetItemId = $ledger->budget_item_id;
                $ledger->delete();
                if ($budgetItemId) {
                    $this->syncActualBudgetItem($budgetItemId);
                }
            }
        }

        $log->delete();

        $this->recalculateVehicleMetrics($userId, $vehicleName);

        return redirect()
            ->route('portfolio.fuel.index', ['month' => $month, 'vehicle' => $vehicleName])
            ->with('status', 'ลบรายการเติมน้ำมันแล้ว');
    }

    private function syncActualBudgetItem(int $budgetItemId): void
    {
        if (!Schema::hasTable('portfolio_budget_items') || !Schema::hasTable('portfolio_ledger')) {
            return;
        }

        $item = BudgetItem::find($budgetItemId);
        if (!$item) return;

        $sum = LedgerEntry::where('budget_item_id', $budgetItemId)
            ->where('type', LedgerEntry::TYPE_EXPENSE)
            ->sum('amount');

        $item->update(['actual_amount' => $sum]);
    }

    private function recalculateVehicleMetrics(int $userId, string $vehicleName): void
    {
        $logs = FuelLog::forUser($userId)
            ->where('vehicle_name', $vehicleName)
            ->orderBy('refill_date', 'asc')
            ->orderBy('odometer', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $prevLog = null;

        foreach ($logs as $log) {
            $distance = null;
            $efficiency = null;
            $costPerKm = null;

            if ($prevLog && $prevLog->odometer !== null && $log->odometer !== null) {
                $diff = (float) $log->odometer - (float) $prevLog->odometer;
                if ($diff > 0) {
                    $distance = round($diff, 2);

                    // Efficiency in km/L: valid when both this and previous refill were full tank
                    if ($log->is_full_tank && $prevLog->is_full_tank && (float) $log->liters > 0) {
                        $efficiency = round($distance / (float) $log->liters, 2);
                    }

                    if ((float) $log->total_price > 0) {
                        $costPerKm = round((float) $log->total_price / $distance, 2);
                    }
                }
            }

            $log->distance = $distance;
            $log->efficiency_kml = $efficiency;
            $log->cost_per_km = $costPerKm;
            $log->saveQuietly();

            $prevLog = $log;
        }
    }
}
