# DYNOVA NETWORK — Hostinger Shared-Hosting Deployment Guide

This document is a **complete, copy-paste-ready** procedure for deploying DYNOVA on Hostinger (or any cPanel-based shared host). Follow it top-to-bottom; nothing extra is required.

---

## 0. What you'll have when you're done

- A live site at **`https://yourdomain.com/`** that shows the DYNOVA landing page.
- A user portal at **`https://yourdomain.com/?r=auth/signup`**.
- An admin portal at **`https://yourdomain.com/?r=admin/login`** (`admin@dynova.com` / `password` — change immediately!).
- One automatic cron job that handles daily reset, deposit auto-reject, weekly bonus audit and monthly salary payout.
- Uploaded payment screenshots saved under `public_html/public/uploads/deposits/`.

---

## 1. Create the database (Hostinger hPanel)

1. Open **hPanel → Databases → MySQL Databases**.
2. **Create database**:
   - Database name: `dynova_db` (the prefix `u123456_` is added automatically).
   - Username: `dynova_user` (prefix added automatically).
   - Password: pick a strong one and **save it somewhere**.
3. After creation, scroll down to **List of Current MySQL Databases and Users** and confirm:
   - DB: `u123456_dynova_db`
   - User: `u123456_dynova_user`  → role: **All privileges**

> The `u123456_` prefix is unique to your Hostinger account — you'll see your real number; use that everywhere this guide says `u123456_`.

---

## 2. Import the schema

1. hPanel → Databases → **phpMyAdmin** → click **Enter phpMyAdmin** beside the database you just made.
2. In the left sidebar, **click your database name** so it becomes the active one (very important — don't import into the wrong DB).
3. Top tabs → **Import**.
4. **Choose file** → upload **`sql/dynova_full_install.sql`** (from this project).
5. Leave the format as **SQL** and click **Go**.
6. You should see ~30 green ticks and the new tables listed in the sidebar:
   `admin_settings, admins, deposits, joining_bonuses, payment_methods, referrals, salaries, salary_ranks, task_completions, task_packages, tasks, transactions, user_packages, users, withdrawals`

---

## 3. Upload the files

You have two equally good options. **Option A is the recommended (cleanest) approach.**

### Option A — Upload the whole `dynova/` folder into `public_html/`  *(recommended)*

1. On your computer, **zip the entire `dynova/` folder** (the one containing `app/`, `public/`, `sql/`, `cron/`, `scheduler.php`, `composer.json`, `.htaccess`, `INSTALL.md`, `DEPLOY.md`, `HOSTINGER_DEPLOY.md`, etc.).
2. hPanel → **File Manager** → open `public_html/`.
3. **Delete** the default `default.php` / `index.html` Hostinger ships with.
4. Top toolbar → **Upload** → drop the ZIP in.
5. Right-click the ZIP → **Extract** → extract to `public_html/`.
6. After extraction, **drag everything out of the inner `dynova/` folder into `public_html/`** so the layout looks like:
   ```
   public_html/
   ├── .htaccess              ← root htaccess (rewrites everything into public/)
   ├── app/
   ├── cron/
   ├── logs/
   ├── public/                ← real docroot (Apache serves from here)
   ├── sql/
   ├── scheduler.php
   ├── composer.json
   ├── INSTALL.md
   ├── DEPLOY.md
   └── HOSTINGER_DEPLOY.md
   ```
7. Delete the now-empty `dynova/` shell and the ZIP.

The root `.htaccess` automatically redirects every request into `public/` and blocks browser access to `app/`, `cron/`, `sql/`, `logs/`, `scheduler.php`, etc. — you don't need to touch anything else.

### Option B — Use `public/` directly as `public_html/`

If you prefer a "Laravel-style" layout where only `public/` is web-accessible and the rest of the project sits **above** the document root:

1. Move **only the contents of `public/`** into `public_html/` (so `public_html/index.php`, `public_html/router.php`, `public_html/assets/`, etc.).
2. Move `app/`, `cron/`, `sql/`, `logs/`, `scheduler.php`, `composer.json` **one level above**, into your home directory (e.g. `/home/u123456/dynova-private/`).
3. Edit `public_html/index.php`, change the first line to point to the new location:
   ```php
   require_once '/home/u123456/dynova-private/app/bootstrap.php';
   ```
4. Skip the root `.htaccess` — it isn't needed in this layout (the one inside `public/` already handles routing).

> Most users on Hostinger pick **Option A**. Option B is slightly more secure but harder to maintain.

---

## 4. Configure database credentials

Open `app/config.php` in the File Manager's built-in editor and edit the **first block**:

```php
define('DB_HOST', getenv('DYNOVA_DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DYNOVA_DB_PORT') ?: '3306');
define('DB_NAME', getenv('DYNOVA_DB_NAME') ?: 'u123456_dynova_db');
define('DB_USER', getenv('DYNOVA_DB_USER') ?: 'u123456_dynova_user');
define('DB_PASS', getenv('DYNOVA_DB_PASS') ?: 'PUT-YOUR-PASSWORD-HERE');
```

Replace the four `:?` defaults with your actual Hostinger DB values (the ones you wrote down in step 1). **Leave `BASE_URL` alone** — its default is now `''`, which is correct for any cPanel host.

Save the file.

---

## 5. Set folder permissions

In File Manager, right-click each folder below → **Permissions** → set to `755`:

- `public_html/public/uploads/`
- `public_html/public/uploads/deposits/`
- `public_html/logs/`

If `public/uploads/deposits/` or `logs/` don't exist yet, create them (File Manager → right-click → New Folder), then set the permissions.

---

## 6. First-run smoke test

1. Visit **`https://yourdomain.com/`**  →  you should see the DYNOVA landing page.
2. Visit **`https://yourdomain.com/?r=admin/login`** → log in with `admin@dynova.com` / `password`.
3. Immediately: admin → **Settings** → change the admin password.
   *(or via SSH:  `php -r "echo password_hash('NEW-PASS', PASSWORD_BCRYPT);"` and then run that hash into `UPDATE admins SET password_hash='…' WHERE email='admin@dynova.com';` via phpMyAdmin.)*
4. Sign up a test user at **`/?r=auth/signup`** and click around: dashboard, tasks, packages, ranks, joining bonus, wallet, profile.

If you see a white screen with a PHP error, open `app/bootstrap.php` and confirm `display_errors` is `1` — the error will appear in the browser and almost always points to either:
- wrong DB credentials in `app/config.php`, or
- the SQL import in step 2 wasn't run against the **right** database.

---

## 7. Schedule the ONE cron job

hPanel → **Advanced → Cron Jobs**.

1. **Common settings:** `Once per minute (* * * * *)`.
2. **Command:**
   ```
   /usr/bin/php /home/u123456/public_html/scheduler.php >> /home/u123456/public_html/logs/cron.log 2>&1
   ```
   - Replace `u123456` with your real Hostinger user.
   - If your host uses a different PHP path, find it in **hPanel → PHP Configuration** ("Switch PHP version"). It's usually `/usr/bin/php` or `/opt/alt/php82/usr/bin/php`.
3. **Create**.

After ~2 minutes, view `public_html/logs/cron.log` in File Manager. You should see:
```
[2026-XX-XX 12:00:00] [INFO ] [scheduler] tick – nothing due.
```

That's it — DYNOVA will now auto-run:
- Daily reset every day at 00:05
- Stale deposit auto-reject every hour
- Weekly rank/bonus audit every Monday at 02:00
- **Monthly salary payout on the 1st of every month at 03:00**

All four jobs share **`logs/cron.log`** so you have a single audit trail.

---

## 8. SSL / HTTPS

Hostinger ships a free Let's Encrypt SSL.

1. hPanel → **Security → SSL** → choose your domain → **Install**.
2. After install, hPanel → **Domains → Manage** → toggle **Force HTTPS** ON.
3. Reload the site — the lock icon should appear in the browser bar.

DYNOVA already uses `samesite=Lax` cookies and never assumes HTTP. No code changes needed.

---

## 9. Day-2 operations

| Action | Where |
|---|---|
| Approve / reject deposits | `/?r=admin/deposits` |
| Approve / mark-paid withdrawals | `/?r=admin/withdrawals` |
| Add / edit task packages | `/?r=admin/packages` |
| Edit joining-bonus tiers | `/?r=admin/bonuses` |
| Edit salary ranks | `/?r=admin/ranks` |
| Trigger this month's payout manually | `/?r=admin/ranks` → "Run Monthly Payout Now" |
| Edit JazzCash / EasyPaisa account numbers | `/?r=admin/settings` |
| See full ledger | `/?r=admin/transactions` |

---

## 10. Backup & restore

**Backup (do this weekly):**

1. phpMyAdmin → select DB → **Export** → format **SQL** → **Go** → save the `.sql` file somewhere safe.
2. File Manager → right-click `public_html/public/uploads/` → **Compress** → download the zip.

**Restore:**

1. phpMyAdmin → select empty DB → **Import** → upload the backup `.sql`.
2. File Manager → extract the `uploads.zip` back into `public_html/public/uploads/`.

---

## 11. Common errors

| Symptom | Cause | Fix |
|---|---|---|
| White page on every URL | DB credentials wrong | Re-check `app/config.php`. |
| `Access denied for user …` | DB user not granted on DB | hPanel → MySQL → "Add user to database" → All privileges. |
| 404 on every URL except `/` | `mod_rewrite` disabled OR root `.htaccess` missing | Confirm `public_html/.htaccess` exists (step 3 / Option A). Hostinger has `mod_rewrite` on by default — if it's off, **hPanel → Advanced → .htaccess Editor** can re-enable it. |
| Static assets 404 (`assets/css/style.css`) | Old `BASE_URL=/api` still in `config.php` | Make sure you have the latest `config.php` (default is `''`). |
| Cron log never gets new lines | Wrong PHP path in the cron command | Try `/opt/alt/php82/usr/bin/php` instead of `/usr/bin/php`. |
| Uploads fail with "Could not save screenshot" | `uploads/deposits/` not writable | Set permissions to `755` (step 5). |

---

## 12. Hardening checklist (recommended)

- [ ] Change the default admin password.
- [ ] **Change the developer password** in `app/config.php` (the `DEV_ACCESS_PASSWORD` constant) or set the `DYNOVA_DEV_ACCESS_PASSWORD` env var if your hosting supports it. The default ships as `DynovaDev@2026` — pick something only you (the developer) know.
- [ ] Edit the payment method account numbers in **Admin → Settings**.
- [ ] Add your support WhatsApp + branding text in **Admin → Settings**.
- [ ] Configure salary ranks for your real economy in **Admin → Salary Ranks**.
- [ ] Review the joining-bonus values per package in **Admin → Joining Bonuses**.
- [ ] Turn ON Force-HTTPS (step 8).
- [ ] Enable Hostinger's nightly **Automatic Backups** feature (hPanel → Files → Backups).
- [ ] In `app/bootstrap.php`, change `ini_set('display_errors', '1')` to `'0'` once the site is stable.

---

## 13. Developer-protection system (how it works)

The admin panel ships with a **two-layer permission model**:

| Who | Can View | Can Add / Edit / Delete |
|---|---|---|
| Admin (after admin login) | ✅ Everything | ❌ Until developer unlock |
| Developer (knows the dev password) | ✅ Everything | ✅ For 60 minutes per unlock |

**What's gated:** Packages · Salary Ranks · Joining Bonuses · Tasks · Site Settings · User block / unblock / balance adjust.
**What stays accessible:** Deposit approve/reject and Withdrawal approve/mark-paid (day-to-day ops).

**Workflow:**

1. Admin logs in normally at `/?r=admin/login`.
2. The admin sees a yellow banner at the top of every admin page: *"Admin is in read-only mode. Adding, editing or deleting data requires a developer unlock."* Every form is dimmed and visually disabled.
3. Admin clicks **"Unlock to edit"** (banner button) → a developer-password page appears.
4. Developer (only person who knows the password) enters the password → forms become live for the next **60 minutes**.
5. The banner turns green showing the remaining time. A **"Lock now"** button revokes the unlock immediately.
6. Admin logging out or the session expiring also re-locks automatically.

**Changing the developer password:**

Open `app/config.php` and edit the constant:

```php
define('DEV_ACCESS_PASSWORD',
    getenv('DYNOVA_DEV_ACCESS_PASSWORD') ?: 'YOUR-NEW-PASSWORD-HERE'
);
```

(or — if Hostinger exposes env vars — set `DYNOVA_DEV_ACCESS_PASSWORD` in hPanel → PHP Configuration → Environment Variables and leave the file unchanged).

**Changing the unlock duration:**

```php
define('DEV_UNLOCK_TTL_MINUTES', 60);   // change to taste
```

---

You're done. The same project that runs on Emergent's preview is now running on your Hostinger domain — no features lost, no code changes needed beyond the DB credentials.
