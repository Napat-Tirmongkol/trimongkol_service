# Tirmongkol Service

Laravel-based public website for **Tirmongkol Service** — software development for Thai SMEs
(queue booking, product management, POS, CRM, websites, HR).

Bilingual TH / EN, served at `https://trimongkol.com/`.

## Stack

- **Laravel 11** with Blade templates
- **Laravel Breeze** for authentication (login / register / password reset / email verify)
- **Livewire 3** for the upcoming admin/scan dashboard (real-time UI)
- **Tailwind CSS v4** via Vite
- **Alpine.js** for the mobile menu toggle
- Translations in `lang/th/site.php` and `lang/en/site.php`, switched via session cookie

## Production checklist

Before opening sign-ups, on the Plesk server `.env` must have:

```
APP_ENV=production
APP_DEBUG=false
APP_NAME="Tirmongkol Service"      # must be quoted

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

Then run on the server (Plesk → Composer → Run command, or via task):

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate

npm install
npm run dev          # in one terminal
php artisan serve    # in another → http://localhost:8000
```

## Deploy on Plesk (Git pull → build is committed)

The `public/build/` directory is committed, so Plesk does **not** need Node.js installed.

1. **Push to GitHub**: `git push origin main`
2. **Plesk → Git → Pull updates** (or auto-pull on push)
3. **Plesk → Composer → Update dependencies** (runs `composer install` against `composer.lock`)
4. SSH or Plesk Scheduled Tasks:
   ```bash
   cd /var/www/vhosts/trimongkol.com/httpdocs
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

If `public/build/` needs to be regenerated after CSS/JS edits, run `npm run build` locally before pushing — the new manifest gets committed.

## Editing content

- Text strings: `lang/th/site.php` and `lang/en/site.php` (mirror each other key-for-key)
- Email / phone / LINE: `config/site.php`
- Services list: `services.items` array in both lang files (must stay in sync)

## Routes

| URL | Controller | Purpose |
|-----|-----------|---------|
| `/` | `PageController@home` | Hero, services preview, why-us, CTA |
| `/services` | `PageController@services` | Full services grid |
| `/about` | `PageController@about` | Story, mission, vision, values |
| `/contact` | `PageController@contact` | Contact info + form |
| `POST /contact` | `ContactController@submit` | Form submission (currently no email send — wire SMTP later) |
| `/locale/{th,en}` | `LocaleController@switch` | Switch UI language |
