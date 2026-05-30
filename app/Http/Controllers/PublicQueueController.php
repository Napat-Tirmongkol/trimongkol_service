<?php

namespace App\Http\Controllers;

use App\Models\Queue;

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
}
