<?php

/**
 * Subscription plan catalog for Ledgerly. Edit prices/limits here — there's no
 * DB row to keep in sync. Limit checks live in App\Services\PlanGate.
 *
 * `unlimited`: use null. Code treats null as "no cap".
 * Limit dimensions in use: max_members (team seats per workspace),
 * max_invoices_per_month (issued sales documents), max_workspaces is enforced
 * elsewhere if needed.
 */

return [
    'free' => [
        'name' => 'Free',
        'price' => 0,            // THB / month
        'tagline' => 'เริ่มต้นใช้งาน — สำหรับกิจการเล็กที่เพิ่งเริ่ม',
        'tagline_en' => 'Get started — for small businesses just beginning.',
        'features' => [
            'ออกใบกำกับ/ใบเสร็จสูงสุด 20 ใบ/เดือน',
            'ผู้ใช้ในทีม 1 คน',
            'ผังบัญชี + ลูกหนี้-เจ้าหนี้',
            'รายงานภาษีซื้อ-ขาย + หัก ณ ที่จ่าย',
        ],
        'features_en' => [
            'Up to 20 invoices/receipts per month',
            '1 team member',
            'Chart of accounts + AR/AP',
            'VAT & withholding tax reports',
        ],
        'limits' => [
            'max_members' => 1,
            'max_invoices_per_month' => 20,
        ],
    ],

    'pro' => [
        'name' => 'Pro',
        'price' => 299,
        'tagline' => 'สำหรับธุรกิจที่กำลังโต — ทีมเล็ก เอกสารเยอะขึ้น',
        'tagline_en' => 'For growing businesses — a small team, more documents.',
        'features' => [
            'ออกเอกสารไม่จำกัด',
            'ผู้ใช้ในทีมสูงสุด 5 คน',
            'ทุกฟีเจอร์ของ Free',
            'ส่งออกสมุดรายวัน (CSV) ให้สำนักงานบัญชี',
            'ยอดยกมา + งบการเงิน',
        ],
        'features_en' => [
            'Unlimited documents',
            'Up to 5 team members',
            'Everything in Free',
            'Journal CSV export for your accountant',
            'Opening balances + financial statements',
        ],
        'limits' => [
            'max_members' => 5,
            'max_invoices_per_month' => null,
        ],
    ],

    'business' => [
        'name' => 'Business',
        'price' => 699,
        'tagline' => 'สำหรับกิจการที่มีหลายทีม — ไม่จำกัด',
        'tagline_en' => 'For multi-team operations — unlimited.',
        'features' => [
            'ทุกอย่างไม่จำกัด',
            'ผู้ใช้ในทีมไม่จำกัด',
            'ทุกฟีเจอร์ของ Pro',
            'Priority support',
        ],
        'features_en' => [
            'Everything unlimited',
            'Unlimited team members',
            'Everything in Pro',
            'Priority support',
        ],
        'limits' => [
            'max_members' => null,
            'max_invoices_per_month' => null,
        ],
    ],
];
