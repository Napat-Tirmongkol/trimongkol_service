<?php

/**
 * Queue product plan catalog — billed *separately* from the platform/Scanner
 * subscription (config/plans.php). A workspace's tier is stored in
 * workspaces.queue_plan and enforced by App\Services\QueuePlan, independent of
 * the Scanner free-launch mode.
 *
 * limits: null = unlimited.  flags: gate non-limit features (Phase 1 enforces
 * `branding` via the watermark; the rest are defined for display + future use).
 */

return [
    'free' => [
        'name' => 'Free',
        'price' => 0,
        'tagline' => 'เริ่มต้นใช้งาน — ร้านเล็ก คิวไม่เยอะ',
        'tagline_en' => 'Get started — small shops, light volume.',
        'features' => [
            '1 จุดบริการ',
            'สูงสุด 2 ช่องบริการ',
            'สูงสุด 80 คิว/วัน',
            'เสียงเรียกคิวมาตรฐาน',
            'มีลายน้ำของระบบบนหน้าลูกค้า',
        ],
        'features_en' => [
            '1 service point',
            'Up to 2 counters',
            'Up to 80 tickets/day',
            'Standard voice announcement',
            'System watermark on the customer page',
        ],
        'limits' => [
            'max_queues' => 1,
            'max_counters' => 2,
            'max_tickets_per_day' => 80,
        ],
        'flags' => [
            'branding' => false,
            'analytics' => false,
            'export' => false,
            'notifications' => false,
            'categories' => false,
        ],
    ],

    'starter' => [
        'name' => 'Starter',
        'price' => 249,
        'tagline' => 'สำหรับร้านที่เริ่มโต — ดูเป็นมืออาชีพขึ้น',
        'tagline_en' => 'For growing shops — look more professional.',
        'features' => [
            'สูงสุด 3 จุดบริการ',
            'สูงสุด 5 ช่องบริการ',
            'คิวไม่จำกัด/วัน',
            'เอาลายน้ำออก + ใส่โลโก้ร้าน',
            'รายงานสถิติพื้นฐาน (รายวัน/สัปดาห์)',
        ],
        'features_en' => [
            'Up to 3 service points',
            'Up to 5 counters',
            'Unlimited tickets/day',
            'Remove watermark + add your logo',
            'Basic stats (daily / weekly)',
        ],
        'limits' => [
            'max_queues' => 3,
            'max_counters' => 5,
            'max_tickets_per_day' => null,
        ],
        'flags' => [
            'branding' => true,
            'analytics' => true,
            'export' => false,
            'notifications' => false,
            'categories' => false,
        ],
    ],

    'pro' => [
        'name' => 'Pro',
        'price' => 699,
        'tagline' => 'สำหรับธุรกิจคิวหนาแน่น เน้นประสบการณ์ลูกค้า',
        'tagline_en' => 'For busy businesses that care about customer experience.',
        'features' => [
            'จุดบริการ/ช่องบริการ ไม่จำกัด',
            'แจ้งเตือนใกล้ถึงคิวผ่าน LINE/SMS',
            'แยกประเภทคิว (ทั่วไป / VIP / รับของ)',
            'Dashboard สถิติเชิงลึก (เวลารอ/เวลาบริการ)',
            'Export ข้อมูล',
        ],
        'features_en' => [
            'Unlimited service points / counters',
            'LINE / SMS near-turn alerts',
            'Queue categories (general / VIP / pickup)',
            'In-depth analytics (wait / service time)',
            'Data export',
        ],
        'limits' => [
            'max_queues' => null,
            'max_counters' => null,
            'max_tickets_per_day' => null,
        ],
        'flags' => [
            'branding' => true,
            'analytics' => true,
            'export' => true,
            'notifications' => true,
            'categories' => true,
        ],
    ],

    'enterprise' => [
        'name' => 'Enterprise',
        'price' => null, // ติดต่อตกลงราคา
        'tagline' => 'ปรับตามองค์กร — เชื่อมระบบเดิม',
        'tagline_en' => 'Tailored for organizations — integrate your stack.',
        'features' => [
            'ทุกอย่างใน Pro',
            'เชื่อมต่อ API / POS',
            'ปรับแต่ง UI/UX เฉพาะ',
            'ดูแลระดับองค์กร',
        ],
        'features_en' => [
            'Everything in Pro',
            'API / POS integration',
            'Custom UI/UX',
            'Enterprise support',
        ],
        'limits' => [
            'max_queues' => null,
            'max_counters' => null,
            'max_tickets_per_day' => null,
        ],
        'flags' => [
            'branding' => true,
            'analytics' => true,
            'export' => true,
            'notifications' => true,
            'categories' => true,
        ],
    ],
];
