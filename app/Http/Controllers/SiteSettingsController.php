<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SiteSettingsController extends Controller
{
    /**
     * Editable keys grouped by section. `i18n` = TH + EN fields,
     * `shared` = single value (not translated).
     */
    public const SCHEMA = [
        'Brand' => [
            'brand.name' => ['type' => 'shared', 'label' => 'Brand name'],
            'brand.tagline' => ['type' => 'i18n', 'label' => 'Tagline'],
            'brand.email' => ['type' => 'shared', 'label' => 'Email'],
            'brand.phone' => ['type' => 'shared', 'label' => 'Phone'],
            'brand.line' => ['type' => 'shared', 'label' => 'LINE'],
        ],
        'Home — Hero' => [
            'home.heroEyebrow' => ['type' => 'i18n', 'label' => 'Eyebrow'],
            'home.heroTitle' => ['type' => 'i18n', 'label' => 'Headline'],
            'home.heroDescription' => ['type' => 'i18n', 'label' => 'Description', 'textarea' => true],
            'home.heroPrimary' => ['type' => 'i18n', 'label' => 'Primary button'],
            'home.heroSecondary' => ['type' => 'i18n', 'label' => 'Secondary button'],
        ],
        'Home — Stats' => [
            'home.stat1Value' => ['type' => 'shared', 'label' => '#1 value (e.g. 50+)'],
            'home.statsClients' => ['type' => 'i18n', 'label' => '#1 label'],
            'home.stat2Value' => ['type' => 'shared', 'label' => '#2 value'],
            'home.statsProjects' => ['type' => 'i18n', 'label' => '#2 label'],
            'home.stat3Value' => ['type' => 'shared', 'label' => '#3 value'],
            'home.statsExperience' => ['type' => 'i18n', 'label' => '#3 label'],
            'home.stat4Value' => ['type' => 'shared', 'label' => '#4 value'],
            'home.statsSupport' => ['type' => 'i18n', 'label' => '#4 label'],
        ],
        'Home — Why us' => [
            'home.whyHeading' => ['type' => 'i18n', 'label' => 'Section heading'],
            'home.whySubheading' => ['type' => 'i18n', 'label' => 'Subheading'],
            'home.why1Title' => ['type' => 'i18n', 'label' => '#1 title'],
            'home.why1Desc' => ['type' => 'i18n', 'label' => '#1 description', 'textarea' => true],
            'home.why2Title' => ['type' => 'i18n', 'label' => '#2 title'],
            'home.why2Desc' => ['type' => 'i18n', 'label' => '#2 description', 'textarea' => true],
            'home.why3Title' => ['type' => 'i18n', 'label' => '#3 title'],
            'home.why3Desc' => ['type' => 'i18n', 'label' => '#3 description', 'textarea' => true],
            'home.why4Title' => ['type' => 'i18n', 'label' => '#4 title'],
            'home.why4Desc' => ['type' => 'i18n', 'label' => '#4 description', 'textarea' => true],
        ],
        'Home — CTA' => [
            'home.ctaHeading' => ['type' => 'i18n', 'label' => 'CTA heading'],
            'home.ctaSubheading' => ['type' => 'i18n', 'label' => 'CTA subheading', 'textarea' => true],
            'home.ctaButton' => ['type' => 'i18n', 'label' => 'CTA button'],
        ],
        'About' => [
            'about.heading' => ['type' => 'i18n', 'label' => 'Page heading'],
            'about.subheading' => ['type' => 'i18n', 'label' => 'Subheading'],
            'about.story' => ['type' => 'i18n', 'label' => 'Story eyebrow'],
            'about.storyText' => ['type' => 'i18n', 'label' => 'Story body', 'textarea' => true],
            'about.missionTitle' => ['type' => 'i18n', 'label' => 'Mission title'],
            'about.missionText' => ['type' => 'i18n', 'label' => 'Mission body', 'textarea' => true],
            'about.visionTitle' => ['type' => 'i18n', 'label' => 'Vision title'],
            'about.visionText' => ['type' => 'i18n', 'label' => 'Vision body', 'textarea' => true],
            'about.valuesHeading' => ['type' => 'i18n', 'label' => 'Values heading'],
            'about.value1Title' => ['type' => 'i18n', 'label' => 'Value #1 title'],
            'about.value1Desc' => ['type' => 'i18n', 'label' => 'Value #1 desc'],
            'about.value2Title' => ['type' => 'i18n', 'label' => 'Value #2 title'],
            'about.value2Desc' => ['type' => 'i18n', 'label' => 'Value #2 desc'],
            'about.value3Title' => ['type' => 'i18n', 'label' => 'Value #3 title'],
            'about.value3Desc' => ['type' => 'i18n', 'label' => 'Value #3 desc'],
            'about.value4Title' => ['type' => 'i18n', 'label' => 'Value #4 title'],
            'about.value4Desc' => ['type' => 'i18n', 'label' => 'Value #4 desc'],
        ],
        'Services page' => [
            'services.heading' => ['type' => 'i18n', 'label' => 'Services heading'],
            'services.subheading' => ['type' => 'i18n', 'label' => 'Services subheading'],
            'services.includedHeading' => ['type' => 'i18n', 'label' => 'Included heading'],
        ],
        'Contact' => [
            'contact.heading' => ['type' => 'i18n', 'label' => 'Page heading'],
            'contact.subheading' => ['type' => 'i18n', 'label' => 'Subheading'],
            'contact.infoTitle' => ['type' => 'i18n', 'label' => 'Info title'],
            'contact.infoHours' => ['type' => 'i18n', 'label' => 'Office hours'],
        ],
        'Footer' => [
            'footer.tagline' => ['type' => 'i18n', 'label' => 'Footer tagline'],
            'footer.copyright' => ['type' => 'i18n', 'label' => 'Copyright line'],
        ],
        'Maintenance' => [
            'maintenance.enabled' => ['type' => 'shared', 'label' => 'Enable maintenance mode (1 = on, blank = off)'],
            'maintenance.message' => ['type' => 'i18n', 'label' => 'Maintenance message', 'textarea' => true],
        ],
        'Free launch / Billing' => [
            'billing.free_mode' => ['type' => 'shared', 'label' => 'โหมดเปิดตัวฟรี — 1 = เปิด (ฟรีทุกคน ลิมิตช่วงเปิดตัว), 0 = ปิด (เริ่มเก็บเงิน ใช้แพ็คเกจจริง). ค่าเริ่มต้น = เปิด', 'wide' => true],
            'billing.launch_max_classrooms' => ['type' => 'shared', 'label' => 'ลิมิตช่วงฟรี: ห้องเรียนต่อบัญชี (เว้นว่าง = ค่าเริ่มต้น 15)'],
            'billing.launch_max_members' => ['type' => 'shared', 'label' => 'ลิมิตช่วงฟรี: สมาชิกต่อทีม (เว้นว่าง = 5)'],
            'billing.launch_max_students_per_classroom' => ['type' => 'shared', 'label' => 'ลิมิตช่วงฟรี: นักเรียนต่อห้อง (เว้นว่าง = 80)'],
        ],
        'Deployment' => [
            'deploy.webhook_url' => ['type' => 'shared', 'label' => 'Plesk Git webhook URL (used by the Pull button in /admin/system)', 'wide' => true],
        ],
        'Donation / บริจาค' => [
            'donate.enabled' => ['type' => 'shared', 'label' => 'เปิดช่องทางบริจาค (1 = เปิด, 0 = ปิด)', 'wide' => true],
            'donate.heading' => ['type' => 'i18n', 'label' => 'หัวข้อหน้าบริจาค'],
            'donate.note' => ['type' => 'i18n', 'label' => 'ข้อความ / คำขอบคุณ', 'textarea' => true],
            'donate.name' => ['type' => 'shared', 'label' => 'ชื่อบัญชี'],
            'donate.account' => ['type' => 'shared', 'label' => 'เลขบัญชี / พร้อมเพย์'],
            'donate.qr' => ['type' => 'shared', 'label' => 'รูป QR (PromptPay / ธนาคาร)', 'wide' => true, 'upload' => true],
        ],
        'Hero / background images' => [
            'hero_image.login' => ['type' => 'shared', 'label' => 'Login / auth background', 'wide' => true, 'upload' => true],
            'hero_image.home' => ['type' => 'shared', 'label' => 'Home', 'wide' => true, 'upload' => true],
            'hero_image.services' => ['type' => 'shared', 'label' => 'Services', 'wide' => true, 'upload' => true],
            'hero_image.about' => ['type' => 'shared', 'label' => 'About', 'wide' => true, 'upload' => true],
            'hero_image.contact' => ['type' => 'shared', 'label' => 'Contact', 'wide' => true, 'upload' => true],
        ],
    ];

    public function edit()
    {
        return view('admin.site-settings', [
            'schema' => self::SCHEMA,
            'values' => Setting::map(),
        ]);
    }

    public function update(Request $request)
    {
        $input = $request->input('s', []);

        foreach ($input as $key => $val) {
            Setting::updateOrCreate(['key' => $key], ['value' => (string) ($val ?? '')]);
        }

        // --- Image uploads (fully traced — see /admin/logs + storage/logs) ---
        // File inputs are named with underscores (no dots); map them back to the
        // real dotted setting keys, whitelisted from the schema.
        $uploadable = [];
        foreach (self::SCHEMA as $fields) {
            foreach ($fields as $k => $cfg) {
                if (! empty($cfg['upload'])) {
                    $uploadable[str_replace('.', '_', $k)] = $k;
                }
            }
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxBytes = 10 * 1024 * 1024;
        $dir = public_path('images/backgrounds');
        $writableProbe = is_dir($dir) ? $dir : (is_dir(public_path('images')) ? public_path('images') : public_path());

        $files = $request->file('upload', []);
        $trace = [
            'fields' => array_keys($files),
            'dir' => $dir,
            'dir_exists' => is_dir($dir),
            'dir_writable' => is_writable($writableProbe),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'files' => [],
        ];

        $uploaded = [];
        $errors = [];

        foreach ($files as $field => $file) {
            if (! $file) {
                continue;
            }
            $key = $uploadable[$field] ?? $field;
            $info = [
                'field' => $field,
                'key' => $key,
                'orig' => $file->getClientOriginalName(),
                'client_mime' => $file->getClientMimeType(),
                'kb' => (int) round(($file->getSize() ?: 0) / 1024),
                'php_error' => $file->getError(),
                'valid' => $file->isValid(),
            ];

            if (! isset($uploadable[$field])) {
                $info['result'] = 'skipped (unknown field)';
            } elseif (! $file->isValid()) {
                $info['result'] = 'invalid: '.$file->getErrorMessage();
                $errors[] = $key.' — '.$file->getErrorMessage();
            } elseif (! in_array($mime = (string) $file->getMimeType(), $allowedMimes, true)) {
                $info['result'] = 'rejected mime: '.$mime;
                $errors[] = $key.' — '.($mime ?: 'unknown').' not allowed';
            } elseif (($file->getSize() ?: 0) > $maxBytes) {
                $info['result'] = 'too large';
                $errors[] = $key.' — too large ('.$info['kb'].'KB)';
            } else {
                try {
                    if (! is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $ext = $file->extension() ?: ($file->getClientOriginalExtension() ?: 'jpg');
                    $name = $field.'-'.Str::random(8).'.'.$ext;
                    $file->move($dir, $name);
                    Setting::updateOrCreate(['key' => $key], ['value' => '/images/backgrounds/'.$name]);
                    $uploaded[] = $key;
                    $info['result'] = 'stored /images/backgrounds/'.$name;
                } catch (\Throwable $e) {
                    $info['result'] = 'move failed: '.$e->getMessage();
                    $errors[] = $key.' — '.$e->getMessage();
                }
            }

            $trace['files'][] = $info;
        }

        $meta = ['keys' => array_keys($input)];
        if ($trace['fields'] || $uploaded || $errors) {
            $meta['uploaded'] = $uploaded;
            $meta['errors'] = $errors;
            $meta['upload_trace'] = $trace;
            Log::info('site-settings image upload', ['admin' => auth()->id()] + $meta);
        }

        AuditLog::record('site_settings.update', null, 'Site settings', $meta);

        $redirect = redirect()->route('admin.site-settings.edit');

        if ($errors) {
            return $redirect->with('error', __('app.cms.upload_failed').' '.implode(' | ', $errors));
        }

        return $redirect->with('status', __('app.cms.saved'));
    }
}
