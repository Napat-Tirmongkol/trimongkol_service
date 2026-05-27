# Tirmongkol Service

Laravel project — installed and managed via Plesk's Composer installer.

> **Note**: This repository was previously a Next.js project. All Next.js code has been removed.
> The previous version is preserved in git history (commit `0f7f5fc` and earlier).

## Setup on Plesk

1. **Disable Node.js** on the domain
   - Plesk → trimongkol.com → Node.js → **Disable Node.js**

2. **Remove old git deployment**
   - Plesk → Git → remove the current repository link (or change deploy path to a folder outside `/httpdocs`)
   - Empty `/httpdocs/tirmongkol_service` (the old Next.js folder)

3. **Install Laravel via Composer**
   - Plesk → trimongkol.com → **Composer** → Create Application → choose `laravel/laravel`
   - Or via SSH: `composer create-project laravel/laravel /var/www/vhosts/trimongkol.com/httpdocs`

4. **Set Document Root** to `/httpdocs/public` (Laravel's public folder)
   - Plesk → trimongkol.com → Hosting Settings → Document root: `/httpdocs/public`

5. **PHP version**: 8.3+ (Laravel 11 needs 8.2+, Laravel 12 needs 8.3+)
   - Plesk → PHP Settings → PHP version: 8.3.x

6. **Database**
   - Plesk → Databases → Add Database (MySQL/MariaDB)
   - Update `.env`:
     ```
     APP_URL=https://trimongkol.com
     DB_CONNECTION=mysql
     DB_DATABASE=...
     DB_USERNAME=...
     DB_PASSWORD=...
     ```

7. **Finalize**
   - File Manager or SSH: run
     ```
     php artisan key:generate
     php artisan migrate
     php artisan storage:link
     ```
   - Set permissions: `storage/` and `bootstrap/cache/` → 775

## Linking back to git (optional)

After Laravel is installed on the server, to track it in this repo:

```bash
# On server (via SSH)
cd /var/www/vhosts/trimongkol.com/httpdocs
git init
git remote add origin https://github.com/Napat-Tirmongkol/trimongkol_service.git
git add .
git commit -m "Initial Laravel install"
git push -u origin main --force
```

Then locally:
```bash
git pull --rebase
```
