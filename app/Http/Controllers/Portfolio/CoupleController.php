<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\CoupleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CoupleController extends Controller
{
    private function ensureTableExists(): void
    {
        if (!Schema::hasTable('portfolio_couple_items')) {
            Schema::create('portfolio_couple_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->enum('type', ['food', 'trip', 'wishlist', 'date_idea', 'movie', 'savings'])->default('date_idea');
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->string('location', 255)->nullable();
                $table->date('target_date')->nullable();
                $table->decimal('target_amount', 12, 2)->nullable();
                $table->decimal('saved_amount', 12, 2)->default(0);
                $table->boolean('is_done')->default(false);
                $table->timestamp('done_at')->nullable();
                $table->tinyInteger('rating')->nullable();
                $table->string('partner_name', 100)->default('แฟน');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'type']);
                $table->index(['user_id', 'is_done']);
            });

            // Seed heartwarming initial sample items
            $userId = $this->resolvePortfolioUserId();
            $samples = [
                [
                    'user_id' => $userId,
                    'type' => 'food',
                    'title' => 'ชาบูหม่าล่าสายพาน & ปิ้งย่างเกาหลี',
                    'description' => 'นัดไปกินของอร่อยช่วงวันหยุดสุดสัปดาห์',
                    'location' => 'สยามสแควร์ หรือเซ็นทรัลลาดพร้าว',
                    'is_done' => true,
                    'done_at' => Carbon::now()->subDays(2),
                    'rating' => 5,
                ],
                [
                    'user_id' => $userId,
                    'type' => 'trip',
                    'title' => 'ทริปขี่มอเตอร์ไซค์รับลมหนาว & กางเต็นท์ดูดาว',
                    'description' => 'วางแผนเส้นทางขึ้นเขาค้อ หรือแม่ฮ่องสอน 3 วัน 2 คืน',
                    'location' => 'เขาค้อ - ภูทับเบิก',
                    'target_date' => Carbon::now()->addMonths(1)->toDateString(),
                    'is_done' => false,
                ],
                [
                    'user_id' => $userId,
                    'type' => 'wishlist',
                    'title' => 'กล้องฟิล์มหรือโพลารอยด์ไว้เก็บความทรงจำคู่กัน',
                    'description' => 'ถ่ายรูปตอนไปเที่ยวทริปต่างจังหวัดแล้วเอามาแปะผนังห้อง',
                    'target_amount' => 3500.00,
                    'saved_amount' => 2000.00,
                    'is_done' => false,
                ],
                [
                    'user_id' => $userId,
                    'type' => 'savings',
                    'title' => 'กระปุกเงินเก็บกองกลางไปเที่ยวทะเลภูเก็ต',
                    'description' => 'ช่วยกันหยอดกระปุกคนละครึ่งทุกสิ้นเดือน',
                    'target_amount' => 15000.00,
                    'saved_amount' => 8500.00,
                    'is_done' => false,
                ],
                [
                    'user_id' => $userId,
                    'type' => 'movie',
                    'title' => 'ดูอนิเมะและซีรีส์เกาหลีมาราธอนช่วงวันหยุด',
                    'description' => 'ซื้อป๊อปคอร์น ขนม ของกินเล่นมานอนดูด้วยกันยาวๆ',
                    'is_done' => false,
                ],
            ];

            foreach ($samples as $s) {
                CoupleItem::create($s);
            }
        }
    }

    private function resolvePortfolioUserId(): int
    {
        $allowed = (array) config('portfolio.allowed_emails', []);
        $primaryEmail = !empty($allowed) ? strtolower($allowed[0]) : null;
        if ($primaryEmail) {
            $owner = User::where('email', $primaryEmail)->first();
            if ($owner) {
                return $owner->id;
            }
        }
        return (int) (auth()->id() ?? 1);
    }

    private function authorizeItem(CoupleItem $item): void
    {
        abort_unless($item->user_id === $this->resolvePortfolioUserId(), 403);
    }

    public function index(Request $request): View
    {
        $this->ensureTableExists();

        $userId = $this->resolvePortfolioUserId();
        $activeType = $request->query('type', 'all');

        $query = CoupleItem::forUser($userId);

        if ($activeType === 'done') {
            $query->where('is_done', true);
        } elseif ($activeType !== 'all') {
            $query->where('type', $activeType);
        }

        $items = $query->orderBy('is_done')
            ->orderByDesc('id')
            ->get();

        $allUserItems = CoupleItem::forUser($userId)->get();
        $totalCount = $allUserItems->count();
        $doneCount = $allUserItems->where('is_done', true)->count();
        $savingsItems = $allUserItems->where('type', 'savings');
        $totalTargetSavings = $savingsItems->sum('target_amount');
        $totalActualSavings = $savingsItems->sum('saved_amount');

        // Type counts
        $foodCount = $allUserItems->where('type', 'food')->count();
        $tripCount = $allUserItems->where('type', 'trip')->count();
        $wishlistCount = $allUserItems->where('type', 'wishlist')->count();
        $savingsCount = $savingsItems->count();
        $movieCount = $allUserItems->where('type', 'movie')->count();

        return view('portfolio.couple', compact(
            'items',
            'activeType',
            'totalCount',
            'doneCount',
            'totalTargetSavings',
            'totalActualSavings',
            'foodCount',
            'tripCount',
            'wishlistCount',
            'savingsCount',
            'movieCount'
        ));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->ensureTableExists();

        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'type'          => 'required|in:food,trip,wishlist,date_idea,movie,savings',
            'description'   => 'nullable|string|max:1000',
            'location'      => 'nullable|string|max:255',
            'target_date'   => 'nullable|date',
            'target_amount' => 'nullable|numeric|min:0',
            'saved_amount'  => 'nullable|numeric|min:0',
            'partner_name'  => 'nullable|string|max:100',
        ]);

        $data['user_id'] = $this->resolvePortfolioUserId();
        $data['saved_amount'] = (float) ($data['saved_amount'] ?? 0);
        $data['partner_name'] = $data['partner_name'] ?? 'แฟน';

        $item = CoupleItem::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'item'    => $item,
                'message' => 'บันทึกรายการคู่รักเรียบร้อยแล้ว 💕',
            ]);
        }

        return redirect()->route('portfolio.couple.index')
            ->with('status', 'บันทึกรายการคู่รักเรียบร้อยแล้ว 💕');
    }

    public function toggleDone(Request $request, CoupleItem $item): JsonResponse|RedirectResponse
    {
        $this->authorizeItem($item);

        $nowDone = !$item->is_done;
        $item->is_done = $nowDone;
        $item->done_at = $nowDone ? Carbon::now() : null;
        $item->save();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_done' => $item->is_done,
                'message' => $nowDone ? 'เย้! ไปทำกิจกรรมนี้ด้วยกันมาแล้ว 🎉' : 'ปรับสถานะกลับมาเป็นรอไปทำ',
            ]);
        }

        return redirect()->route('portfolio.couple.index');
    }

    public function updateSavings(Request $request, CoupleItem $item): JsonResponse|RedirectResponse
    {
        $this->authorizeItem($item);

        $data = $request->validate([
            'saved_amount' => 'required|numeric|min:0',
        ]);

        $item->saved_amount = (float) $data['saved_amount'];
        if ($item->target_amount > 0 && $item->saved_amount >= $item->target_amount) {
            $item->is_done = true;
            $item->done_at = $item->done_at ?? Carbon::now();
        }
        $item->save();

        if (request()->wantsJson()) {
            return response()->json([
                'success'      => true,
                'saved_amount' => $item->saved_amount,
                'percent'      => $item->savingsPercent(),
                'is_done'      => $item->is_done,
                'message'      => 'อัปเดตยอดเงินในกระปุกเรียบร้อย 💰',
            ]);
        }

        return redirect()->route('portfolio.couple.index')
            ->with('status', 'อัปเดตยอดเงินในกระปุกเรียบร้อย 💰');
    }

    public function destroy(CoupleItem $item): JsonResponse|RedirectResponse
    {
        $this->authorizeItem($item);
        $item->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'ลบรายการเรียบร้อยแล้ว',
            ]);
        }

        return redirect()->route('portfolio.couple.index')
            ->with('status', 'ลบรายการเรียบร้อยแล้ว');
    }
}
