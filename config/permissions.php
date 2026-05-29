<?php

/**
 * Back-office permission catalog (fixed — each key maps to a gate the code
 * checks). Roles live in the DB and bundle these keys; admins can't invent
 * permissions the code doesn't enforce, so the catalog stays here.
 *
 * Each group / permission carries TH + EN labels for the role editor UI
 * (same pattern as config/plans.php).
 */

return [
    'groups' => [
        'users' => [
            'label' => 'ผู้ใช้', 'label_en' => 'Users',
            'permissions' => [
                'users.view'         => ['label' => 'ดูรายชื่อ/รายละเอียดผู้ใช้', 'label_en' => 'View users'],
                'users.manage'       => ['label' => 'จัดการบัญชี (ระงับ / รีเซ็ตรหัส / สวมสิทธิ์)', 'label_en' => 'Manage accounts (suspend / reset / impersonate)'],
                'users.delete'       => ['label' => 'ลบผู้ใช้', 'label_en' => 'Delete users'],
                'users.assign_roles' => ['label' => 'กำหนด role ให้ผู้ใช้', 'label_en' => 'Assign roles to users'],
            ],
        ],
        'roles' => [
            'label' => 'Roles & สิทธิ์', 'label_en' => 'Roles & permissions',
            'permissions' => [
                'roles.manage' => ['label' => 'สร้าง / แก้ / ลบ role และกำหนดสิทธิ์', 'label_en' => 'Create / edit / delete roles'],
            ],
        ],
        'leads' => [
            'label' => 'ข้อความติดต่อ', 'label_en' => 'Leads',
            'permissions' => [
                'leads.view'   => ['label' => 'ดูข้อความติดต่อ', 'label_en' => 'View leads'],
                'leads.manage' => ['label' => 'จัดการ (มอบหมาย / สถานะ / ลบ)', 'label_en' => 'Manage leads'],
            ],
        ],
        'workspaces' => [
            'label' => 'Workspaces', 'label_en' => 'Workspaces',
            'permissions' => [
                'workspaces.view'   => ['label' => 'ดู workspace', 'label_en' => 'View workspaces'],
                'workspaces.manage' => ['label' => 'เปลี่ยนแพ็คเกจ / ลบ workspace', 'label_en' => 'Manage workspaces (plan / delete)'],
            ],
        ],
        'content' => [
            'label' => 'เนื้อหา & สินค้า', 'label_en' => 'Content & products',
            'permissions' => [
                'cms.manage'        => ['label' => 'แก้ไขเนื้อหาเว็บ (/admin/site)', 'label_en' => 'Edit site content'],
                'products.moderate' => ['label' => 'ดูแลสินค้า (Homework Scanner ฯลฯ)', 'label_en' => 'Moderate products'],
            ],
        ],
        'platform' => [
            'label' => 'ระบบ & ความปลอดภัย', 'label_en' => 'Platform & security',
            'permissions' => [
                'security.view' => ['label' => 'ดูหน้าความปลอดภัย', 'label_en' => 'View security'],
                'audit.view'    => ['label' => 'ดู Audit Log', 'label_en' => 'View audit log'],
                'system.manage' => ['label' => 'ระบบ: Pull / Migrate / Clear cache / อีเมล', 'label_en' => 'System: deploy / migrate / cache'],
            ],
        ],
    ],
];
