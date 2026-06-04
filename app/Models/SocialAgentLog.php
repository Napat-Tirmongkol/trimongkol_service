<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAgentLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['social_post_id', 'type', 'message', 'duration_ms'];

    protected $casts = ['created_at' => 'datetime'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }
}
