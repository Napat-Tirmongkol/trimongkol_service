<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    protected $fillable = [
        'classroom_id', 'name', 'category', 'due_date', 'scoring_mode', 'default_score',
        'max_score', 'weight', 'description',
    ];

    public const CATEGORIES = ['homework', 'classwork', 'quiz', 'exam', 'project', 'other'];

    protected $casts = [
        'due_date' => 'date',
        'max_score' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Effective max score: explicit column when set, otherwise a sensible
     * fallback per mode so check/fixed assignments still aggregate.
     */
    public function effectiveMaxScore(): float
    {
        if ($this->max_score !== null) {
            return (float) $this->max_score;
        }
        return match ($this->scoring_mode) {
            'check', 'attendance' => 1.0,
            'fixed' => (float) ($this->default_score ?? 1),
            'custom' => 100.0,
            default => 100.0,
        };
    }

    /**
     * Score earned for a submission row (null if no submission).
     * Check / attendance: a present submission counts as 1 even though score is null.
     */
    public function earnedScore(?Submission $submission): ?float
    {
        if (! $submission) {
            return null;
        }
        if ($this->scoring_mode === 'check' || $this->scoring_mode === 'attendance') {
            return 1.0;
        }
        return $submission->score !== null ? (float) $submission->score : null;
    }
}
