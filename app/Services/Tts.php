<?php

namespace App\Services;

use App\Models\Queue;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Google Cloud Text-to-Speech for the queue announcements.
 *
 * Calls the REST endpoint with a plain API key (no composer dependency,
 * no service account) and caches the resulting MP3 on the local disk so
 * repeated phrases never re-hit Google. Everything degrades gracefully:
 * any failure returns null and the front-end falls back to the browser's
 * built-in speech synthesis.
 */
class Tts
{
    private const ENDPOINT = 'https://texttospeech.googleapis.com/v1/text:synthesize';
    private const DEFAULT_VOICE = 'th-TH-Neural2-C';

    public static function enabled(): bool
    {
        return setting('tts.provider') === 'google' && filled(self::key());
    }

    public static function key(): ?string
    {
        $stored = setting('tts.google_key');
        if (filled($stored)) {
            try {
                return Crypt::decryptString($stored);
            } catch (\Throwable $e) {
                // Not encrypted (e.g. set straight in the DB) — use as-is.
                return $stored;
            }
        }

        return config('services.google_tts.key');
    }

    public static function voice(): string
    {
        return setting('tts.voice') ?: (config('services.google_tts.voice') ?: self::DEFAULT_VOICE);
    }

    /**
     * Synthesize the spoken phrase for a queue event and return MP3 bytes,
     * or null when TTS is off / unavailable.
     */
    public static function audioFor(Queue $queue, ?string $type, int $number, string $counter): ?string
    {
        if (! self::enabled()) {
            return null;
        }

        $counter = mb_substr(trim($counter), 0, 40);
        $text = $type === 'turn'
            ? self::turnText($counter)
            : self::callText((string) $queue->prefix, $number, $counter);

        return self::mp3($text);
    }

    /** Synthesize arbitrary Thai text to MP3 bytes (disk-cached). */
    public static function mp3(string $text): ?string
    {
        $text = trim($text);
        if ($text === '' || ! self::enabled()) {
            return null;
        }

        $disk = Storage::disk('local');
        $path = 'tts/'.sha1(self::voice().'|'.$text).'.mp3';
        if ($disk->exists($path)) {
            return $disk->get($path);
        }

        $audio = self::request($text);
        if ($audio === null) {
            return null;
        }

        $disk->put($path, $audio);

        return $audio;
    }

    /** For the admin "test key" button — returns [ok, message]. */
    public static function test(): array
    {
        if (setting('tts.provider') !== 'google') {
            return [false, 'provider != google'];
        }
        if (! filled(self::key())) {
            return [false, 'missing key'];
        }

        try {
            $res = self::call('ทดสอบเสียงเรียกคิว หมายเลข หนึ่ง');
        } catch (\Throwable $e) {
            return [false, $e->getMessage()];
        }

        if ($res->successful() && $res->json('audioContent')) {
            return [true, 'ok'];
        }

        return [false, $res->json('error.message') ?: ('HTTP '.$res->status())];
    }

    public static function callText(string $prefix, int $number, string $counter): string
    {
        $code = ($prefix !== '' ? $prefix.' ' : '').$number;

        return __('app.queue.voice_call', [], 'th').' '.$code.' '.__('app.queue.voice_counter', [], 'th').' '.$counter;
    }

    public static function turnText(string $counter): string
    {
        $text = __('app.queue.voice_your_turn', [], 'th');

        return $counter !== '' ? $text.' '.$counter : $text;
    }

    private static function request(string $text): ?string
    {
        try {
            $res = self::call($text);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }

        if (! $res->successful()) {
            return null;
        }

        $b64 = $res->json('audioContent');
        if (! $b64) {
            return null;
        }

        $mp3 = base64_decode($b64, true);

        return ($mp3 === false || $mp3 === '') ? null : $mp3;
    }

    private static function call(string $text)
    {
        return Http::timeout(8)->post(self::ENDPOINT.'?key='.urlencode((string) self::key()), [
            'input' => ['text' => $text],
            'voice' => ['languageCode' => 'th-TH', 'name' => self::voice()],
            'audioConfig' => ['audioEncoding' => 'MP3', 'speakingRate' => 0.96],
        ]);
    }
}
