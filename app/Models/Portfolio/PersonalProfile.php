<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalProfile extends Model
{
    protected $table = 'portfolio_personal_profiles';

    protected $fillable = [
        'user_id',
        'mbti',
        'headline',
        'traits',                // ลักษณะนิสัยใจคอ
        'strengths',             // จุดแข็ง / ข้อดี
        'weaknesses',            // จุดอ่อน / สิ่งที่ต้องระวัง
        'energy_boosters',       // สิ่งที่ทำให้มีพลัง / ฮีลใจ
        'energy_drainers',       // สิ่งที่ทำให้หมดพลัง / หงุดหงิด
        'coping_guide',          // วิธีรับมือเวลาเครียด / ดิ่ง / โกรธ
        'communication_style',   // สไตล์การสื่อสารที่ชอบ
        'life_principles',       // กฎเหล็ก & ปรัชญาชีวิต
        'favorite_things',       // สิ่งที่ชอบเป็นพิเศษ (มอเตอร์ไซค์, เพลง, อาหาร)
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }
}
