<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\AuditLog;
use Illuminate\Http\Request;
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
        'Deployment' => [
            'deploy.webhook_url' => ['type' => 'shared', 'label' => 'Plesk Git webhook URL (used by the Pull button in /admin/system)', 'wide' => true],
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
        // Images only: no SVG (script vector), capped at 5 MB.
        $request->validate([
            'upload.*' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $input = $request->input('s', []);

        foreach ($input as $key => $val) {
            Setting::updateOrCreate(['key' => $key], ['value' => (string) ($val ?? '')]);
        }

        // Uploaded files override the matching text value with a stored path.
        $uploaded = [];
        foreach ($request->file('upload', []) as $key => $file) {
            if (! $file) {
                continue;
            }
            $ext = $file->extension() ?: $file->getClientOriginalExtension();
            $name = str_replace('.', '_', $key).'-'.Str::random(8).'.'.$ext;
            $file->move(public_path('images/backgrounds'), $name);
            Setting::updateOrCreate(['key' => $key], ['value' => '/images/backgrounds/'.$name]);
            $uploaded[] = $key;
        }

        AuditLog::record('site_settings.update', null, 'Site settings', [
            'keys' => array_keys($input),
            'uploaded' => $uploaded,
        ]);

        return redirect()->route('admin.site-settings.edit')
            ->with('status', __('app.cms.saved'));
    }
}
