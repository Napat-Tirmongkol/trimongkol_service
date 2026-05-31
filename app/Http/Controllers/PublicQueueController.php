<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Services\Tts;
use Illuminate\Http\Request;

class PublicQueueController extends Controller
{
    /**
     * Public, login-free customer page reached by scanning the queue's QR
     * code or opening its share link. Resolved by the unguessable token
     * rather than the numeric id, so the internal id is never exposed.
     */
    public function show(string $token)
    {
        $queue = Queue::where('public_token', $token)->firstOrFail();

        return view('queues.public', compact('queue'));
    }

    /** Server-proxied Google TTS audio for the customer's "your turn" alert. */
    public function tts(Request $request, string $token)
    {
        $queue = Queue::where('public_token', $token)->firstOrFail();

        $mp3 = Tts::audioFor($queue, $request->query('type'), (int) $request->query('number', 0), (string) $request->query('counter', ''));
        abort_if($mp3 === null, 404);

        return response($mp3, 200, [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
