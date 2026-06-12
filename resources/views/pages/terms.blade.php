@extends('layouts.marketing')

@section('title', 'ข้อกำหนดการให้บริการ — ' . t('brand.name'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">

    <h1 class="mb-2 text-3xl font-bold text-slate-900">ข้อกำหนดการให้บริการ</h1>
    <p class="mb-10 text-sm text-slate-500">อัปเดตล่าสุด: {{ now()->format('d F Y') }}</p>

    <div class="prose prose-slate max-w-none space-y-8">

        <section>
            <h2 class="text-xl font-semibold text-slate-800">1. การยอมรับข้อกำหนด</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                การเข้าใช้บริการของ {{ t('brand.name') }} ("เรา", "บริการ") ถือว่าคุณได้อ่าน
                ทำความเข้าใจ และยอมรับข้อกำหนดทั้งหมดนี้แล้ว หากไม่ยอมรับ
                กรุณาหยุดใช้บริการ
            </p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">2. คำอธิบายบริการ</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                เราให้บริการระบบซอฟต์แวร์สำหรับธุรกิจ SME ไทย ครอบคลุมระบบจองคิว
                ระบบบัญชี ระบบสแกนการบ้าน เว็บไซต์ธุรกิจ พอร์ตทรัพย์สินส่วนตัว
                และระบบอื่น ๆ ที่พัฒนาขึ้นตามความต้องการ
            </p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">3. บัญชีผู้ใช้</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                ในการใช้งานบางฟีเจอร์ คุณต้องลงทะเบียนบัญชีและให้ข้อมูลที่ถูกต้องครบถ้วน
                คุณมีหน้าที่:
            </p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-slate-600">
                <li>รักษาความลับของรหัสผ่านและข้อมูล Google account ที่ใช้ Sign-In</li>
                <li>รับผิดชอบกิจกรรมทั้งหมดที่เกิดขึ้นในบัญชีของคุณ</li>
                <li>แจ้งเราทันทีหากพบการเข้าใช้บัญชีโดยไม่ได้รับอนุญาต</li>
            </ul>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">4. การใช้งานที่ยอมรับได้</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">คุณตกลงว่าจะไม่:</p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-slate-600">
                <li>ใช้บริการเพื่อกิจกรรมผิดกฎหมายหรือฉ้อโกง</li>
                <li>พยายามเจาะระบบ ทดสอบช่องโหว่ หรือรบกวนการทำงานของบริการ</li>
                <li>ทำซ้ำ ดัดแปลง หรือกระจายซอร์สโค้ดของระบบโดยไม่ได้รับอนุญาต</li>
                <li>ส่งสแปม มัลแวร์ หรือเนื้อหาที่ผิดกฎหมายผ่านบริการของเรา</li>
                <li>ใช้บริการในลักษณะที่อาจทำให้ระบบหรือผู้ใช้รายอื่นเสียหาย</li>
            </ul>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">5. เนื้อหาของผู้ใช้</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                ข้อมูลและเนื้อหาที่คุณป้อนเข้าระบบ (เช่น รายการทรัพย์สิน รายชื่อนักเรียน
                ใบเสนอราคา) ยังคงเป็นกรรมสิทธิ์ของคุณ คุณรับผิดชอบความถูกต้องและถูกกฎหมาย
                ของเนื้อหาที่ป้อน เราเก็บรักษาเนื้อหาตามนโยบายความเป็นส่วนตัวของเรา
            </p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">6. ค่าบริการและการชำระเงิน</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                บางฟีเจอร์มีค่าบริการรายเดือนหรือรายปีตามแพ็กเกจที่เลือก
                ราคาที่แสดงรวม VAT แล้ว ค่าบริการที่ชำระแล้วไม่สามารถขอคืนได้
                ยกเว้นกรณีที่เราระบุไว้เป็นลายลักษณ์อักษร เรามีสิทธิ์ปรับราคาแพ็กเกจในอนาคต
                โดยแจ้งให้ทราบล่วงหน้าอย่างน้อย 30 วัน
            </p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">7. การระงับและยกเลิกบัญชี</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                เราขอสงวนสิทธิ์ในการระงับหรือยกเลิกบัญชีของคุณหากพบว่า:
            </p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-slate-600">
                <li>ละเมิดข้อกำหนดเหล่านี้</li>
                <li>มีการใช้งานที่ผิดปกติหรือเป็นภัยต่อระบบ</li>
                <li>ไม่ชำระค่าบริการตามกำหนด</li>
            </ul>
            <p class="mt-3 text-slate-600 leading-relaxed">
                คุณสามารถยกเลิกบัญชีของตนเองได้ตลอดเวลาผ่านหน้าตั้งค่าหรือติดต่อทีมงาน
            </p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">8. ข้อจำกัดความรับผิด</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                บริการให้ "ตามสภาพ" โดยไม่มีการรับประกันใด ๆ เราจะพยายามให้บริการอย่างต่อเนื่อง
                แต่ไม่รับประกันว่าระบบจะใช้งานได้ตลอดเวลาหรือปราศจากข้อผิดพลาด
            </p>
            <p class="mt-3 text-slate-600 leading-relaxed">
                ข้อมูลในฟีเจอร์พอร์ตทรัพย์สิน (เช่น ราคาหุ้น/คริปโต/อัตราแลกเปลี่ยน) ดึงจากแหล่งข้อมูลภายนอก
                เพื่อความสะดวกในการดูภาพรวม <strong>ไม่ใช่คำแนะนำการลงทุน</strong>
                การตัดสินใจลงทุนเป็นความรับผิดชอบของคุณเอง
            </p>
            <p class="mt-3 text-slate-600 leading-relaxed">
                เราไม่รับผิดชอบต่อความเสียหายทางอ้อม การสูญเสียกำไร หรือผลกระทบทางการเงิน
                ที่เกิดจากการใช้หรือไม่สามารถใช้บริการของเราได้
            </p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">9. ทรัพย์สินทางปัญญา</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                ซอร์สโค้ด การออกแบบ โลโก้ และเครื่องหมายการค้าของ {{ t('brand.name') }}
                เป็นทรัพย์สินของเราหรือผู้ให้สิทธิ์ของเรา การใช้งานบริการไม่ก่อให้เกิดการโอน
                สิทธิ์ในทรัพย์สินทางปัญญาแก่คุณ
            </p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">10. การเปลี่ยนแปลงข้อกำหนด</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                เรามีสิทธิ์ปรับปรุงข้อกำหนดเหล่านี้เป็นครั้งคราว เราจะแจ้งให้ทราบล่วงหน้า
                ผ่านอีเมลหรือประกาศในระบบเมื่อมีการเปลี่ยนแปลงสำคัญ การใช้งานต่อหลังจากวันที่
                ข้อกำหนดใหม่มีผลถือว่าคุณยอมรับข้อกำหนดที่ปรับปรุงแล้ว
            </p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">11. กฎหมายที่ใช้บังคับ</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                ข้อกำหนดนี้อยู่ภายใต้กฎหมายแห่งราชอาณาจักรไทย ข้อพิพาทใด ๆ ให้ยื่นต่อศาลที่มีเขตอำนาจในประเทศไทย
            </p>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-slate-800">12. ติดต่อเรา</h2>
            <p class="mt-2 text-slate-600 leading-relaxed">
                หากมีคำถามเกี่ยวกับข้อกำหนดนี้ สามารถติดต่อเราได้ที่
                <a href="{{ route('contact') }}" class="text-brand-600 hover:underline">หน้าติดต่อ</a>
            </p>
        </section>

    </div>
</div>
@endsection
