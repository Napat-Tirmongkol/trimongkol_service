<?php

/**
 * Subscription plan catalog. Edit prices/limits here — there's no DB
 * row to keep in sync. Limit checks live in App\Services\PlanGate.
 *
 * `unlimited`: use null. Code treats null as "no cap".
 */

return [
    'free' => [
        'name' => 'Free',
        'price' => 0,            // THB / month
        'tagline' => 'เริ่มต้นใช้งาน — สำหรับครูคนเดียว ห้องไม่กี่ห้อง',
        'tagline_en' => 'Get started — for solo teachers with a few classrooms.',
        'features' => [
            'ห้องเรียนสูงสุด 3 ห้อง',
            'ครูในทีม 1 คน',
            'นักเรียนสูงสุด 50 คน/ห้อง',
            'สแกน QR + ตรวจการบ้านพื้นฐาน',
        ],
        'features_en' => [
            'Up to 3 classrooms',
            '1 team member',
            'Up to 50 students per classroom',
            'QR scan + basic homework grading',
        ],
        'limits' => [
            'max_classrooms' => 3,
            'max_members' => 1,
            'max_students_per_classroom' => 50,
        ],
    ],

    'basic' => [
        'name' => 'Basic',
        'price' => 199,
        'tagline' => 'สำหรับโรงเรียนเล็ก — มีทีม + ห้องเยอะขึ้น',
        'tagline_en' => 'For small schools — team plus more classrooms.',
        'features' => [
            'ห้องเรียนสูงสุด 20 ห้อง',
            'ครูในทีม 5 คน',
            'นักเรียนสูงสุด 200 คน/ห้อง',
            'Gradebook + Export CSV',
        ],
        'features_en' => [
            'Up to 20 classrooms',
            '5 team members',
            'Up to 200 students per classroom',
            'Gradebook + CSV export',
        ],
        'limits' => [
            'max_classrooms' => 20,
            'max_members' => 5,
            'max_students_per_classroom' => 200,
        ],
    ],

    'pro' => [
        'name' => 'Pro',
        'price' => 499,
        'tagline' => 'สำหรับโรงเรียนใหญ่ — ไม่จำกัด',
        'tagline_en' => 'For large schools — unlimited.',
        'features' => [
            'ห้องเรียนไม่จำกัด',
            'ครูในทีมไม่จำกัด',
            'นักเรียนไม่จำกัด',
            'ทุกฟีเจอร์ของ Basic',
            'Priority support',
        ],
        'features_en' => [
            'Unlimited classrooms',
            'Unlimited team members',
            'Unlimited students',
            'Everything in Basic',
            'Priority support',
        ],
        'limits' => [
            'max_classrooms' => null,
            'max_members' => null,
            'max_students_per_classroom' => null,
        ],
    ],
];
