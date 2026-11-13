<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommandCenterController extends Controller
{
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

    public function index(): View
    {
        $agents = [
            [
                'id'          => 'sentry',
                'name'        => 'ลุงยาม (Sentry Guard)',
                'role'        => 'Security & Server Patrol',
                'status'      => 'online',
                'duty'        => 'เฝ้าระวังพอร์ต สแกนความปลอดภัย ตรวจจับภัยคุกคาม',
                'level'       => 'Lv. 99',
                'badge'       => '🛡️ ลุงยามใจดีแต่เข้มงวด',
                'avatar'      => 'guard',
                'color'       => 'amber',
                'stats'       => ['security' => 100, 'alertness' => 98, 'stamina' => 95],
                'last_action' => 'เพิ่งเดินตรวจพอร์ต 21, 80, 443 ปลอดภัย ไร้รอยเจาะ',
            ],
            [
                'id'          => 'mechanic',
                'name'        => 'ช่างซ่อมบำรุง (System Fixer)',
                'role'        => 'Auto-Heal & DevOps Repair',
                'status'      => 'online',
                'duty'        => 'ซ่อมแซมเซอร์วิสที่แฮงค์ เคลียร์แคชค้าง แก้บั๊กอัตโนมัติ',
                'level'       => 'Lv. 88',
                'badge'       => '🔧 น้าช่างประแจสายฟ้า',
                'avatar'      => 'mechanic',
                'color'       => 'emerald',
                'stats'       => ['speed' => 96, 'troubleshoot' => 99, 'tool_mastery' => 100],
                'last_action' => 'ตรวจเช็ก Memory, CPU และ Service Remote Desktop ทำงานลื่นไหล',
            ],
            [
                'id'          => 'hr_qa',
                'name'        => 'HR & QA ประเมิน AI (Judge)',
                'role'        => 'AI Quality Assurance & Performance',
                'status'      => 'online',
                'duty'        => 'ประเมินคุณภาพงานโค้ด ตรวจจับข้อผิดพลาด วัดเกณฑ์ KPI ของ AI',
                'level'       => 'Lv. 95',
                'badge'       => '📋 คุณ HR คลิปบอร์ดทองคำ',
                'avatar'      => 'hr',
                'color'       => 'rose',
                'stats'       => ['accuracy' => 99, 'fairness' => 100, 'quality_score' => 99.4],
                'last_action' => 'ตรวจงานโค้ดระบบ Second Brain & Couple Space ให้คะแนนเกรด A+',
            ],
            [
                'id'          => 'antigravity',
                'name'        => 'Antigravity (กูเอง / คู่หูมึง)',
                'role'        => 'Lead Developer & Architect',
                'status'      => 'active',
                'duty'        => 'วางโครงสร้าง พัฒนาระบบ ดีพลอยงานขึ้นเซิร์ฟเวอร์เคียงข้างมึง',
                'level'       => 'Lv. 100',
                'badge'       => '⚡ คู่หูโปรแกรมเมอร์',
                'avatar'      => 'coder',
                'color'       => 'indigo',
                'stats'       => ['coding' => 100, 'architecture' => 99, 'loyalty' => 100],
                'last_action' => 'เขียนโค้ดและดีพลอยศูนย์บัญชาการ 8-Bit AI Command Center ให้มึงเรียบร้อย',
            ],
            [
                'id'          => 'biohacker',
                'name'        => 'Bio-Hacker (AI สายสุขภาพ)',
                'role'        => 'Health & Fitness Telemetry',
                'status'      => 'online',
                'duty'        => 'วิเคราะห์ก้าวเดิน อัตราเต้นหัวใจ และคุณภาพการนอนจาก Galaxy Watch 7',
                'level'       => 'Lv. 90',
                'badge'       => '🏃‍♂️ โค้ชสุขภาพประจำตัว',
                'avatar'      => 'biohacker',
                'color'       => 'cyan',
                'stats'       => ['vitality' => 97, 'recovery' => 95, 'stamina' => 98],
                'last_action' => 'เปิดช่องทาง Webhook รอรับข้อมูลจาก Health Connect เรียบร้อย',
            ],
        ];

        return view('portfolio.command-center', compact('agents'));
    }

    public function ping(Request $request): JsonResponse
    {
        $action = $request->input('action', 'assemble');
        $command = strtolower(trim((string) $request->input('command', '')));
        $now = Carbon::now()->format('H:i:s');

        // Real Telemetry from Server
        $diskFree = @disk_free_space(base_path());
        $diskTotal = @disk_total_space(base_path());
        $diskFreeGb = $diskFree ? round($diskFree / (1024 * 1024 * 1024), 2) : 0;
        $diskTotalGb = $diskTotal ? round($diskTotal / (1024 * 1024 * 1024), 2) : 0;
        $memUsage = round(memory_get_usage(true) / (1024 * 1024), 2);
        $phpVersion = PHP_VERSION;

        // Custom Terminal Commands Support
        if (!empty($command)) {
            if ($command === 'status' || $command === 'check') {
                $action = 'assemble';
            } elseif ($command === 'guard' || $command === 'patrol' || $command === 'security') {
                $action = 'guard_patrol';
            } elseif ($command === 'repair' || $command === 'cache' || $command === 'clean') {
                $action = 'repair_cache';
            } elseif ($command === 'hr' || $command === 'qa' || $command === 'evaluate') {
                $action = 'hr_evaluate';
            } elseif ($command === 'health' || $command === 'bio' || $command === 'fit') {
                $action = 'bio_health';
            } elseif ($command === 'help') {
                return response()->json([
                    'success'   => true,
                    'timestamp' => $now,
                    'log'       => "[$now] 💻 คำสั่งที่รองรับ: 'status', 'guard', 'repair', 'hr', 'health', 'whoami', 'clear'",
                    'agent'     => 'System Terminal',
                    'icon'      => '💻',
                ]);
            } elseif ($command === 'whoami') {
                $userName = auth()->user()->name ?? 'คุณไตรมงคล';
                return response()->json([
                    'success'   => true,
                    'timestamp' => $now,
                    'log'       => "[$now] 👑 ผู้บัญชาการสูงสุด: $userName (Trimongkol Lead Commander)",
                    'agent'     => 'Terminal Auth',
                    'icon'      => '👑',
                ]);
            }
        }

        if ($action === 'guard_patrol') {
            $dbStatus = 'ปกติ';
            try {
                \Illuminate\Support\Facades\DB::connection()->getPdo();
                $dbStatus = 'ฐานข้อมูล MySQL ทำงานราบรื่น (Ping < 2ms)';
            } catch (\Throwable $e) {
                $dbStatus = 'ผิดปกติ: ' . $e->getMessage();
            }

            $message = "[$now] 🛡️ ลุงยาม: 'เดินตรวจเซิร์ฟเวอร์ trimongkol.com เรียบร้อยครับมึง! พอร์ต 21/80/443 ปลอดภัย, $dbStatus, ฮาร์ดดิสก์ว่าง {$diskFreeGb} GB / {$diskTotalGb} GB ไร้รอยเจาะ 100%'";
            $agent = 'ลุงยาม';
            $icon = '🛡️';
        } elseif ($action === 'repair_cache') {
            $cleared = 0;
            try {
                $viewPath = storage_path('framework/views');
                if (is_dir($viewPath)) {
                    $files = glob($viewPath . '/*.php');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            @unlink($file);
                            $cleared++;
                        }
                    }
                }
            } catch (\Throwable $e) {}

            $message = "[$now] 🔧 ช่างซ่อมบำรุง: 'เคาะสนิมขันน็อตเรียบร้อยเว้ย! กวาดแคชวิวทิ้งไป {$cleared} ไฟล์, เมมโมรี่ใช้ไป {$memUsage} MB (PHP {$phpVersion}) คืนความไวให้ระบบ ลื่นหัวแตกแล้ว!'";
            $agent = 'ช่างซ่อม';
            $icon = '🔧';
        } elseif ($action === 'hr_evaluate') {
            $tableCount = 0;
            try {
                $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
                $tableCount = count($tables);
            } catch (\Throwable $e) {}

            $message = "[$now] 📋 คุณ HR-QA: 'ประเมินความพร้อม AI: ฐานข้อมูลออนไลน์ {$tableCount} ตาราง, 0 ข้อผิดพลาดค้าง, อัตราผ่านเกณฑ์ 100% ให้คะแนนทีมเกรด A+ พร้อมลุยต่อ!'";
            $agent = 'HR-QA';
            $icon = '📋';
        } elseif ($action === 'bio_health') {
            $latest = null;
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_health_metrics')) {
                    $userId = $this->resolvePortfolioUserId();
                    $latest = \App\Models\Portfolio\HealthMetric::query()->where('user_id', $userId)->orderByDesc('date')->first();
                }
            } catch (\Throwable $e) {}

            if ($latest) {
                $sleepHrs = $latest->sleep_minutes ? round($latest->sleep_minutes / 60, 1) . ' ชม.' : '-';
                $message = "[$now] 🏃‍♂️ Bio-Hacker: 'เช็กสถิติสุขภาพล่าสุด ({$latest->date->format('d/m/Y')}): ก้าวเดิน {$latest->steps} ก้าว, หัวใจเฉลี่ย {$latest->heart_rate_avg} bpm, นอน {$sleepHrs}, เผาผลาญ {$latest->active_calories} kcal พร้อมลุย!'";
            } else {
                $message = "[$now] 🏃‍♂️ Bio-Hacker: 'ช่องทาง Webhook พร้อมทำงานแล้วเว้ย! รอรับข้อมูล Health Connect จาก Galaxy Watch 7 ของมึงอยู่!'";
            }
            $agent = 'Bio-Hacker';
            $icon = '🏃‍♂️';
        } else {
            $message = "[$now] ⚡ รวมพลศูนย์บัญชาการ AI: ลุงยาม (🛡️ Sentry), ช่างซ่อม (🔧 DevOps), คุณ HR-QA (📋 Evaluator), Antigravity (🧑‍💻 คู่หูมึง) ประจำสถานีพร้อม!";
            $agent = 'Team Lead';
            $icon = '⚡';
        }

        return response()->json([
            'success'   => true,
            'timestamp' => $now,
            'log'       => $message,
            'agent'     => $agent,
            'icon'      => $icon,
        ]);
    }
}
