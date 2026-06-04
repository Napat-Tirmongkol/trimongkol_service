<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Models\SocialPost;
use App\Services\AgentLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeneratePostContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(public readonly int $postId) {}

    public function handle(): void
    {
        $post = SocialPost::find($this->postId);
        if (! $post || $post->status !== 'pending_ai') {
            return;
        }

        $apiKey = $this->resolveApiKey();
        if (! $apiKey) {
            AgentLogger::error($this->postId, 'ไม่พบ Gemini API Key — กรุณาตั้งค่าใน Settings');
            $post->update(['status' => 'failed', 'error_message' => 'Gemini API key not configured']);
            return;
        }

        AgentLogger::info($this->postId, 'เริ่มต้น AI Agent (Gemini Function Calling)');

        try {
            $start   = microtime(true);
            $content = $this->runAgent($apiKey, $post);
            $total   = (int) ((microtime(true) - $start) * 1000);

            AgentLogger::success($this->postId, 'บันทึก Draft เรียบร้อย', $total);
            $post->update(['ai_content' => trim($content), 'status' => 'draft']);
        } catch (\Throwable $e) {
            AgentLogger::error($this->postId, 'เกิดข้อผิดพลาด: '.$e->getMessage());
            $post->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }

    // ─── Agent Loop ────────────────────────────────────────────────────────────

    private function runAgent(string $apiKey, SocialPost $post): string
    {
        $systemPrompt = implode(' ', [
            'คุณเป็น Social Media Editor ผู้เชี่ยวชาญของเว็บข่าวไทย',
            'คุณมี tool fetch_article_content สำหรับดึงเนื้อหาบทความ — ให้ดึงบทความก่อนเสมอ',
            'หลังอ่านบทความแล้วให้เขียนโพสต์ Facebook เป็นภาษาไทย ไม่เกิน 3 ย่อหน้า',
            'มี hashtag 3-5 ตัว และใส่ลิงก์บทความต้นฉบับท้ายโพสต์',
            'ตอบด้วยตัวโพสต์ Facebook เท่านั้น ไม่ต้องมีคำนำหรือคำอธิบายเพิ่มเติม',
        ]);

        $contents = [[
            'role'  => 'user',
            'parts' => [['text' => "บทความ: \"{$post->title}\"\nURL: {$post->source_url}\n\nกรุณาดึงเนื้อหาบทความแล้วเขียนโพสต์ Facebook เป็นภาษาไทย"]],
        ]];

        $tools = [[
            'function_declarations' => [[
                'name'        => 'fetch_article_content',
                'description' => 'ดึงข้อความเต็มของบทความข่าวจาก URL เพื่อนำไปสรุปและเขียน Facebook post',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => ['url' => ['type' => 'string', 'description' => 'URL ของบทความที่ต้องการดึง']],
                    'required'   => ['url'],
                ],
            ]],
        ]];

        AgentLogger::info($this->postId, "ส่งบทความให้ Agent: \"{$post->title}\"");

        for ($turn = 0; $turn < 6; $turn++) {
            $callStart = microtime(true);

            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key={$apiKey}",
                [
                    'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                    'contents'           => $contents,
                    'tools'              => $tools,
                    'tool_config'        => ['function_calling_config' => ['mode' => 'AUTO']],
                    'generationConfig'   => ['maxOutputTokens' => 1500],
                ]
            );

            $callMs = (int) ((microtime(true) - $callStart) * 1000);

            if (! $response->successful()) {
                throw new \RuntimeException('Gemini API error: '.$response->status().' '.$response->body());
            }

            $candidate = $response->json('candidates.0.content') ?? [];
            $parts     = $candidate['parts'] ?? [];

            $functionCall = null;
            $textContent  = null;

            foreach ($parts as $part) {
                if (isset($part['functionCall'])) {
                    $functionCall = $part['functionCall'];
                }
                if (isset($part['text']) && trim($part['text']) !== '') {
                    $textContent = $part['text'];
                }
            }

            // Agent called a tool
            if ($functionCall) {
                $funcName = $functionCall['name'];
                $funcArgs = $functionCall['args'] ?? [];

                AgentLogger::info($this->postId, "Agent เรียก tool: {$funcName}(".(json_encode($funcArgs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)).')');

                $contents[] = ['role' => 'model', 'parts' => $parts];

                $toolStart  = microtime(true);
                $toolResult = $this->executeTool($funcName, $funcArgs);
                $toolMs     = (int) ((microtime(true) - $toolStart) * 1000);

                if ($funcName === 'fetch_article_content') {
                    if ($toolResult['success'] ?? false) {
                        $len = mb_strlen($toolResult['content']);
                        AgentLogger::success($this->postId, "ดึงบทความสำเร็จ — {$len} ตัวอักษร", $toolMs);
                    } else {
                        AgentLogger::warning($this->postId, 'ดึงบทความไม่ได้: '.($toolResult['content'] ?? ''));
                    }
                }

                $contents[] = [
                    'role'  => 'user',
                    'parts' => [['functionResponse' => ['name' => $funcName, 'response' => $toolResult]]],
                ];

                continue;
            }

            // Agent returned the final Facebook post
            if ($textContent) {
                AgentLogger::success($this->postId, "Gemini เขียนโพสต์เสร็จแล้ว ({$callMs}ms)", $callMs);
                return $textContent;
            }

            throw new \RuntimeException('Empty response from Gemini agent (turn '.$turn.')');
        }

        throw new \RuntimeException('AI Agent เกิน 6 รอบโดยไม่มีผลลัพธ์');
    }

    // ─── Tool Implementations ──────────────────────────────────────────────────

    private function executeTool(string $name, array $args): array
    {
        if ($name === 'fetch_article_content') {
            return $this->fetchArticle($args['url'] ?? '');
        }

        return ['error' => "Unknown tool: {$name}"];
    }

    private function fetchArticle(string $url): array
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'content' => 'URL ไม่ถูกต้อง'];
        }

        try {
            $response = Http::timeout(12)
                ->withUserAgent('Mozilla/5.0 (compatible; TrimongkolBot/1.0)')
                ->get($url);

            if (! $response->successful()) {
                return ['success' => false, 'content' => "HTTP {$response->status()}"];
            }

            $text = strip_tags($response->body());
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim(mb_substr($text, 0, 5000));

            return ['success' => true, 'content' => $text ?: '(ไม่พบเนื้อหาในหน้า)'];
        } catch (\Throwable $e) {
            Log::warning("social agent: fetch_article failed for {$url}: ".$e->getMessage());
            return ['success' => false, 'content' => $e->getMessage()];
        }
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function resolveApiKey(): ?string
    {
        $stored = Setting::where('key', 'social.gemini_key')->value('value');
        if ($stored) {
            try {
                return Crypt::decryptString($stored);
            } catch (\Exception) {
                Log::warning('social: could not decrypt gemini key from settings');
            }
        }

        return config('services.gemini.key') ?: null;
    }

    public function failed(\Throwable $e): void
    {
        AgentLogger::error($this->postId, 'Job failed: '.$e->getMessage());
        SocialPost::where('id', $this->postId)->update([
            'status'        => 'failed',
            'error_message' => $e->getMessage(),
        ]);
    }
}
