<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialFeedSource extends Model
{
    protected $fillable = ['name', 'url', 'is_active', 'last_fetched_at', 'fail_count'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_fetched_at' => 'datetime',
        'fail_count' => 'integer',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(SocialPost::class, 'feed_source_id');
    }
}
