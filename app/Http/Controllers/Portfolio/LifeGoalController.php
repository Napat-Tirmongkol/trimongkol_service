<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\LifeGoal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LifeGoalController extends Controller
{
    private function ensureTableExists(): void
    {
        if (!Schema::hasTable('portfolio_life_goals')) {
            Schema::create('portfolio_life_goals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->enum('horizon', ['short', 'medium', 'long'])->default('short');
                $table->string('category', 50)->default('personal');
                $table->unsignedSmallInteger('target_year')->nullable()->default(2027);
                $table->date('target_date')->nullable();
                $table->unsignedTinyInteger('progress_percent')->default(0);
                $table->boolean('is_completed')->default(false);
                $table->timestamp('completed_at')->nullable();
                $table->integer('sort_order')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'horizon']);
                $table->index(['user_id', 'is_completed']);
                $table->index(['user_id', 'target_year']);
            });

            // Seed initial sample goals for a warm start
            $userId = $this->resolvePortfolioUserId();
            $samples = [
                [
                    'user_id' => $userId,
                    'title' => 'ออกกำลังกายหรือขี่มอเตอร์ไซค์รับลมสัปดาห์ละ 3 วัน',
                    'description' => 'สร้างวินัยและสุขภาพที่ดี คุมน้ำหนักและอัตราการเต้นของหัวใจ',
                    'horizon' => 'short',
                    'category' => 'health',
                    'target_year' => 2027,
                    'progress_percent' => 50,
                    'is_completed' => false,
                ],
                [
                    'user_id' => $userId,
                    'title' => 'อ่านหนังสือพัฒนาตัวเองหรือเทคโนโลยีใหม่ให้จบ 5 เล่ม',
                    'description' => 'อ่านและสรุปประเด็นสำคัญเพื่อนำมาต่อยอดการทำงาน',
                    'horizon' => 'short',
                    'category' => 'learning',
                    'target_year' => 2027,
                    'progress_percent' => 20,
                    'is_completed' => false,
                ],
                [
                    'user_id' => $userId,
                    'title' => 'สำรองเงินฉุกเฉินครบ 6 เดือนของค่าใช้จ่ายจำเป็น',
                    'description' => 'สร้างเสถียรภาพทางการเงิน รองรับความผันผวนของงานและชีวิต',
                    'horizon' => 'medium',
                    'category' => 'finance',
                    'target_year' => 2027,
                    'progress_percent' => 75,
                    'is_completed' => false,
                ],
                [
                    'user_id' => $userId,
                    'title' => 'ออกทริปมอเตอร์ไซค์ท่องเที่ยวเส้นทางใหม่ภาคเหนือ 1,000 กม.',
                    'description' => 'เก็บบันทึก GPS และภาพถ่ายสวย ๆ ตลอดเส้นทางลงระบบ Life Journey',
                    'horizon' => 'medium',
                    'category' => 'travel',
                    'target_year' => 2027,
                    'progress_percent' => 30,
                    'is_completed' => false,
                ],
                [
                    'user_id' => $userId,
                    'title' => 'สร้าง Passive Income และพอร์ตการลงทุนแตะเป้าหมาย 1 ล้านบาท',
                    'description' => 'DCA กองทุนและหุ้นคุณค่าอย่างสม่ำเสมอทุกเดือนตามแผน Portfolio',
                    'horizon' => 'long',
                    'category' => 'finance',
                    'target_year' => 2028,
                    'progress_percent' => 45,
                    'is_completed' => false,
                ],
            ];

            foreach ($samples as $s) {
                LifeGoal::create($s);
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

    private function authorizeGoal(LifeGoal $goal): void
    {
        abort_unless($goal->user_id === $this->resolvePortfolioUserId(), 403);
    }

    public function index(Request $request): View
    {
        $this->ensureTableExists();

        $userId = $this->resolvePortfolioUserId();

        $activeHorizon = $request->query('horizon', 'all');
        $activeCategory = $request->query('category', 'all');
        $activeStatus = $request->query('status', 'all');
        $activeYear = $request->query('year', 'all');

        // All goals for user
        $allGoals = LifeGoal::forUser($userId)
            ->orderBy('sort_order')
            ->orderByRaw("FIELD(horizon, 'short', 'medium', 'long')")
            ->orderBy('target_date')
            ->orderByDesc('id')
            ->get();

        // Stats calculation
        $totalGoals = $allGoals->count();
        $completedGoals = $allGoals->where('is_completed', true)->count();
        $overallPercent = $totalGoals > 0 ? (int) round(($completedGoals / $totalGoals) * 100) : 0;

        $shortGoals = $allGoals->where('horizon', 'short');
        $shortCount = $shortGoals->count();
        $shortCompleted = $shortGoals->where('is_completed', true)->count();
        $shortPercent = $shortCount > 0 ? (int) round(($shortCompleted / $shortCount) * 100) : 0;

        $mediumGoals = $allGoals->where('horizon', 'medium');
        $mediumCount = $mediumGoals->count();
        $mediumCompleted = $mediumGoals->where('is_completed', true)->count();
        $mediumPercent = $mediumCount > 0 ? (int) round(($mediumCompleted / $mediumCount) * 100) : 0;

        $longGoals = $allGoals->where('horizon', 'long');
        $longCount = $longGoals->count();
        $longCompleted = $longGoals->where('is_completed', true)->count();
        $longPercent = $longCount > 0 ? (int) round(($longCompleted / $longCount) * 100) : 0;

        // Distinct years
        $years = $allGoals->pluck('target_year')->filter()->unique()->sort()->values()->toArray();
        if (empty($years)) {
            $years = [2027, 2028];
        }

        // Filtered list
        $filteredGoals = $allGoals->filter(function ($g) use ($activeHorizon, $activeCategory, $activeStatus, $activeYear) {
            if ($activeHorizon !== 'all' && $g->horizon !== $activeHorizon) {
                return false;
            }
            if ($activeCategory !== 'all' && $g->category !== $activeCategory) {
                return false;
            }
            if ($activeStatus === 'completed' && !$g->is_completed) {
                return false;
            }
            if ($activeStatus === 'pending' && $g->is_completed) {
                return false;
            }
            if ($activeYear !== 'all' && (string) $g->target_year !== (string) $activeYear) {
                return false;
            }
            return true;
        })->values();

        return view('portfolio.goals', compact(
            'allGoals',
            'filteredGoals',
            'totalGoals',
            'completedGoals',
            'overallPercent',
            'shortCount',
            'shortCompleted',
            'shortPercent',
            'mediumCount',
            'mediumCompleted',
            'mediumPercent',
            'longCount',
            'longCompleted',
            'longPercent',
            'years',
            'activeHorizon',
            'activeCategory',
            'activeStatus',
            'activeYear'
        ));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->ensureTableExists();

        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'horizon'          => 'required|in:short,medium,long',
            'category'         => 'required|in:finance,health,career,travel,learning,personal,other',
            'target_year'      => 'nullable|integer|min:2025|max:2050',
            'target_date'      => 'nullable|date',
            'progress_percent' => 'nullable|integer|min:0|max:100',
            'is_completed'     => 'nullable|boolean',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $data['user_id'] = $this->resolvePortfolioUserId();
        $data['progress_percent'] = (int) ($data['progress_percent'] ?? 0);
        $data['is_completed'] = $request->boolean('is_completed');

        if ($data['is_completed']) {
            $data['progress_percent'] = 100;
            $data['completed_at'] = Carbon::now();
        }

        $goal = LifeGoal::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'goal'    => $goal,
                'message' => 'เพิ่มเป้าหมายเรียบร้อยแล้ว',
            ]);
        }

        return redirect()->route('portfolio.life-goals.index')
            ->with('status', 'เพิ่มเป้าหมายเรียบร้อยแล้ว');
    }

    public function update(Request $request, LifeGoal $goal): RedirectResponse|JsonResponse
    {
        $this->authorizeGoal($goal);

        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'horizon'          => 'required|in:short,medium,long',
            'category'         => 'required|in:finance,health,career,travel,learning,personal,other',
            'target_year'      => 'nullable|integer|min:2025|max:2050',
            'target_date'      => 'nullable|date',
            'progress_percent' => 'nullable|integer|min:0|max:100',
            'is_completed'     => 'nullable|boolean',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $wasCompleted = $goal->is_completed;
        $nowCompleted = $request->boolean('is_completed');

        $data['progress_percent'] = (int) ($data['progress_percent'] ?? $goal->progress_percent);
        $data['is_completed'] = $nowCompleted;

        if (!$wasCompleted && $nowCompleted) {
            $data['completed_at'] = Carbon::now();
            $data['progress_percent'] = 100;
        } elseif ($wasCompleted && !$nowCompleted) {
            $data['completed_at'] = null;
            if ($data['progress_percent'] >= 100) {
                $data['progress_percent'] = 0;
            }
        }

        $goal->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'goal'    => $goal,
                'message' => 'อัปเดตเป้าหมายเรียบร้อยแล้ว',
            ]);
        }

        return redirect()->route('portfolio.life-goals.index')
            ->with('status', 'อัปเดตเป้าหมายเรียบร้อยแล้ว');
    }

    public function toggle(Request $request, LifeGoal $goal): RedirectResponse|JsonResponse
    {
        $this->authorizeGoal($goal);

        $isCompleted = !$goal->is_completed;
        $goal->is_completed = $isCompleted;
        $goal->completed_at = $isCompleted ? Carbon::now() : null;

        if ($isCompleted) {
            $goal->progress_percent = 100;
        }

        $goal->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success'      => true,
                'is_completed' => $goal->is_completed,
                'progress'     => $goal->progress_percent,
                'goal'         => $goal,
                'message'      => $isCompleted ? 'ยินดีด้วย! คุณทำเป้าหมายสำเร็จแล้ว 🎉' : 'ปรับสถานะเป็นยังไม่เสร็จสิ้น',
            ]);
        }

        return redirect()->route('portfolio.life-goals.index')
            ->with('status', $isCompleted ? 'ยินดีด้วย! คุณทำเป้าหมายสำเร็จแล้ว 🎉' : 'ปรับสถานะเป็นยังไม่เสร็จสิ้น');
    }

    public function updateProgress(Request $request, LifeGoal $goal): JsonResponse
    {
        $this->authorizeGoal($goal);

        $data = $request->validate([
            'progress_percent' => 'required|integer|min:0|max:100',
        ]);

        $percent = $data['progress_percent'];
        $goal->progress_percent = $percent;

        if ($percent >= 100) {
            $goal->is_completed = true;
            $goal->completed_at = $goal->completed_at ?? Carbon::now();
        } else {
            $goal->is_completed = false;
            $goal->completed_at = null;
        }

        $goal->save();

        return response()->json([
            'success'      => true,
            'is_completed' => $goal->is_completed,
            'progress'     => $goal->progress_percent,
            'goal'         => $goal,
        ]);
    }

    public function destroy(LifeGoal $goal): RedirectResponse|JsonResponse
    {
        $this->authorizeGoal($goal);
        $goal->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'ลบเป้าหมายเรียบร้อยแล้ว',
            ]);
        }

        return redirect()->route('portfolio.life-goals.index')
            ->with('status', 'ลบเป้าหมายเรียบร้อยแล้ว');
    }
}
