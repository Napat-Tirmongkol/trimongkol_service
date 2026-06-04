<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Models\SocialPost;
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
    public int $timeout = 60;

    public function __construct(public readonly int $postId) {}

    public function handle(): void
    {
        $post = SocialPost::find($this->postId);
        if (! $post || $post->status !== 'pending_ai') {
            return;
        }

        $apiKey = $this->resolveApiKey();
        if (! $apiKey) {
            $post->update(['status' => 'failed', 'error_message' => 'Gemini API key not configured']);
            return;
        }

        $prompt = $this->buildPrompt($post->title, $post->source_url);

        $response = Http::timeout(50)->post(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key='.$apiKey,
            [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['maxOutputTokens' => 1024],
            ]
        );

        if (! $response->successful()) {
            $post->update([
                'status' => 'failed',
                'error_message' => 'Gemini API error: '.$response->status().' '.$response->body(),
            ]);
            return;
        }

        $content = $response->json('candidates.0.content.parts.0.text', '');
        if (empty($content)) {
            $post->update(['status' => 'failed', 'error_message' => 'Empty response from Gemini']);
            return;
        }

        $post->update(['ai_content' => trim($content), 'status' => 'draft']);
    }

    private function buildPrompt(string $title, string $url): string
    {
        return <<<PROMPT
คุณเป็น Social Media Editor ของเว็บไซต์ข่าวไทย ช่วยเขียนโพสต์ Facebook จากหัวข้อข่าวนี้:

หัวข้อ: {$title}
ลิงก์: {$url}

คำแนะนำ:
- เขียนโพสต์เป็นภาษาไทย ไม่เกิน 3 ย่อหน้า
- ย่อหน้าแรก: ใจความสำคัญของข่าว (น่าสนใจ กระตุ้นให้อ่านต่อ)
- ย่อหน้าสอง: รายละเอียดเพิ่มเติมหรือบริบท (ถ้ามี)
- ย่อหน้าสุดท้าย: CTA + ลิงก์ เช่น "อ่านต่อได้ที่ → {$url}"
- เพิ่ม hashtag ที่เหมาะสม 3-5 ตัว ท้ายโพสต์
- ใช้ภาษาเป็นทางการแต่เข้าถึงง่าย ไม่เป็นทางการจนเกินไป
- ห้ามเขียนเนื้อหาที่ไม่มีอยู่ในหัวข้อ (อย่าแต่งเนื้อหา)

ตอบเฉพาะตัวโพสต์ Facebook เท่านั้น ไม่ต้องมีคำอธิบายเพิ่มเติม
PROMPT;
    }

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
        SocialPost::where('id', $this->postId)->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);
    }
}
