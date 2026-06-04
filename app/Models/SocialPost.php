<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPost extends Model
{
    protected $fillable = [
        'feed_source_id', 'title', 'source_url', 'source_published_at',
        'ai_content', 'status', 'facebook_post_id', 'published_at', 'error_message',
    ];

    protected $casts = [
        'source_published_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function feedSource(): BelongsTo
    {
        return $this->belongsTo(SocialFeedSource::class, 'feed_source_id');
    }

    public function isPending(): bool  { return $this->status === 'pending_ai'; }
    public function isDraft(): bool    { return $this->status === 'draft'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isPublished(): bool { return $this->status === 'published'; }
    public function isFailed(): bool   { return $this->status === 'failed'; }
}
