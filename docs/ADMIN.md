# Admin Architecture

โครงสร้าง admin panel ของ Tirmongkol Service — ออกแบบให้รองรับ **multi-product** เพื่อให้
เพิ่มระบบใหม่ในอนาคตได้โดยไม่ต้องรื้อ nav

---

## โครงสร้าง URL

```
/admin                              ← Platform dashboard (cross-cutting)
/admin/users                        ← User management
/admin/users/{id}                   ← User detail
/admin/logs                         ← Audit log
/admin/site                         ← CMS / Site settings

/admin/products/scanner             ← Product hub (Homework Scanner)
/admin/products/scanner/classrooms  ← Product-specific moderation
```

แนวคิด: **Platform** = ทุกอย่างที่ cross product (users, audit, settings, etc.)
**Products** = features เฉพาะของแต่ละสินค้า

---

## โครงสร้างโฟลเดอร์

```
app/Http/Controllers/
├── AdminController.php                          ← Platform-wide actions
└── Admin/
    └── Products/
        └── ScannerController.php                ← Homework Scanner moderation

resources/views/admin/
├── dashboard.blade.php                          ← Platform overview
├── users.blade.php, user_show.blade.php
├── logs.blade.php
├── site-settings.blade.php
└── products/
    └── scanner/
        ├── dashboard.blade.php                  ← Product hub
        ├── classrooms.blade.php
        └── classroom_show.blade.php

config/admin-products.php                        ← Product registry (drives the nav)
```

---

## เพิ่ม Product ใหม่ — 4 ขั้น

ตัวอย่าง: เพิ่ม "Course" product

### 1. Register ใน `config/admin-products.php`

```php
return [
    'scanner' => [ /* existing */ ],

    'course' => [
        'label_key' => 'app.admin.products.course.label',
        'desc_key' => 'app.admin.products.course.desc',
        'route' => 'admin.course.dashboard',
        'pattern' => 'admin.course.*',
        'tabs' => [
            ['route' => 'admin.course.dashboard',
             'label_key' => 'app.admin.products.course.tab_overview',
             'pattern' => 'admin.course.dashboard'],
            ['route' => 'admin.course.lessons',
             'label_key' => 'app.admin.products.course.tab_lessons',
             'pattern' => 'admin.course.lessons*'],
        ],
    ],
];
```

### 2. สร้าง Controller

`app/Http/Controllers/Admin/Products/CourseController.php`:

```php
namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;

class CourseController extends Controller
{
    public function dashboard() { return view('admin.products.course.dashboard'); }
    public function lessons() { /* ... */ }
}
```

### 3. เพิ่ม Routes

ใน `routes/web.php` ภายใต้ admin group:

```php
use App\Http\Controllers\Admin\Products\CourseController as AdminCourseController;

Route::prefix('products/course')->name('course.')->group(function () {
    Route::get('/', [AdminCourseController::class, 'dashboard'])->name('dashboard');
    Route::get('/lessons', [AdminCourseController::class, 'lessons'])->name('lessons');
});
```

### 4. สร้าง Views + Translations

```
resources/views/admin/products/course/
├── dashboard.blade.php
└── lessons.blade.php
```

เพิ่ม translation keys:

```php
// lang/th/app.php
'admin' => [
    'products' => [
        'course' => [
            'label' => 'Course Manager',
            'desc' => 'ระบบจัดการคอร์สเรียนออนไลน์',
            'tab_overview' => 'ภาพรวม',
            'tab_lessons' => 'บทเรียน',
        ],
    ],
],
```

**เสร็จ** — Nav จะแสดง "Course Manager" ใน Products dropdown อัตโนมัติ
พร้อม second-row tabs

---

## Audit Log — บันทึก privileged action

ทุก action ที่ส่งผลต่อ user อื่นหรือข้อมูล shared ควรเรียก `AuditLog::record()`:

```php
use App\Services\AuditLog;

// หลัง suspend/delete/promote etc.
AuditLog::record(
    action: 'course.publish',       // เลือกชื่อให้สื่อ
    target: $course,                // model ที่ถูกกระทำ (optional)
    targetLabel: $course->title,    // string ที่แสดงได้แม้ target ถูกลบ
    metadata: ['module' => 'video'],// (optional) JSON metadata
);
```

ดูประวัติได้ที่ `/admin/logs`

---

## Suspend User — Middleware

`App\Http\Middleware\BlockSuspendedUser` ตรวจ `$user->is_active` ทุก request
ถ้า `false` จะ kick user ออกระบบทันที (logout + redirect ไป login พร้อมข้อความเตือน)

ลงทะเบียนใน `bootstrap/app.php` กลุ่ม `web` ครอบทุก authenticated route

---

## Maintenance Mode

เปิดผ่าน `/admin/site` → ใส่ `1` ใน `maintenance.enabled`

Middleware `MaintenanceMode`:
- Admin login เข้าใช้งานได้ปกติ
- `/admin/*`, `/up`, `/locale/*` ผ่านตลอด
- User อื่นเห็นหน้า 503 (`resources/views/errors/maintenance.blade.php`) พร้อมข้อความที่ตั้งใน
  `maintenance.message` (i18n)

---

## Impersonation

Admin login เป็น user อื่นได้จากหน้า `/admin/users/{id}` (เฉพาะ user ที่ active)

- เก็บ `impersonator_id` ใน session
- Component `<x-impersonation-banner />` แสดงแถบเหลืองเตือนทุกหน้า authenticated app
- POST `/impersonate/stop` คืน session กลับเป็น admin เดิม

ทั้ง start และ stop log ลง `admin_actions` พร้อม IP

---

## SweetAlert2 Integration

ทุก confirm dialog ในระบบใช้ SweetAlert2 modal ผ่าน data-attribute pattern:

```html
<form data-confirm="ลบสิ่งนี้?" data-confirm-danger="1" method="POST" ...>
    @csrf @method('DELETE')
    <button type="submit">ลบ</button>
</form>
```

- `data-confirm` — ข้อความที่จะแสดง (text body)
- `data-confirm-danger="1"` — ใช้ไอคอน warning + ปุ่มยืนยันสีแดง
- `data-confirm-title` — override title (default: "ยืนยันการดำเนินการ")
- `data-confirm-yes` / `data-confirm-no` — override ปุ่ม

Flash message จาก controller แสดงเป็น toast มุมขวาบนอัตโนมัติ:

```php
return back()->with('status', 'บันทึกเรียบร้อย');  // toast เขียว
return back()->with('error', 'เกิดข้อผิดพลาด');     // toast แดง
```

โหลด SweetAlert ผ่าน CDN — ดู `resources/views/partials/sweetalert.blade.php`
