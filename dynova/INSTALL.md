# Quick Install Guide

## Option A — cPanel (production)
1. **Database:** cPanel → MySQL → create DB + user → assign privileges.
2. **Upload:** unzip → upload everything so that:
   - `public/` → `public_html/`
   - `app/`, `cron/`, `sql/` → one level above `public_html/` (safer)
3. **Adjust path:** open `public_html/index.php` and change:
   ```php
   require_once __DIR__ . '/../app/bootstrap.php';
   ```
   to point at wherever you placed `app/`.
4. **Configure:** edit `app/config.php` with your DB credentials.
   Set `BASE_URL = ""` (empty) for cPanel.
5. **Import SQL:** phpMyAdmin → Import → `sql/schema.sql`.
6. **Test:** open your domain → login page should appear.
7. **Admin:** `admin@dynova.com` / `password` → change immediately.
8. **Cron:** add `0 3 * * 0 /usr/bin/php /home/USER/cron/weekly_salary.php`.

## Option B — localhost (XAMPP)
1. Place the project under `htdocs/dynova`.
2. Start Apache + MySQL via XAMPP control panel.
3. Import `sql/schema.sql` via phpMyAdmin.
4. Edit `app/config.php` (DB user usually `root` with empty password). `BASE_URL = ""`.
5. Open `http://localhost/dynova/public/`.

## Option C — PHP built-in server
```bash
php -S 0.0.0.0:8080 -t public public/router.php
```

## Important env vars (optional)
You can override config without editing `app/config.php`:
- `DYNOVA_DB_HOST`, `DYNOVA_DB_PORT`, `DYNOVA_DB_NAME`, `DYNOVA_DB_USER`, `DYNOVA_DB_PASS`
- `DYNOVA_BASE_URL`  ← set to `""` on cPanel, `/api` on the Emergent preview

## Admin password reset
```bash
php -r "echo password_hash('YourNewPass', PASSWORD_BCRYPT);"
```
Then in SQL:
```sql
UPDATE admins SET password_hash='<paste-hash>' WHERE email='admin@dynova.com';
```
