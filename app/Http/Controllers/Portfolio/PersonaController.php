<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\PersonalProfile;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PersonaController extends Controller
{
    private function ensureTableExists(): void
    {
        if (!Schema::hasTable('portfolio_personal_profiles')) {
            Schema::create('portfolio_personal_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->string('mbti', 20)->nullable();
                $table->string('headline', 255)->nullable();
                $table->text('traits')->nullable();
                $table->text('strengths')->nullable();
                $table->text('weaknesses')->nullable();
                $table->text('energy_boosters')->nullable();
                $table->text('energy_drainers')->nullable();
                $table->text('coping_guide')->nullable();
                $table->text('communication_style')->nullable();
                $table->text('life_principles')->nullable();
                $table->text('favorite_things')->nullable();
                $table->timestamps();

                $table->index('user_id');
            });
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

    public function index(): View
    {
        $this->ensureTableExists();

        $userId = $this->resolvePortfolioUserId();

        $profile = PersonalProfile::firstOrCreate(['user_id' => $userId]);

        // Auto-enrich profile with verified CV and portfolio evidence
        if (empty($profile->headline) || str_contains($profile->headline, 'โปรแกรมเมอร์สายลุย')) {
            $profile->update([
                'headline'            => 'Cybersecurity SOC Analyst & DevSecOps Engineer · มือเบสสายลุย',
                'mbti'                => 'ISTP / INTJ',
                'traits'              => "- ความชัดเจนตรงไปตรงมา: ชอบคุยกระชับ ตรงประเด็น ไม่ชอบพิธีรีตอง\n- All-Rounder Hybrid: เชื่อมโยงได้ทั้งฝั่งความมั่นคงปลอดภัยไซเบอร์ (SOC), เขียนโค้ด Full-Stack, IoT Hardware และศิลปะดนตรี\n- สมาธิเจาะลึก: เวลาสนใจสิ่งใดจะสืบค้นลงลึกจนสำเร็จ (จากพอร์ต ม.ปลาย สู่ปริญญานิพนธ์ และระบบคลินิกจริง)\n- รักอิสระและความเร็ว: ชื่นชอบการขับขี่มอเตอร์ไซค์รับลมเปิดหูเปิดตา",
                'strengths'           => "1. ความเชี่ยวชาญคู่ (Dual-Stack): เชี่ยวชาญทั้งฝั่ง Microsoft Security (Sentinel, MDE, MDI, KQL) และ Web Development (PHP 8, MySQL, Tailwind, LINE API)\n2. ประสบการณ์จริงระดับ Enterprise: เฝ้าระวังภัยไซเบอร์ระบบใหญ่ที่ PTT Digital Solutions และพัฒนาระบบ ERP บริหารคลินิก ม.รังสิต 35,500 คน\n3. ความคิดเป็นระบบและทักษะการบันทึก: วางสถาปัตยกรรมความจำ AI (hot.md, CLAUDE.md) และระบบ P.A.R.A. Second Brain อย่างเป็นระเบียบ\n4. มิติทางศิลปะและดนตรี: เป็นมือเบสวงสตริงและเล่นดนตรีไทย (จะเข้ ซออู้) ช่วยเพิ่มความคิดสร้างสรรค์และมุมมองนอกกรอบ",
                'weaknesses'          => "1. ถ่อมตัวเกินไป (Under-marketing): มักประเมินผลงานตัวเองต่ำกว่าสเกลจริงในพอร์ต ทำให้เสียโอกาสโชว์ศักยภาพระดับเต็มร้อย\n2. การจัดการพลังงานเวลาโหมงาน: เมื่อโฟกัสกับปัญหาด้านเทคนิคหรือสืบสวนภัยคุกคาม มักจะนั่งติดเก้าอี้จนลืมพักผ่อน\n3. ทักษะภาษาอังกฤษเชิงวิชาชีพ: ยังอยู่ในระดับ Limited Working Proficiency ซึ่งจำเป็นต้องยกระดับเพื่อก้าวสู่เวทีสากล\n4. ความเครียดสะสมจากงานเวร: การขึ้นเวรดึก 24/7 ในงาน SOC อาจกระทบต่อวงจรการนอนหากไม่บริหารเวลาอย่างเคร่งครัด",
                'energy_boosters'     => "1. ขี่มอเตอร์ไซค์รับลมเย็นๆ ออกทริปต่างจังหวัด\n2. เล่นกีตาร์เบส / ฟังเพลงโปรดพร้อมจิบกาแฟหรือเครื่องดื่มเย็นๆ\n3. เขียนโค้ดหรือทำระบบส่วนตัวที่เสร็จแล้วใช้งานได้จริง\n4. ไปกินชาบู/ปิ้งย่าง หรือนั่งชิลกับแฟนและเพื่อนสนิท",
                'energy_drainers'     => "- การคุยไร้สาระที่ไม่เห็นจุดหมาย หรือการเมืองในที่ทำงาน\n- คนที่ไม่ตรงต่อเวลา หรือสั่งงานแบบไม่ชัดเจน\n- สภาพแวดล้อมที่วุ่นวาย เสียงดังเกินไปเวลาต้องใช้สมาธิ",
                'coping_guide'        => "🚨 เมื่อเริ่มรู้สึกเครียด หรืออารมณ์ดิ่ง:\n1. หยุดหน้าจอก่อน 15 นาที อย่าเพิ่งรีบตัดสินใจอะไรตอนอารมณ์ไม่นิ่ง\n2. ออกไปสตาร์ทมอเตอร์ไซค์ขี่รับลมสักรอบ หรือเดินเปลี่ยนบรรยากาศ\n3. หยิบกีตาร์เบสมาแกะเพลง หรือหาของอร่อยกิน ดื่มน้ำเย็นๆ\n4. เตือนสติตัวเองว่า: เรื่องนี้อีก 1 เดือนข้างหน้าจะยังสำคัญอยู่ไหม?",
                'communication_style' => "- พูดตรงๆ ชัดเจน ตรงประเด็น ไม่ต้องเกริ่นยาว\n- สนิทกันคุยแบบเพื่อน สรรพนามกู-มึงได้เลย สบายใจกว่าเยอะ\n- เวลาต้องการความช่วยเหลือให้บอกปัญหาและเป้าหมายให้ชัด",
                'life_principles'     => "1. ทำสิ่งที่รัก และรับผิดชอบชีวิตตัวเอง 100%\n2. วินัยนำหน้าอารมณ์ สม่ำเสมอดีกว่าหวือหวา\n3. รักษาคำพูดและความซื่อสัตย์กับคนที่เรารัก\n4. ชีวิตสั้นเกินกว่าจะเสียเวลากับสิ่งที่ไม่ชอบ",
                'favorite_things'     => "- รถคู่ใจ: มอเตอร์ไซค์คู่ชีพ\n- ดนตรีที่รัก: เบสไฟฟ้า, กีตาร์, คีย์บอร์ด, จะเข้, ซออู้ (สไตล์ Rock, Indie, Lo-Fi)\n- ของดื่ม & อาหาร: กาแฟดำ / ชาเย็น / ชาบู-ปิ้งย่าง\n- งานอดิเรก: วิเคราะห์ความปลอดภัยไซเบอร์, วางแผนการเงิน DCA, ทัวร์ริ่งสำรวจเส้นทาง",
            ]);
            $profile->refresh();
        }

        $health = null;
        try {
            if (Schema::hasTable('portfolio_health_metrics')) {
                $health = \App\Models\Portfolio\HealthMetric::forUser($userId)->orderByDesc('date')->first();
            }
        } catch (\Throwable $e) {}

        // Folk's Personal Q&A Vault
        $qaList = $this->getQaList();

        return view('portfolio.persona', compact('profile', 'health', 'qaList'));
    }

    public function storeQa(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'question' => 'required|string|max:255',
            'answer'   => 'required|string|max:1000',
            'icon'     => 'nullable|string|max:10',
            'category' => 'nullable|string|max:50',
        ]);

        $qaList = $this->getQaList();
        $id = time() . '_' . rand(100, 999);

        // Check if updating existing question
        $updated = false;
        foreach ($qaList as &$item) {
            if (trim($item['question']) === trim($data['question'])) {
                $item['answer'] = $data['answer'];
                $item['icon'] = $data['icon'] ?: $item['icon'] ?? '✨';
                $item['category'] = $data['category'] ?: $item['category'] ?? 'ตัวตนทั่วไป';
                $item['updated_at'] = now()->toIso8601String();
                $updated = true;
                break;
            }
        }
        unset($item);

        if (! $updated) {
            array_unshift($qaList, [
                'id'         => $id,
                'question'   => $data['question'],
                'answer'     => $data['answer'],
                'icon'       => $data['icon'] ?: '✨',
                'category'   => $data['category'] ?: 'ตัวตนทั่วไป',
                'created_at' => now()->toIso8601String(),
            ]);
        }

        $this->saveQaList($qaList);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'บันทึกคำตอบลงคลังตัวตนของโฟล์กเรียบร้อยแล้วเว้ย!',
                'qaList'  => $qaList,
            ]);
        }

        return redirect()->route('portfolio.persona.index')
            ->with('status', 'บันทึกคำตอบลงคลังตัวตนของโฟล์กเรียบร้อยแล้วเว้ย! 💜');
    }

    public function deleteQa(string $id): RedirectResponse|JsonResponse
    {
        $qaList = $this->getQaList();
        $qaList = array_values(array_filter($qaList, fn($q) => ($q['id'] ?? '') !== $id));
        $this->saveQaList($qaList);

        return redirect()->route('portfolio.persona.index')
            ->with('status', 'ลบคำถามออกจากคลังเรียบร้อยแล้ว');
    }

    private function getQaList(): array
    {
        $paths = [
            storage_path('app/portfolio_folk_qa.json'),
            storage_path('app/private/portfolio_folk_qa.json'),
        ];

        foreach ($paths as $p) {
            if (file_exists($p)) {
                $content = @file_get_contents($p);
                if ($content) {
                    $decoded = json_decode($content, true);
                    if (is_array($decoded) && ! empty($decoded)) {
                        return $decoded;
                    }
                }
            }
        }

        // Default Seed Q&A for Folk
        $defaults = [
            [
                'id'         => 'qa_birthday',
                'question'   => 'เกิดวันที่เท่าไหร่? (วันเกิดโฟล์ก)',
                'answer'     => '6 พฤศจิกายน 2001 (อายุครบ 25 ปีบริบูรณ์ เบญจเพสพอดี)',
                'icon'       => '🎂',
                'category'   => 'ข้อมูลสำคัญ',
                'created_at' => '2026-11-11T13:30:00+07:00',
            ],
            [
                'id'         => 'qa_color',
                'question'   => 'ชอบสีอะไรมากที่สุด?',
                'answer'     => 'สีม่วง 💜 (Deep Purple / Neon Violet)',
                'icon'       => '💜',
                'category'   => 'ความชอบส่วนตัว',
                'created_at' => '2026-11-11T13:30:00+07:00',
            ],
            [
                'id'         => 'qa_animal',
                'question'   => 'ชอบสัตว์อะไร?',
                'answer'     => 'แมว 🐱 (ทาสแมวตัวจริง ชอบความน้วยและความเป็นอิสระ)',
                'icon'       => '🐱',
                'category'   => 'ความชอบส่วนตัว',
                'created_at' => '2026-11-11T13:30:00+07:00',
            ],
            [
                'id'         => 'qa_vehicle',
                'question'   => 'ยานพาหนะคู่ใจที่รักที่สุด?',
                'answer'     => 'มอเตอร์ไซค์คู่ชีพ ชอบขี่รับลมเย็นๆ ออกทริปเปิดหูเปิดตา',
                'icon'       => '🏍️',
                'category'   => 'ไลฟ์สไตล์',
                'created_at' => '2026-11-11T13:30:00+07:00',
            ],
            [
                'id'         => 'qa_music',
                'question'   => 'เครื่องดนตรีที่เล่นเป็นและรักที่สุด?',
                'answer'     => 'กีตาร์เบส (มือเบสประจำวงสตริง) และดนตรีไทย (จะเข้, ซออู้, กลองแขก)',
                'icon'       => '🎸',
                'category'   => 'ดนตรีและศิลปะ',
                'created_at' => '2026-11-11T13:30:00+07:00',
            ],
            [
                'id'         => 'qa_food',
                'question'   => 'ของกินและเครื่องดื่มที่กินกี่รอบก็ไม่เบื่อ?',
                'answer'     => 'ชาบู, ปิ้งย่าง, กาแฟดำเข้มๆ และชาเย็นหวานกำลังดี',
                'icon'       => '🍲',
                'category'   => 'ของกิน & เครื่องดื่ม',
                'created_at' => '2026-11-11T13:30:00+07:00',
            ],
            [
                'id'         => 'qa_rest',
                'question'   => 'นิยามของ "วันพักผ่อนที่สมบูรณ์แบบ"?',
                'answer'     => 'ไม่ต้องมีเรื่องงานให้ปวดหัว อยู่บ้านชิลๆ เล่นกับแมว ฟังเพลงโปรด หรือขี่มอเตอร์ไซค์ไปนั่งรับลมริมน้ำกับคนรู้ใจ',
                'icon'       => '🏖️',
                'category'   => 'ไลฟ์สไตล์',
                'created_at' => '2026-11-11T13:30:00+07:00',
            ],
        ];

        $this->saveQaList($defaults);
        return $defaults;
    }

    private function saveQaList(array $qaList): void
    {
        $json = json_encode($qaList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        @file_put_contents(storage_path('app/portfolio_folk_qa.json'), $json);
        @file_put_contents(storage_path('app/private/portfolio_folk_qa.json'), $json);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->ensureTableExists();

        $userId = $this->resolvePortfolioUserId();
        $profile = PersonalProfile::where('user_id', $userId)->firstOrFail();

        $data = $request->validate([
            'headline'            => 'nullable|string|max:255',
            'mbti'                => 'nullable|string|max:20',
            'traits'              => 'nullable|string',
            'strengths'           => 'nullable|string',
            'weaknesses'          => 'nullable|string',
            'energy_boosters'     => 'nullable|string',
            'energy_drainers'     => 'nullable|string',
            'coping_guide'        => 'nullable|string',
            'communication_style' => 'nullable|string',
            'life_principles'     => 'nullable|string',
            'favorite_things'     => 'nullable|string',
        ]);

        $profile->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'บันทึกคู่มือตัวตนและลักษณะนิสัยเรียบร้อยแล้วเว้ย!',
                'profile' => $profile,
            ]);
        }

        return redirect()->route('portfolio.persona.index')
            ->with('status', 'บันทึกคู่มือตัวตนและลักษณะนิสัยเรียบร้อยแล้วเว้ย! ✨');
    }
}
