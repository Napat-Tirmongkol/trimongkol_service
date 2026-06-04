@extends('layouts.marketing')

@section('title', 'นโยบายความเป็นส่วนตัว — ' . t('brand.name'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">

    <h1 class="mb-2 text-3xl font-bold text-slate-900">นโยบายความเป็นส่วนตัว</h1>
    <p class="mb-10 text-sm text-slate-500">อัปเดตล่าสุด: {{ now()->format('d F Y') }}</p>

    <div class="prose prose-slate max-w-none space-y-8">

        <section>
            <h2 class="text-xl font-semibold text-slate-800">1. บทนำ</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                {{ t('brand.name') }} ("เรา", "ของเรา") ให้ความสำคัญกับความเป็นส่วนตัวของคุณ
                นโยบายนี้อธิบายวิธีที่เราเก็บรวบรวม ใช้ และปกป้องข้อมูลของคุณเมื่อใช้บริการของเรา
                รวมถึงการเชื่อมต่อกับ Facebook / Meta Platform
            </p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">2. ข้อมูลที่เก็บรวบรวม</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">เราเก็บรวบรวมข้อมูลดังต่อไปนี้:</p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-slate-600">
                <li><strong>ข้อมูลบัญชี:</strong> ชื่อ อีเมล รหัสผ่าน (เข้ารหัส)</li>
                <li><strong>ข้อมูลการใช้งาน:</strong> IP address, เวลาเข้าระบบ, การกระทำในระบบ</li>
                <li><strong>ข้อมูล Facebook Page:</strong> access token สำหรับโพสต์เนื้อหา (เข้ารหัสก่อนจัดเก็บ)</li>
                <li><strong>เนื้อหาที่สร้าง:</strong> โพสต์ที่สร้างด้วย AI เพื่อเผยแพร่บน Facebook Page</li>
            </ul>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">3. วิธีใช้ข้อมูล</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">เราใช้ข้อมูลของคุณเพื่อ:</p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-slate-600">
                <li>ให้บริการและปรับปรุงระบบ</li>
                <li>โพสต์เนื้อหาไปยัง Facebook Page ตามที่คุณอนุมัติ</li>
                <li>รักษาความปลอดภัยของบัญชี</li>
                <li>ส่งการแจ้งเตือนที่เกี่ยวข้องกับบริการ</li>
            </ul>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">4. การใช้งาน Facebook / Meta Platform</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                บริการของเราเชื่อมต่อกับ Meta Platform API เพื่อโพสต์เนื้อหาบน Facebook Page
                เราขอสิทธิ์เข้าถึงดังต่อไปนี้:
            </p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-slate-600">
                <li><strong>pages_manage_posts</strong> — สร้างและจัดการโพสต์บน Page</li>
                <li><strong>pages_read_engagement</strong> — อ่านสถิติการมีส่วนร่วม</li>
                <li><strong>pages_show_list</strong> — แสดงรายการ Page ที่ดูแล</li>
            </ul>
            <p class="mt-3 text-slate-600 leading-relaxed">
                เราไม่จัดเก็บหรือแบ่งปันข้อมูลส่วนตัวของผู้ใช้ Facebook รายอื่น
                และปฏิบัติตาม
                <a href="https://developers.facebook.com/policy/" target="_blank" rel="noopener"
                   class="text-brand-600 hover:underline">นโยบายของ Meta สำหรับนักพัฒนา</a>
            </p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">5. การแบ่งปันข้อมูล</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                เราไม่ขาย ให้เช่า หรือแบ่งปันข้อมูลส่วนตัวของคุณกับบุคคลที่สาม
                ยกเว้นในกรณีต่อไปนี้:
            </p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-slate-600">
                <li>เมื่อได้รับความยินยอมจากคุณ</li>
                <li>เพื่อปฏิบัติตามกฎหมายหรือคำสั่งศาล</li>
                <li>ผู้ให้บริการภายนอกที่ช่วยให้บริการ (เช่น AI API) โดยอยู่ภายใต้ข้อตกลงการรักษาความลับ</li>
            </ul>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">6. ความปลอดภัยของข้อมูล</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                เราใช้มาตรการรักษาความปลอดภัยที่เหมาะสม ได้แก่ การเข้ารหัส token และรหัสผ่าน
                การจำกัดการเข้าถึง และการบันทึก audit log สำหรับการกระทำที่สำคัญ
            </p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">7. สิทธิ์ของคุณ</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">คุณมีสิทธิ์:</p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-slate-600">
                <li>เข้าถึง แก้ไข หรือลบข้อมูลส่วนตัวของคุณ</li>
                <li>เพิกถอนการเข้าถึง Facebook Page ได้ตลอดเวลาผ่านการตั้งค่าของ Facebook</li>
                <li>ยกเลิกบัญชีและลบข้อมูลทั้งหมด</li>
            </ul>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">8. ติดต่อเรา</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                หากมีคำถามเกี่ยวกับนโยบายนี้ สามารถติดต่อเราได้ที่
                <a href="{{ route('contact') }}" class="text-brand-600 hover:underline">หน้าติดต่อ</a>
                หรือทางอีเมลที่ระบุในเว็บไซต์
            </p>
        </section>

    </div>
</div>
@endsection
