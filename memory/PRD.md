# DYNOVA NETWORK — PRD

## Original problem statement
User provided a fully built PHP project (custom MVC, MariaDB) — video-rating
task earning platform with referrals, deposits, withdrawals and an admin panel.
STRICT RULE: do NOT rebuild. Run the existing PHP app inside the Emergent
preview environment, ship iterative feature/bug fixes, and produce Hostinger-
deployable ZIP patches for every change.

## Stack (frozen)
- PHP 8.2 custom MVC — /app/dynova
- MariaDB (local pod for preview; user's Hostinger DB in prod)
- FastAPI reverse proxy at /app/backend/server.py (port 8001) that spawns the
  PHP built-in server on 127.0.0.1:9000 and forwards /api/* -> PHP.
- Frontend React shell is a passthrough only.

## Preview boot sequence (self-heal is automatic)
1. FastAPI lifespan runs /app/scripts/dynova_bootstrap.sh which installs PHP
   + MariaDB if missing, boots MariaDB, applies schema.
2. FastAPI then spawns `php -S 127.0.0.1:9000 -t /app/dynova/public router.php`.
3. Any restart -> just `sudo supervisorctl restart backend`.

## Deliverables format
- Every change produces a Hostinger-deployable ZIP inside
  /app/dynova/public/downloads/ served at
  {REACT_APP_BACKEND_URL}/api/downloads/<file>.zip
- Naming: dynova-PATCH-YYYY-MM-DD[-suffix].zip (changed files only)
  or dynova-FULL-YYYY-MM-DD.zip (whole project).

## Implemented (chronological)
- Withdrawal logic — unlimited repeat of same slab
- Admin Tasks view — Serial numbers
- Deposit payment methods — ID-based (allows dup names)
- User referral tree — downline details + masked WhatsApp
- Password eye toggle on login/signup
- Admin referral tree — multi-search + copy buttons
- Admin developer popup messages
- Admin developer System Lock (maintenance mode)
- Red Envelope feature (wallet bonus, closed envelope UI, gated to active pkg)
- Package upgrade — pro-rata diff, redirect to deposit locking exact amount
- Withdrawal 24-hour lock with UI countdown
- Admin users list pagination
- **2026-07-15b — Red Envelope CLAIM button hotfix** (defensive inline
  fallback in RedEnvelope::claim so it can NEVER 500 again even if a future
  Hostinger upload forgets to overwrite app/helpers.php)

## Test credentials
See /app/memory/test_credentials.md

## Backlog / next
- Await user's next iteration request after they confirm the CLAIM fix on
  the live site.

## Key files
- /app/dynova/app/models/RedEnvelope.php
- /app/dynova/app/helpers.php
- /app/dynova/app/controllers/WalletController.php
- /app/dynova/app/controllers/PackageController.php
- /app/dynova/app/views/user/dashboard.php
- /app/dynova/public/index.php
- /app/backend/server.py
- /app/scripts/dynova_bootstrap.sh
