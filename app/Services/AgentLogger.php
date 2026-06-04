<?php

namespace App\Services;

use App\Models\SocialAgentLog;
use Illuminate\Support\Facades\Log;

class AgentLogger
{
    public static function info(int $postId, string $message, ?int $durationMs = null): void
    {
        static::write($postId, 'info', $message, $durationMs);
    }

    public static function success(int $postId, string $message, ?int $durationMs = null): void
    {
        static::write($postId, 'success', $message, $durationMs);
    }

    public static function warning(int $postId, string $message): void
    {
        static::write($postId, 'warning', $message);
    }

    public static function error(int $postId, string $message): void
    {
        static::write($postId, 'error', $message);
    }

    private static function write(int $postId, string $type, string $message, ?int $durationMs = null): void
    {
        try {
            SocialAgentLog::create([
                'social_post_id' => $postId,
                'type'           => $type,
                'message'        => $message,
                'duration_ms'    => $durationMs,
            ]);
        } catch (\Throwable $e) {
            Log::warning("AgentLogger: could not write log for post {$postId}: ".$e->getMessage());
        }
    }
}
