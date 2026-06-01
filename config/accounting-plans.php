<?php

/**
 * Accounting product plan catalog — billed *separately* from the platform
 * (config/plans.php) and the queue product (config/queue-plans.php). A
 * workspace's tier is stored in workspaces.accounting_plan and enforced by
 * App\Services\AccountingPlan.
 *
 * limits: null = unlimited.  flags: gate non-limit features.
 */

return [
    'free' => [
        'name' => 'Free',
        'price' => 0,            // THB / month
        'tagline' => 'เริ่มต้นทำบัญชี — กิจการเล็กที่เพิ่งเริ่ม',
        'tagline_en' => 'Start keeping books — for small businesses just beginning.',
        'features' => [
            'ออกใบกำกับ/ใบเสร็จสูงสุด 20 ใบ/เดือน',
            'ลูกหนี้-เจ้าหนี้ + ผังบัญชี',
            'รายงานภาษีซื้อ-ขาย + หัก ณ ที่จ่าย',
        ],
        'features_en' => [
            'Up to 20 invoices/receipts per month',
            'AR/AP + chart of accounts',
            'VAT & withholding-tax reports',
        ],
        'limits' => [
            'max_invoices_per_month' => 20,
        ],
        'flags' => [
            'csv_export' => false,
        ],
    ],

    'starter' => [
        'name' => 'Starter',
        'price' => 249,
        'tagline' => 'สำหรับธุรกิจที่กำลังโต — เอกสารเยอะขึ้น',
        'tagline_en' => 'For growing businesses — more documents.',
        'features' => [
            'ออกเอกสารสูงสุด 200 ใบ/เดือน',
            'ทุกฟีเจอร์ของ Free',
            'ส่งออกสมุดรายวัน (CSV) ให้สำนักงานบัญชี',
            'ยอดยกมา + งบการเงิน',
        ],
        'features_en' => [
            'Up to 200 documents per month',
            'Everything in Free',
            'Journal CSV export for your accountant',
            'Opening balances + financial statements',
        ],
        'limits' => [
            'max_invoices_per_month' => 200,
        ],
        'flags' => [
            'csv_export' => true,
        ],
    ],

    'pro' => [
        'name' => 'Pro',
        'price' => 599,
        'tagline' => 'สำหรับกิจการที่เอกสารเยอะ — ไม่จำกัด',
        'tagline_en' => 'For high-volume operations — unlimited.',
        'features' => [
            'ออกเอกสารไม่จำกัด',
            'ทุกฟีเจอร์ของ Starter',
            'Priority support',
        ],
        'features_en' => [
            'Unlimited documents',
            'Everything in Starter',
            'Priority support',
        ],
        'limits' => [
            'max_invoices_per_month' => null,
        ],
        'flags' => [
            'csv_export' => true,
        ],
    ],
];
