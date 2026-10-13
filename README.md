# DEMO POS

**Client repository:** [baconene/demo-pos](https://github.com/baconene/demo-pos)

**Source of truth:** `baconene/bypassgrill`, approved base commit `5a3761d54e7f9e56eb6032d1993e34d21c868106` (`feature/android-pos-app`).

DEMO POS is a standalone Laravel 13 / Vue + Inertia web POS with an Expo React Native offline-first Android POS, inventory, reports, kitchen monitor, financial reporting, deposit control, and printer settings.

## Client configuration

| Setting | Value |
|---|---|
| Business name | DEMO POS |
| Public domain | http://demo.pos.baconologies.com |
| Business address | DEMO ADDRESS |
| Appearance | Neutral minimalist: slate / white |
| Default order type | Dine-in |
| Android package | `com.baconologies.demopos` |
| Web and Android printing | Available after configuring an isolated printing service |

### Conditional order field rules

- **Dine-in:** table number required, customer name optional.
- **Takeout:** customer name or alias required.
- **Delivery:** customer name, contact number and address required.

Rules are validated in the web cart, Laravel API and Android checkout. Android offline order synchronization revalidates each order individually, and invalid orders stay queued for correction.

## First deployment on Laravel Forge

1. Create a separate Forge site for `demo.pos.baconologies.com`, connecting this repository's `main` branch.
2. Provision a new MySQL database/user exclusive to DEMO POS, then add the actual environment values in Forge. See root `.env.example`.
3. Generate a unique `APP_KEY` through Forge. Do not commit the production `.env` file, secrets, account credentials, or any source business data.
4. Install PHP 8.4+, Node 22, Composer, and MySQL 8; execute `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction` and `npm ci && npm run build`.
5. Run `php artisan migrate --force`, create the storage link, enable queue workers, and cache configuration/routes/views as supported by Forge.
6. On an intentionally empty DEMO POS database, `php artisan db:seed --force` installs roles, tenders and a fictional generic demo product/recipe catalog. It never creates administrator accounts or historical transactions.
7. Provision the first administrator securely: generate a random setup code (`openssl rand -hex 32`), save it **only** as Forge's `INITIAL_ADMIN_SETUP_TOKEN`, then run `php artisan migrate --force`, `php artisan db:seed --force`, and `php artisan config:cache`. Enable HTTPS in Forge before entering credentials, then open `/setup/admin` and enter a unique admin name, email, 12+ character strong password, and Forge setup code. The setup form disappears permanently after the first admin is created, even if that user is later deleted. Immediately delete `INITIAL_ADMIN_SETUP_TOKEN` from Forge and refresh config cache. Public registration remains disabled; future staff users are created by the authenticated administrator.
8. Configure isolated Pusher Channels and/or printing service settings before enabling cloud receipt printing.
9. Build the Android APK from **Actions → Android POS APK**; configure each handset's printer and validate offline sync against this site's API.
10. Smoke-test POS checkout, order type validation, online/offline payments, kitchen status changes, inventory and reports.

## Fictional four-month history

The separate `demo:seed-history` command creates configurable synthetic order, payment, inventory and expense activity **through the existing POS services**. It is never part of normal `db:seed`. Preview without writing:

```bash
php artisan demo:seed-history --months=4 --orders-per-day=3 --dry-run
```

After creating the first admin, and only on a disposable DEMO POS database with no previous orders, financial or inventory movements, temporarily enable `DEMO_POS_ALLOW_HISTORY_SEED=true` in Forge, rebuild the config cache, then run:

```bash
php artisan demo:seed-history --months=4 --orders-per-day=3 --seed=20261013 --confirm-demo
```

Remove the environment opt-in immediately afterward. The 4-month default includes the current month (through yesterday) and the preceding three calendar months. Change `--months`, `--orders-per-day`, `--seed` or the assumptions in `config/demo_data.php` for future demo scripts. See [docs/DEMO_HISTORY_COMMAND.md](docs/DEMO_HISTORY_COMMAND.md) for safety checks and financial semantics.

## Delivery status

GitHub Actions web CI validates Vite build and conditional order rules. Successful CI does **not** by itself imply that Forge/DNS/SSL, production database, receipt printing, or Android APK installation have been configured or verified.

The `demo-pos-review-kit` folder holds the pinned initial-client preparation script and its tests. Changes for other clients belong in their own repositories; Bypass Grill's source remains unmodified.
