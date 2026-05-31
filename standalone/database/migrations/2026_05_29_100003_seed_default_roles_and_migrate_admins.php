<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed the three default roles and migrate every existing admin to
     * Super Admin so nobody loses access. is_admin stays the "has any
     * back-office role" flag and keeps working unchanged.
     */
    public function up(): void
    {
        $now = now();

        $admin = [
            'users.view', 'users.manage', 'users.delete',
            'leads.view', 'leads.manage',
            'workspaces.view', 'workspaces.manage',
            'cms.manage', 'products.moderate',
            'security.view', 'audit.view',
        ];
        $support = [
            'users.view', 'leads.view', 'leads.manage',
            'workspaces.view', 'products.moderate', 'audit.view',
        ];

        $roles = [
            ['key' => 'super-admin', 'name' => 'Super Admin', 'description' => 'เข้าถึงทุกอย่าง รวมถึงจัดการ role และระบบ', 'permissions' => json_encode(['*']), 'is_system' => true],
            ['key' => 'admin', 'name' => 'Admin', 'description' => 'จัดการผู้ใช้ / leads / workspaces / เนื้อหา (ยกเว้น role และ system)', 'permissions' => json_encode($admin), 'is_system' => true],
            ['key' => 'support', 'name' => 'Support', 'description' => 'ดูข้อมูลและตอบ leads (เน้นอ่าน)', 'permissions' => json_encode($support), 'is_system' => true],
        ];

        foreach ($roles as $r) {
            if (! DB::table('roles')->where('key', $r['key'])->exists()) {
                DB::table('roles')->insert($r + ['created_at' => $now, 'updated_at' => $now]);
            }
        }

        $superId = DB::table('roles')->where('key', 'super-admin')->value('id');
        if ($superId) {
            DB::table('users')->where('is_admin', true)->update(['role_id' => $superId]);
        }
    }

    public function down(): void
    {
        DB::table('users')->whereNotNull('role_id')->update(['role_id' => null]);
        DB::table('roles')->whereIn('key', ['super-admin', 'admin', 'support'])->delete();
    }
};
