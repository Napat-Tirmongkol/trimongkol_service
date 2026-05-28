<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Student extends Model
{
    protected $fillable = ['classroom_id', 'code', 'name', 'number', 'photo_path'];

    protected static function booted(): void
    {
        static::creating(function (Student $student) {
            if (empty($student->code)) {
                $student->code = self::generateUniqueCode($student->classroom_id);
            }
        });
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public static function generateUniqueCode(int $classroomId): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('classroom_id', $classroomId)->where('code', $code)->exists());

        return $code;
    }
}
