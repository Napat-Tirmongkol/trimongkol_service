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

class PublishToFacebookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(public readonly int $postId) {}

    public function handle(): void
    {
        $post = SocialPost::find($this->postId);
        if (! $post || $post->status !== 'approved') {
            return;
        }

        AgentLogger::info($post->id, 'เริ่มต้นส่งโพสต์ไปยัง Facebook');

        ['page_id' => $pageId, 'token' => $token] = $this->resolveCredentials();

        if (! $pageId || ! $token) {
            AgentLogger::error($post->id, 'ไม่พบ Facebook credentials — กรุณาตั้งค่า Page ID และ Token ใน Settings');
            $post->update(['status' => 'failed', 'error_message' => 'Facebook credentials not configured']);
            return;
        }

        AgentLogger::info($post->id, "กำลังโพสต์ไปยัง Facebook Page (ID: {$pageId})...");

        $start    = microtime(true);
        $response = Http::withToken($token)->timeout(25)->post(
            'https://graph.facebook.com/v21.0/me/feed',
            ['message' => $post->ai_content]
        );
        $ms = (int) ((microtime(true) - $start) * 1000);

        if (! $response->successful() || $response->json('error')) {
            $err = $response->json('error.message', $response->body());
            AgentLogger::error($post->id, 'Facebook API ตอบกลับ error: '.$err);
            $post->update(['status' => 'failed', 'error_message' => 'Facebook API: '.$err]);
            return;
        }

        $fbPostId = $response->json('id');
        AgentLogger::success($post->id, "โพสต์ Facebook สำเร็จ (post_id: {$fbPostId})", $ms);

        $post->update([
            'status'           => 'published',
            'facebook_post_id' => $fbPostId,
            'published_at'     => now(),
            'error_message'    => null,
        ]);
    }

    private function resolveCredentials(): array
    {
        $pageId   = Setting::where('key', 'social.facebook_page_id')->value('value');
        $tokenEnc = Setting::where('key', 'social.facebook_page_token')->value('value');
        $token    = null;

        if ($tokenEnc) {
            try {
                $token = Crypt::decryptString($tokenEnc);
            } catch (\Exception) {
                Log::warning('social: could not decrypt facebook token');
            }
        }

        return ['page_id' => $pageId, 'token' => $token];
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
