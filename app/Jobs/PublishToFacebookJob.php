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

        ['page_id' => $pageId, 'token' => $token] = $this->resolveCredentials();

        if (! $pageId || ! $token) {
            $post->update(['status' => 'failed', 'error_message' => 'Facebook credentials not configured']);
            return;
        }

        $message = $post->ai_content;

        $response = Http::timeout(25)->post(
            "https://graph.facebook.com/v21.0/{$pageId}/feed",
            ['message' => $message, 'access_token' => $token]
        );

        if (! $response->successful() || $response->json('error')) {
            $err = $response->json('error.message', $response->body());
            $post->update(['status' => 'failed', 'error_message' => 'Facebook API: '.$err]);
            return;
        }

        $post->update([
            'status' => 'published',
            'facebook_post_id' => $response->json('id'),
            'published_at' => now(),
            'error_message' => null,
        ]);
    }

    private function resolveCredentials(): array
    {
        $pageId = Setting::where('key', 'social.facebook_page_id')->value('value');
        $tokenEnc = Setting::where('key', 'social.facebook_page_token')->value('value');
        $token = null;

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
        SocialPost::where('id', $this->postId)->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);
    }
}
