<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\BrainNote;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SecondBrainController extends Controller
{
    private function ensureTableExists(): void
    {
        if (!Schema::hasTable('portfolio_brain_notes')) {
            Schema::create('portfolio_brain_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('title', 255);
                $table->longText('content')->nullable();
                $table->enum('type', ['inbox', 'project', 'area', 'resource', 'archive'])->default('inbox');
                $table->string('category', 50)->default('general');
                $table->json('tags')->nullable();
                $table->string('color', 30)->default('slate');
                $table->string('url', 500)->nullable();
                $table->boolean('is_pinned')->default(false);
                $table->boolean('is_archived')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['user_id', 'type']);
                $table->index(['user_id', 'is_pinned']);
                $table->index(['user_id', 'is_archived']);
            });

            // Seed initial sample notes for a warm start
            $userId = $this->resolvePortfolioUserId();
            $samples = [
                [
                    'user_id' => $userId,
                    'title' => '⚡ สรุปไอเดียระบบเตือนรอบบำรุงรักษารถมอเตอร์ไซค์',
                    'content' => "คิดไว้ว่าจะเชื่อมเข้ากับระบบบันทึกค่าน้ำมัน (Fuel Log):\n- แจ้งเตือนเมื่อระยะทางสะสมครบ 3,000 กม. เพื่อเปลี่ยนถ่ายน้ำมันเครื่อง\n- เช็กแรงดันลมยางและระบบเบรกก่อนออกทริปไกล",
                    'type' => 'inbox',
                    'category' => 'travel',
                    'tags' => ['#motorcycle', '#idea', '#fuel'],
                    'color' => 'amber',
                    'is_pinned' => true,
                ],
                [
                    'user_id' => $userId,
                    'title' => '🎯 โปรเจกต์ Life Operating System บน trimongkol.com',
                    'content' => "รวมเครื่องมือจัดการชีวิตไว้ในที่เดียว:\n1. บันทึกการเดินทาง (Life Journey 2,216 ทริป)\n2. บันทึกค่าน้ำมัน & อัตราสิ้นเปลือง\n3. เป้าหมายชีวิต (Goal Year 2570)\n4. คลังสมองที่สอง (Second Brain P.A.R.A.)",
                    'type' => 'project',
                    'category' => 'work',
                    'tags' => ['#trimongkol', '#project', '#roadmap'],
                    'color' => 'rose',
                    'is_pinned' => true,
                ],
                [
                    'user_id' => $userId,
                    'title' => '🌐 มิติชีวิต: วินัยการเงินและการลงทุน DCA',
                    'content' => "- สำรองเงินฉุกเฉิน 6 เดือนในบัญชีออมทรัพย์ดอกเบี้ยสูง\n- ลงทุน DCA หุ้นคุณค่าและกองทุนดัชนีทุกวันที่ 1 ของเดือน\n- ตรวจสอบสรุปทรัพย์สินและหนี้สินทุกสิ้นเดือน",
                    'type' => 'area',
                    'category' => 'finance',
                    'tags' => ['#finance', '#dca', '#wealth'],
                    'color' => 'blue',
                    'is_pinned' => false,
                ],
                [
                    'user_id' => $userId,
                    'title' => '📚 คลังความรู้: กฎและคำสั่งลัด Plesk FTP Auto-Deployment',
                    'content' => "สคริปต์ deploy_files.py เชื่อมต่อผ่าน Port 21:\n- อัปโหลดไฟล์เฉพาะที่แก้ไขไปยัง /httpdocs/\n- ล้างแคช Blade views ใน /httpdocs/storage/framework/views อัตโนมัติ ป้องกันแคชหน้าเว็บค้าง",
                    'type' => 'resource',
                    'category' => 'tech',
                    'tags' => ['#plesk', '#devops', '#python'],
                    'color' => 'emerald',
                    'is_pinned' => false,
                ],
            ];

            foreach ($samples as $s) {
                BrainNote::create($s);
            }
        }

        $this->seedRsuClinicKnowledge($this->resolvePortfolioUserId());
        $this->seedSocMasterPlaybook($this->resolvePortfolioUserId());
        $this->seedYouTubeWatchSearchProfile($this->resolvePortfolioUserId());
    }

    private function seedSocMasterPlaybook(int $userId): void
    {
        try {
            $exists = BrainNote::where('user_id', $userId)
                ->where('title', 'like', '%Master KQL%')
                ->exists();

            if ($exists) {
                return;
            }

            $socNotes = [
                [
                    'user_id'   => $userId,
                    'title'     => '⚡ Master KQL Threat Hunting Playbook (Microsoft Sentinel & Defender)',
                    'content'   => "รวมสุดยอด Kusto Query Language (KQL) สำหรับงาน CSOC คัดกรองจากบันทึกงานจริง:\n\n"
                        . "// 1. ค้นหาประวัติการเข้าสู่ระบบและ IP ต้องสงสัยของ User\n"
                        . "IdentityLogonEvents\n"
                        . "| where AccountUpn contains \"<user_email>\"\n"
                        . "| where IPAddress == \"<target_ip>\"\n"
                        . "| project Timestamp, ActionType, AccountUpn, IPAddress, DeviceName, Location\n\n"
                        . "// 2. สรุปความถี่และสาเหตุ Logon Failed\n"
                        . "IdentityLogonEvents\n"
                        . "| where AccountUpn contains \"<user_email>\"\n"
                        . "| summarize ActionCount = count(), FirstLogonTime = min(Timestamp), LastLogonTime = max(Timestamp) by ActionType, FailureReason\n\n"
                        . "// 3. ตรวจจับการสแกนบัญชี (Suspected Account Enumeration)\n"
                        . "IdentityLogonEvents\n"
                        . "| where Timestamp >= ago(7d)\n"
                        . "| extend hasSourceIP = iff(IPAddress != \"\", \"RESOLVED\", \"NULL\")\n"
                        . "| summarize eventCount=count(),\n"
                        . "            resolvedIPs=make_set_if(IPAddress, IPAddress != \"\"),\n"
                        . "            resolvedDevices=make_set_if(DeviceName, DeviceName != \"NULL\")\n"
                        . "  by AccountName, hasSourceIP\n"
                        . "| order by AccountName asc\n\n"
                        . "// 4. ตรวจจับ Process ผิดปกติด้วย Anomaly Score (PowerShell / CMD)\n"
                        . "DeviceProcessEvents\n"
                        . "| where FileName in~ (\"powershell.exe\", \"cmd.exe\", \"rundll32.exe\")\n"
                        . "| summarize Count = count() by bin(Timestamp, 1h), DeviceName, FileName\n"
                        . "| make-series ProcessCount = sum(Count) on Timestamp from ago(14d) to now() step 1h by DeviceName\n"
                        . "| extend (anomalies, score, baseline) = series_decompose_anomalies(ProcessCount, 2.5)\n\n"
                        . "// 5. ตรวจจับ Password Spraying (เจาะหลายบัญชีจาก IP เดียว)\n"
                        . "IdentityLogonEvents\n"
                        . "| where ActionType == \"LogonFailed\"\n"
                        . "| summarize FailedAttempts = count(), TargetAccounts = dcount(AccountUpn) by IPAddress, bin(Timestamp, 15m)\n"
                        . "| where TargetAccounts >= 5",
                    'type'      => 'resource',
                    'category'  => 'tech',
                    'tags'      => ['#kql', '#microsoft-sentinel', '#defender', '#threat-hunting', '#soc'],
                    'color'     => 'purple',
                    'is_pinned' => true,
                    'sort_order'=> 1,
                ],
                [
                    'user_id'   => $userId,
                    'title'     => '✉️ SOC Incident Response & Communication Playbook (ServiceDesk & User Templates)',
                    'content'   => "คลังเทมเพลตมาตรฐานในการสื่อสารและประสานงานของทีม CSOC:\n\n"
                        . "1. เทมเพลตประสานงาน ServiceDesk เปิด Ticket & ดำเนินการ:\n"
                        . "\"Dear ServiceDesk,\n"
                        . "Could you please create a ticket and inform ticket id to us.\n"
                        . "Activity: [Suspicious Logon / Password Spray / Malware Alert]\n"
                        . "Username: [Target User]\n"
                        . "Action Required: [Revoke active sessions / Force password reset / Clear credentials failed device]\"\n\n"
                        . "2. เทมเพลตสอบถาม User กรณีตรวจพบกิจกรรม Privileged หรือต่างถิ่น:\n"
                        . "\"ตรวจพบการเข้าสู่ระบบจากบัญชีของท่าน ในวันที่ [วัน/เวลา] จาก IP [IP Address]\n"
                        . "รบกวนดำเนินการตรวจสอบว่ากิจกรรมดังกล่าวเป็นการใช้งานจริงของท่านหรือไม่:\n"
                        . "- หากเป็นท่าน: ระบบจะบันทึก Trust เพื่อไม่ให้แจ้งเตือนซ้ำ\n"
                        . "- หากไม่ใช่ท่าน: รบกวนแจ้งกลับ CSOC ทันที และเปลี่ยนรหัสผ่านเพื่อความปลอดภัย\"\n\n"
                        . "3. เทมเพลตปลดล็อกบัญชี (Account Locked / False Positive Review):\n"
                        . "\"The CSOC has completed its review and that the activity does not constitute a high-risk attack. Kindly proceed with requesting IFM/O to unlock the account.\"\n\n"
                        . "4. เทมเพลตอีเมลเตือนลิงก์อันตราย (Malicious URL Removed After Delivery):\n"
                        . "\"ระบบ Microsoft Defender ได้ดำเนินการลบอีเมลที่มี URL อันตรายออกจากกล่องข้อความของท่านโดยอัตโนมัติแล้ว รบกวนงดคลิกลิงก์หรือเปิดไฟล์แนบที่ไม่คุ้นเคย\"",
                    'type'      => 'resource',
                    'category'  => 'work',
                    'tags'      => ['#soc-playbook', '#servicedesk', '#incident-response', '#email-template'],
                    'color'     => 'blue',
                    'is_pinned' => true,
                    'sort_order'=> 2,
                ],
                [
                    'user_id'   => $userId,
                    'title'     => '🔄 24/7 CSOC Operations: Shift Handover & UEBA Alert Triage Protocol',
                    'content'   => "มาตรฐานการปฏิบัติงานและส่งมอบกะประจำวันของทีม CSOC:\n\n"
                        . "• วงรอบการส่งมอบกะ (Shift Cycles):\n"
                        . "  - กะเช้า (Morning) ➔ กะบ่าย (Afternoon) ➔ กะดึก (Night)\n\n"
                        . "• โครงสร้างการบันทึก DailyShiftSummary:\n"
                        . "  1. สรุปภาพรวมเคส Alerts & Incidents: ปิดเคสแล้ว / รอตรวจสอบ / เคสเร่งด่วน\n"
                        . "  2. รายการบัญชีเฝ้าระวังพิเศษ (Watchlist): ผู้บริหาร, บัญชีแอดมิน (Privileged Accounts)\n"
                        . "  3. รายการที่รอประสานงาน (Pending User Response / Pending IT Support)\n"
                        . "  4. การอัปเดตตารางกะและผู้รับผิดชอบ\n\n"
                        . "• เกณฑ์การวิเคราะห์ UEBA (User and Entity Behavior Analytics):\n"
                        . "  - Anomalous Token / Atypical Travel: ตรวจสอบความสมเหตุสมผลของการเดินทาง หากข้ามประเทศในเวลาสั้น ให้สั่ง Revoke Sessions ทันที\n"
                        . "  - Screen Capture via PowerShell: ยืนยันว่าเป็นการปฏิบัติงานของ User จริงหรือไม่ หากผิดปกติให้ตัดการเชื่อมต่อเครื่องทันที",
                    'type'      => 'area',
                    'category'  => 'work',
                    'tags'      => ['#csoc-ops', '#shift-handover', '#ueba', '#operations'],
                    'color'     => 'amber',
                    'is_pinned' => true,
                    'sort_order'=> 3,
                ],
                [
                    'user_id'   => $userId,
                    'title'     => '🛡️ SOC Incident Case Studies: สรุปรูปแบบการโจมตีจริงจาก 575 เคส',
                    'content'   => "บทเรียนและรูปแบบการโจมตีที่พบบ่อยจากการสืบสวนเคสจริงกว่า 575 เคส (PTTEP / Banpu):\n\n"
                        . "1. Password Spraying & Brute Force:\n"
                        . "   - พุ่งเป้าไปที่ Cloud PC / Azure Bastion / RDP Services\n"
                        . "   - รูปแบบ: ยิงมาจาก IP ต่างประเทศ สุ่มรหัสผ่านเดิมซ้ำๆ กับหลาย Username\n"
                        . "   - มาตรการ: Block IP, เปิดใช้ MFA/FIDO2 และตั้ง Conditional Access\n\n"
                        . "2. Anomalous Token & Compromised Sessions:\n"
                        . "   - การขโมย Session Cookie/Token แล้วนำไป Sign-in จากเครื่องแฮกเกอร์\n"
                        . "   - สังเกตจาก IP ที่ล็อกอินสำเร็จเป็น IP โฮสติ้งต่างประเทศ\n"
                        . "   - มาตรการ: สั่ง Revoke All Active Sessions ใน Entra ID ทันที\n\n"
                        . "3. Malicious Screen Capture & Reconnaissance:\n"
                        . "   - โค้ด PowerShell แอบถ่ายภาพหน้าจอส่งออกไปยังเซิร์ฟเวอร์ภายนอก\n"
                        . "   - มาตรการ: กักกันเครื่อง (Isolate Device) ผ่าน Microsoft Defender for Endpoint ทันที",
                    'type'      => 'archive',
                    'category'  => 'tech',
                    'tags'      => ['#case-study', '#attack-vectors', '#soc', '#incident-response'],
                    'color'     => 'rose',
                    'is_pinned' => false,
                    'sort_order'=> 4,
                ],
                [
                    'user_id'   => $userId,
                    'title'     => '🎯 Project Roadmap: SOC Intelligence Hub & Life Operating System',
                    'content'   => "แผนงานยกระดับ Second Brain และระบบสนับสนุนงาน SOC บน trimongkol.com:\n\n"
                        . "Phase 1 [เสร็จสิ้น]: สกัดองค์ความรู้จาก Google Keep (801 โน้ต) รวบเป็น Master KQL & Playbooks\n"
                        . "Phase 2 [กำลังดำเนินการ]: พัฒนาระบบค้นหา KQL Query ด่วนผ่านหน้าเว็บ Second Brain พร้อมปุ่ม One-Click Copy\n"
                        . "Phase 3 [เป้าหมายถัดไป]: เชื่อมโยง Webhook แจ้งเตือนสถานะสุขภาพ (Galaxy Watch 7) เข้ากับตารางเข้ากะ SOC เพื่อเตือนให้พักผ่อนอย่างเพียงพอก่อนขึ้นเวรดึก",
                    'type'      => 'project',
                    'category'  => 'work',
                    'tags'      => ['#roadmap', '#second-brain', '#soc-automation'],
                    'color'     => 'cyan',
                    'is_pinned' => false,
                    'sort_order'=> 5,
                ],
            ];

            foreach ($socNotes as $noteData) {
                BrainNote::create($noteData);
            }
        } catch (\Throwable $e) {
            \Log::warning('SecondBrain SOC Seeder error: ' . $e->getMessage());
        }
    }

    private function seedRsuClinicKnowledge(int $userId): void
    {
        try {
            $exists = BrainNote::where('user_id', $userId)
                ->where('title', 'like', '%RSU Medical Clinic%')
                ->exists();

            if ($exists) {
                return;
            }

            $rsuNotes = [
                [
                    'user_id'   => $userId,
                    'title'     => '🏥 RSU Medical Clinic Services — ระบบ ERP บริหารคลินิกแพทย์ ม.รังสิต (Full Stack)',
                    'content'   => "โปรเจกต์ระดับ Enterprise ERP บริหารคลินิกแพทย์ มหาวิทยาลัยรังสิต ครบวงจร (Path: C:\\xampp\\htdocs\\RSU-Medical-Clinic-Services)\n"
                        . "Stack: PHP 8 + MySQL (PDO Singleton) + Tailwind CSS + Vanilla JS + Chart.js + SweetAlert2 + LINE Messaging API + Gemini 2.5 Flash\n\n"
                        . "✅ 9 โมดูลหลักที่พัฒนาเสร็จสมบูรณ์:\n"
                        . "1. Patient Billing & AR Aging: ระบบคิดค่ารักษา ออกใบแจ้งหนี้ รับชำระเงิน ซิงก์เข้า Cash Book อัตโนมัติ และรายงานลูกหนี้คงค้าง 4 ช่วงอายุ (0-30 / 31-60 / 61-90 / 90+ วัน)\n"
                        . "2. Thai Payroll System: ระบบเงินเดือนพนักงานคลินิก คำนวณภาษีหัก ณ ที่จ่าย ภ.ง.ด.1 แบบขั้นบันได, ประกันสังคม (SSO), กองทุนสำรองเลี้ยงชีพ (PF) และออกสลิปเงินเดือน A4\n"
                        . "3. Central Person Directory (sys_persons): ทะเบียนบุคคลกลาง Single Source of Truth รวมข้อมูลนักศึกษา บุคลากร และบุคคลภายนอกกว่า 35,500+ รายการ พร้อมระบบ Audit Trail\n"
                        . "4. Nurse Scheduler UI: ระบบจัดตารางเวรพยาบาลรายเดือนแบบ Brush-Paint ลากระบายสีเวร คำนวณชั่วโมงทำงานและ OT อัตโนมัติ\n"
                        . "5. Scholarship Portal: ระบบจัดการนักศึกษาทุน ลงเวลาเข้า-ออกเวร ดูประวัติย้อนหลังรายคน และอนุมัติจ่ายเบี้ยเลี้ยงรายเดือน\n"
                        . "6. e-Campaign & Walk-in QR: ระบบเปิดแคมเปญจองคิวฉีดวัคซีน/ตรวจสุขภาพ พร้อมระบบ Walk-in QR รับคิวเสริมหน้างาน\n"
                        . "7. LINE Official Chat Phase 2: ระบบ CRM แชทคนไข้ มี Gemini AI ช่วยร่างคำตอบ, แท็กเคส, โน้ตภายใน และระบบ Proxy โหลดไฟล์มีเดียปลอดภัย\n"
                        . "8. e_Borrow & Consumables: ระบบยืม-คืนครุภัณฑ์ทางการแพทย์และตัดสต็อกเวชภัณฑ์สิ้นเปลือง\n"
                        . "9. Identity, RBAC & ISO 27001 Governance: ระบบสิทธิ์แบบ Flag-based แยกตามตำแหน่งงาน และบันทึกประวัติการเข้าถึงข้อมูล",
                    'type'      => 'archive',
                    'category'  => 'work',
                    'tags'      => ['#rsu-clinic', '#erp', '#php8', '#mysql', '#line-oa', '#gemini-ai'],
                    'color'     => 'emerald',
                    'is_pinned' => true,
                    'sort_order'=> 1,
                ],
                [
                    'user_id'   => $userId,
                    'title'     => '🧠 Architecture Pattern: ระบบความจำ AI (hot.md + CLAUDE.md + AI/logs)',
                    'content'   => "สูตรจัดโครงสร้าง Context Persistence จากโปรเจกต์ RSU Clinic เพื่อให้ AI Agent เข้าใจโปรเจกต์ขนาดใหญ่ได้ภายใน 60 วินาทีโดยไม่ต้องไล่อ่านโค้ดใหม่ทั้งหมด:\n\n"
                        . "1. hot.md (Root Snapshot): เก็บสถานะปัจจุบัน, Phase ที่เพิ่งทำจบ, และการตัดสินใจล่าสุด (อัปเดตคู่กับทุก Commit สำคัญ)\n"
                        . "2. CLAUDE.md / GEMINI.md: เก็บกฎเหล็กถาวรของโปรเจกต์, Coding Conventions, และ Design System Tokens\n"
                        . "3. AI/logs/YYYY-MM-DD-topic.md (Episodic Memory): บันทึกเหตุผลเบื้องหลังการตัดสินใจแบบ Append-only ห้ามแก้ไฟล์เก่าเพื่อรักษา Audit Trail\n"
                        . "4. AI/knowledge/ (Semantic Memory): สกัดเฉพาะ Pattern หรือสูตรโค้ดที่ใช้ซ้ำเกิน 2 ครั้งขึ้นไปมาเก็บเป็นคู่มืออ้างอิง",
                    'type'      => 'resource',
                    'category'  => 'tech',
                    'tags'      => ['#ai-memory', '#architecture', '#claude', '#best-practice', '#rsu-clinic'],
                    'color'     => 'purple',
                    'is_pinned' => true,
                    'sort_order'=> 2,
                ],
                [
                    'user_id'   => $userId,
                    'title'     => '🛠️ Code Recipes: Portal-Escape Modal (z-9000), Self-Healing DDL & Thai Tax Engine',
                    'content'   => "คลังเทคนิคการเขียนโค้ดที่สกัดจาก RSU Medical Clinic Services สำหรับนำไป Reuse ในโปรเจกต์ถัดไป:\n\n"
                        . "• Portal-Escape Modal Pattern: แก้ปัญหา Modal จมใต้ตารางหรือโดน overflow-hidden ตัดขอบ ด้วยการ Teleport DOM node ไปที่ document.body พร้อมตั้ง z-index: 9000 และล็อก Body Scroll\n"
                        . "• Self-Healing Schema (ensure_schema): เขียนฟังก์ชันตรวจสอบและสร้างตาราง/คอลัมน์แบบ Idempotent บน Runtime ป้องกันระบบล่ม HTTP 500 ตอน Deploy\n"
                        . "• Transactional Cash Book Sync: ใช้ SELECT ... FOR UPDATE ล็อกแถวคู่กับ finance_sync_upsert() บังคับว่าถ้ายอดเงินซิงก์เข้าบัญชีกลางไม่สำเร็จ ต้อง Rollback ทั้งหมดทันที\n"
                        . "• Thai PIT Tax Engine: สูตรคำนวณภาษีหัก ณ ที่จ่าย ภ.ง.ด.1 (รายได้ต่อเดือน × 12 หักค่าใช้จ่าย 40% ไม่เกิน 100,000 หักลดหย่อนส่วนตัว 60,000 และประกันสังคมสูงสุด 9,000 แล้วคำนวณภาษีขั้นบันได 0–35% หาร 12)",
                    'type'      => 'resource',
                    'category'  => 'tech',
                    'tags'      => ['#frontend', '#database', '#modal', '#tax-engine', '#php8'],
                    'color'     => 'cyan',
                    'is_pinned' => false,
                    'sort_order'=> 3,
                ],
                [
                    'user_id'   => $userId,
                    'title'     => '🚀 RSU Clinic Next Roadmap: e-Campaign LINE Reminder & Person Directory Phase 5',
                    'content'   => "รายการ Backlog ที่รอพัฒนาต่อยอดในโปรเจกต์ RSU Medical Clinic Services:\n\n"
                        . "1. e-Campaign Part B (LINE Reminder): ระบบแจ้งเตือนผู้ลงทะเบียนให้มาเข้าร่วมกิจกรรมผ่าน LINE Official 2 รอบ (ล่วงหน้า 1 วัน และเช้าวันงาน) ต่อยอดจาก cron/send_appointment_reminders.php\n"
                        . "2. Central Person Directory Phase 5–6: ผูกตารางแอดมิน (sys_admins) เข้ากับทะเบียนบุคคลกลาง (sys_persons) ผ่าน linked_person_id เพื่อให้ Audit Log อ้างอิงตัวตนจริงได้ 100%\n"
                        . "3. Insurance AR Bridge: เชื่อม Foreign Key จากรายงานลูกหนี้คงค้าง (payer_type='insurance') เข้ากับรหัสบริษัทประกันในระบบ Insurance Hub",
                    'type'      => 'project',
                    'category'  => 'work',
                    'tags'      => ['#rsu-clinic', '#roadmap', '#line-api', '#backlog'],
                    'color'     => 'amber',
                    'is_pinned' => false,
                    'sort_order'=> 4,
                ],
                [
                    'user_id'   => $userId,
                    'title'     => '🛡️ Cybersecurity SOC & ISO 27001 Clinic Security Playbook',
                    'content'   => "มาตรฐานความมั่นคงปลอดภัยไซเบอร์และการดูแลระบบแบบ Security Operations (SOC):\n\n"
                        . "• Full-Depth Access Gate: บังคับตรวจสิทธิ์ 3 ชั้นเสมอ (Sidebar Menu + Page Init Guard + AJAX Endpoint) ป้องกันช่องโหว่ซ่อนแค่ปุ่มหน้าเว็บแต่ยิง Request ตรงเข้า API ได้\n"
                        . "• Secure Media Proxy: ดึงไฟล์จาก LINE Content API ผ่านตัวกลางที่บังคับ Strict MIME Whitelist (jpg/png/pdf/mp4) และใส่ CSP Sandbox ไม่แสดง Header ดิบจากภายนอก\n"
                        . "• LLM Prompt Injection Defense: แยก Role Turns ชัดเจนในการส่งข้อความแชทคนไข้ให้ AI ประมวลผล\n"
                        . "• แผนถัดไป: เตรียมนำเข้าคลังโน้ต Cybersecurity SOC จาก Google Keep (ผ่าน Google Takeout) มาคัดแยกหมวดหมู่ Incident Response, IOCs และ Log Analysis ลงใน Second Brain",
                    'type'      => 'area',
                    'category'  => 'tech',
                    'tags'      => ['#cybersecurity', '#soc', '#iso27001', '#security', '#playbook'],
                    'color'     => 'rose',
                    'is_pinned' => true,
                    'sort_order'=> 5,
                ],
            ];

            foreach ($rsuNotes as $noteData) {
                BrainNote::create($noteData);
            }
        } catch (\Throwable $e) {
            \Log::warning('SecondBrain RSU Seeder error: ' . $e->getMessage());
        }
    }

    private function seedYouTubeWatchSearchProfile(int $userId): void
    {
        try {
            $exists = BrainNote::where('user_id', $userId)
                ->where('title', 'like', '%YouTube Watch & Search%')
                ->exists();

            if ($exists) {
                return;
            }

            $ytNotes = [
                [
                    'user_id'   => $userId,
                    'title'     => '🧠 วิเคราะห์บุคลิกและตัวตนจาก YouTube Watch & Search (พ.ค. 2568 – พ.ย. 2569)',
                    'content'   => "สกัดพฤติกรรมจาก Google Takeout จริง (ไม่รวม Watch Later) — ประวัติการดู 38,174 ครั้ง (27,077 คลิปไม่ซ้ำ) + ประวัติการค้นหา 3,957 ครั้ง:\n\n"
                        . "1. Emotional Immersion & Deep Focus: เปิดซาวด์แทร็ก/เพลงบรรเลงสายอนิเมะและไซไฟซ้ำเป็นร้อยรอบ (เช่น Cyberpunk: Edgerunners 'I Really Want To Stay At Your House' รวม 4 เวอร์ชัน > 274 รอบ, Hiroyuki SAWANO, RADWIMPS, Arcane, How to Train Your Dragon) สะท้อนคนอินกับอารมณ์เรื่องราวและใช้ดนตรีสร้างสมาธิเวลาทำงาน/ขับรถ\n"
                        . "2. Builder & Gear-Head Pragmatist: ค้นหาสาย Tech/AI/Gadgets (S24 Ultra, Galaxy Watch 7, ThinkPad, AI/Dev = 7.2%) คู่กับมอเตอร์ไซค์ทัวริง (Zontes 150X, Fazzio, Motovision X1 Lite = 5.2%) สไตล์ศึกษาสเปกละเอียดก่อนลงมือทำหรือซื้อจริง\n"
                        . "3. Shift-Driven Unwind Rhythm: ช่วงดู YouTube พีกสุดคือ 18:00 น. (2,457 ครั้ง) และ 22:00 น. (2,324 ครั้ง) สลับระหว่างสายผ่อนคลาย (GTA FiveM ลุงเอ 124 ครั้ง / คุณหนูเอ 29 ครั้ง) และเพลงไทยอินดี้/ฮิปฮอป (YOUNGOHM, YEW, YONIA)",
                    'type'      => 'area',
                    'category'  => 'idea',
                    'tags'      => ['#youtube-takeout', '#persona', '#self-reflection', '#habits'],
                    'color'     => 'purple',
                    'is_pinned' => true,
                    'sort_order'=> 6,
                ],
                [
                    'user_id'   => $userId,
                    'title'     => '🎧 YouTube Top Replay (จาก Watch History 38,174 ครั้ง)',
                    'content'   => "อันดับคลิปและเพลงที่เปิดดู/ฟังซ้ำมากที่สุดจาก Google Takeout (19 พ.ค. 2568 – 11 พ.ย. 2569):\n\n"
                        . "1. 108x — I Really Want To Stay At Your House | EPIC EMOTIONAL COVER (Samuel Kim Music) https://youtu.be/_AAdae7diOU\n"
                        . "2. 90x — RADWIMPS - 二人の時間 https://youtu.be/fOu00p1i5fE\n"
                        . "3. 75x — ซอยข่อยแน - YONIA https://youtu.be/HR2ZQefLIgI\n"
                        . "4. 74x — I really want to stay at your house - Doomer Wave https://youtu.be/wBDVaAPujSU\n"
                        . "5. 67x — CHAINSAW MAN ED / ano「ちゅ、多様性。」bass cover https://youtu.be/5tirDksDIs8\n"
                        . "6. 66x — YOUNGOHM - Benz ดำ https://youtu.be/QkiF57kn6RI\n"
                        . "7. 56x — YEW - ลมแล้ง feat. LANDOKMAI https://youtu.be/E_wrJhXm7jo\n"
                        . "8. 55x — Fireworks Festival (RADWIMPS) https://youtu.be/dDR1DFtik0E\n"
                        . "9. 55x — Mạc Vấn Quy Kỳ Phonk https://youtu.be/7taUCxVKqCA\n"
                        . "10. 53x — Voices of the Chord (Hiroyuki SAWANO) https://youtu.be/Cxz-W6rNscM\n"
                        . "11. 52x — ano「ちゅ、多様性。」MV https://youtu.be/-lec--FlSJ4\n"
                        . "12. 50x — Sacred Play Secret Place (matryoshka) https://youtu.be/XX2gPs44fxg\n"
                        . "13. 50x — 進撃gt20130218巨人 (Hiroyuki SAWANO) https://youtu.be/47RK0qI2DiI\n"
                        . "14. 49x — YOUNGOHM - เรื่องราวความรักในตำนาน https://youtu.be/ZSE7C9dAanw\n"
                        . "15. 48x — YOUNGOHM - เจิดจรัส https://youtu.be/Rj2iemP05B8\n"
                        . "16. 47x — Call of Silence (Hiroyuki SAWANO) https://youtu.be/B-1ZzOp0UUA\n"
                        . "17. 39x — Test Driving Toothless (John Powell) https://youtu.be/Q4ksp-14-Og\n"
                        . "18. 39x — Fantastic (Arcane S2 - King Princess) https://youtu.be/G-_4HKPqLso",
                    'type'      => 'resource',
                    'category'  => 'general',
                    'tags'      => ['#youtube-takeout', '#music', '#soundtrack', '#top-replay'],
                    'color'     => 'indigo',
                    'is_pinned' => false,
                    'sort_order'=> 7,
                ],
                [
                    'user_id'   => $userId,
                    'title'     => '🔍 YouTube Search Trends & พฤติกรรมรายชั่วโมง (จาก 3,957 คำค้นหา)',
                    'content'   => "สรุปการวิเคราะห์ประวัติการค้นหา 3,957 ครั้ง (20 พ.ค. 2568 – 11 พ.ย. 2569):\n\n"
                        . "• คำค้นหายอดฮิต: ลุงเอ (124), i really want to stay at your house (31), คุณหนูเอ (29), zontes 150x (35 รวมทุกรูปแบบ), fazzio (23 รวมทุกรูปแบบ), motovision x1 lite (13), ททท (12), s24 ultra (11), project hail mary soundtrack (10), zoltraak / frieren (20 รวมกัน), samsung galaxy watch 7 (10), ปริมคุงเล่าเรื่อง (10)\n"
                        . "• กลุ่มความสนใจหลักในคำค้น: Tech/AI/Gadgets 283 (7.2%), Gaming/FiveM 276 (7.0%), Music/OST/Bass 241 (6.1%), Motorcycles/Touring 204 (5.2%), Anime/Sci-Fi 105 (2.7%)\n"
                        . "• เดือนที่รับชมสูงสุด: ก.ค. 2569 (2,759), ต.ค. 2568 (2,476), ต.ค. 2569 (2,411)\n"
                        . "• ชั่วโมงพีกตลอด 24 ชม.: 18:00 น. (2,457), 22:00 น. (2,324), 17:00 น. (2,170), 21:00 น. (2,103)",
                    'type'      => 'resource',
                    'category'  => 'tech',
                    'tags'      => ['#youtube-takeout', '#search-trends', '#analytics', '#interests'],
                    'color'     => 'cyan',
                    'is_pinned' => false,
                    'sort_order'=> 8,
                ],
            ];

            foreach ($ytNotes as $noteData) {
                BrainNote::create($noteData);
            }
        } catch (\Throwable $e) {
            \Log::warning('SecondBrain YouTube Seeder error: ' . $e->getMessage());
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

    private function authorizeNote(BrainNote $note): void
    {
        abort_unless($note->user_id === $this->resolvePortfolioUserId(), 403);
    }

    public function index(Request $request): View
    {
        $this->ensureTableExists();

        $userId = $this->resolvePortfolioUserId();

        $activeType = $request->query('type', 'all');
        $activeCategory = $request->query('category', 'all');
        $activeTag = $request->query('tag');
        $searchQuery = trim($request->query('q', ''));

        // All notes query
        $query = BrainNote::forUser($userId);

        if ($activeType !== 'all') {
            $query->where('type', $activeType);
        }

        if ($activeCategory !== 'all') {
            $query->where('category', $activeCategory);
        }

        if ($activeTag) {
            $tagWithHash = str_starts_with($activeTag, '#') ? $activeTag : '#' . $activeTag;
            $query->whereJsonContains('tags', $tagWithHash);
        }

        if ($searchQuery !== '') {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('title', 'like', "%{$searchQuery}%")
                  ->orWhere('content', 'like', "%{$searchQuery}%")
                  ->orWhere('tags', 'like', "%{$searchQuery}%");
            });
        }

        $allNotes = $query->orderByDesc('is_pinned')
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->get();

        // Stats calculation
        $baseQuery = BrainNote::forUser($userId);
        $totalNotes = (clone $baseQuery)->count();
        $inboxCount = (clone $baseQuery)->where('type', 'inbox')->count();
        $projectCount = (clone $baseQuery)->where('type', 'project')->count();
        $areaCount = (clone $baseQuery)->where('type', 'area')->count();
        $resourceCount = (clone $baseQuery)->where('type', 'resource')->count();
        $archiveCount = (clone $baseQuery)->where('type', 'archive')->count();

        // Extract all unique tags
        $allUserNotes = BrainNote::forUser($userId)->select('tags')->get();
        $tagsList = [];
        foreach ($allUserNotes as $n) {
            if (is_array($n->tags)) {
                foreach ($n->tags as $t) {
                    $cleaned = trim($t);
                    if ($cleaned) {
                        $tagsList[$cleaned] = ($tagsList[$cleaned] ?? 0) + 1;
                    }
                }
            }
        }
        arsort($tagsList);

        return view('portfolio.brain', compact(
            'allNotes',
            'totalNotes',
            'inboxCount',
            'projectCount',
            'areaCount',
            'resourceCount',
            'archiveCount',
            'tagsList',
            'activeType',
            'activeCategory',
            'activeTag',
            'searchQuery'
        ));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->ensureTableExists();

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'content'     => 'nullable|string',
            'type'        => 'required|in:inbox,project,area,resource,archive',
            'category'    => 'nullable|string|max:50',
            'tags'        => 'nullable',
            'color'       => 'nullable|string|max:30',
            'url'         => 'nullable|url|max:500',
            'is_pinned'   => 'nullable|boolean',
        ]);

        $data['user_id'] = $this->resolvePortfolioUserId();
        $data['category'] = $data['category'] ?? 'general';
        $data['color'] = $data['color'] ?? 'slate';
        $data['is_pinned'] = $request->boolean('is_pinned');

        // Parse tags
        $tags = [];
        if (!empty($data['tags'])) {
            if (is_string($data['tags'])) {
                $parts = explode(',', $data['tags']);
                foreach ($parts as $p) {
                    $p = trim($p);
                    if ($p) {
                        $tags[] = str_starts_with($p, '#') ? $p : '#' . $p;
                    }
                }
            } elseif (is_array($data['tags'])) {
                $tags = $data['tags'];
            }
        }

        // Auto-extract hashtags from content if any
        if (!empty($data['content'])) {
            preg_match_all('/#([\w\x{0E00}-\x{0E7F}]+)/u', $data['content'], $matches);
            if (!empty($matches[0])) {
                foreach ($matches[0] as $foundTag) {
                    if (!in_array($foundTag, $tags, true)) {
                        $tags[] = $foundTag;
                    }
                }
            }
        }

        $data['tags'] = array_values(array_unique($tags));

        $note = BrainNote::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'note'    => $note,
                'message' => 'บันทึกข้อมูลเข้าสู่ Second Brain เรียบร้อยแล้ว',
            ]);
        }

        return redirect()->route('portfolio.brain.index')
            ->with('status', 'บันทึกข้อมูลเข้าสู่ Second Brain เรียบร้อยแล้ว');
    }

    public function update(Request $request, BrainNote $note): RedirectResponse|JsonResponse
    {
        $this->authorizeNote($note);

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'content'     => 'nullable|string',
            'type'        => 'required|in:inbox,project,area,resource,archive',
            'category'    => 'nullable|string|max:50',
            'tags'        => 'nullable',
            'color'       => 'nullable|string|max:30',
            'url'         => 'nullable|url|max:500',
            'is_pinned'   => 'nullable|boolean',
            'is_archived' => 'nullable|boolean',
        ]);

        $data['category'] = $data['category'] ?? $note->category;
        $data['color'] = $data['color'] ?? $note->color;
        $data['is_pinned'] = $request->boolean('is_pinned');
        $data['is_archived'] = $request->boolean('is_archived');

        // Parse tags
        $tags = [];
        if (!empty($data['tags'])) {
            if (is_string($data['tags'])) {
                $parts = explode(',', $data['tags']);
                foreach ($parts as $p) {
                    $p = trim($p);
                    if ($p) {
                        $tags[] = str_starts_with($p, '#') ? $p : '#' . $p;
                    }
                }
            } elseif (is_array($data['tags'])) {
                $tags = $data['tags'];
            }
        }

        if (!empty($data['content'])) {
            preg_match_all('/#([\w\x{0E00}-\x{0E7F}]+)/u', $data['content'], $matches);
            if (!empty($matches[0])) {
                foreach ($matches[0] as $foundTag) {
                    if (!in_array($foundTag, $tags, true)) {
                        $tags[] = $foundTag;
                    }
                }
            }
        }

        $data['tags'] = array_values(array_unique($tags));

        $note->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'note'    => $note,
                'message' => 'อัปเดตโน้ตเรียบร้อยแล้ว',
            ]);
        }

        return redirect()->route('portfolio.brain.index')
            ->with('status', 'อัปเดตโน้ตเรียบร้อยแล้ว');
    }

    public function togglePin(BrainNote $note): JsonResponse|RedirectResponse
    {
        $this->authorizeNote($note);
        $note->is_pinned = !$note->is_pinned;
        $note->save();

        if (request()->wantsJson()) {
            return response()->json([
                'success'   => true,
                'is_pinned' => $note->is_pinned,
                'message'   => $note->is_pinned ? 'ปักหมุดโน้ตแล้ว 📌' : 'ปลดหมุดโน้ตแล้ว',
            ]);
        }

        return redirect()->route('portfolio.brain.index');
    }

    public function destroy(BrainNote $note): JsonResponse|RedirectResponse
    {
        $this->authorizeNote($note);
        $note->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'ลบโน้ตเรียบร้อยแล้ว',
            ]);
        }

        return redirect()->route('portfolio.brain.index')
            ->with('status', 'ลบโน้ตเรียบร้อยแล้ว');
    }
}
