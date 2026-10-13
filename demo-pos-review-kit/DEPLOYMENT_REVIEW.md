# DEMO POS — implementation and Forge deployment review

**Status: PRE-PUSH DRAFT.** The connected GitHub target repository `baconene/demo-pos` was read-only inspected and was empty. **No GitHub writes were performed.** This kit operates on a local source checkout only; it does not contain the complete source repo.

## Selected source and source-of-truth policy

- Upstream: `baconene/bypassgrill`, pinned to `feature/android-pos-app` commit `5a3761d54e7f9e56eb6032d1993e34d21c868106`.
- Use a copied baseline (not shared databases or API tokens). Keep upstream read-only.
- Destination: `baconene/demo-pos`, default `main` branch proposed. The repository is empty; do not initialize/push until owner approves.
- Source includes PHP 8.3+ / Laravel 13 / Vue + Inertia + Vite 8 / Expo Android, plus an APK GitHub Actions workflow.
- Source's `.env.example` has an inappropriate existing brand name and duplicated broadcast settings; the safe client template replaces both.

## Required behavior, checked on three layers

| Order type | Web cart | Laravel normal orders | Android offline | Laravel mobile sync |
|---|---|---|---|---|
| `dine_in` | table number required; name optional | `required_if` table | table number required before local save | per-order validated table |
| `takeout` | name or alias required | `required_if` name | name required before local save | per-order validated name |
| `delivery` | name + contact + address | `required_if` 3 fields | all three required before local save | per-order validates all three |

The cart must initialize and reset to `dine_in`. Editing an existing order should retain that order's original order type. For failed offline validation, sync returns a per-order `error`; the Android sync queue retains that order in `FAILED`, preventing a partial or invalid server order. Retrying requires correction before a later successful sync.

## What `prepare_demo_pos.py` stages

- Exact application baseline copied to an independent local directory, excluding `.git`, `.env`, local keys, caches, dependencies, source demo screenshots and business-specific generated data.
- Client metadata (`DEMO POS`, domain, address) and neutral brand shell/monogram.
- Replaces Bypass Grill web/Android identifiers and original mobile API endpoint; Android Expo `com.baconologies.demopos` with a placeholder DP icon.
- Source web cart default and conditional checks, corresponding Android checkout checks, Laravel form request rules, and per-order validation for Android offline synchronization.
- Receipt defaults to `DEMO POS` and `DEMO ADDRESS`; cloud printing starts disabled until isolated credentials and a printing service are configured.
- Disables inherited product/user/sample seeding; normal DB seeding keeps roles/permissions and standard payment tenders only. No inherited users, passwords, products, menu, costs, suppliers, or orders.
- Android APK workflow retargeted to `main`, renamed release artifact.
- Preserves all six inventory/report CSS/component files previously missing during Load Cafe's migration.
- Removes remote Bunny font fetch from Vite configuration to reduce Forge build networking failures.
- Generates a residual-branding report for code review; **some technical CSS classes and unused source-specific screens are expected to remain** until final visual cleanup.

## Client branding review blockers

The source `Welcome.vue` is heavy with Bypass Grill marketing and its own public cart/Facebook workflow. This kit applies basic neutral copy/asset fallback and retains its core state and order-list builder; **a product-quality minimal welcome page still requires visual review and testing**. No client Facebook/ordering contact was provided, so no Bypass Grill Facebook URL should remain and the public outbound send links are hidden until DEMO POS contact details are supplied. Staff POS functions do not depend on the public Facebook link.

Inherited source demo screenshots are intentionally omitted. Source-specific public demo/tutorial routes are disabled rather than serving stale branded screenshots. Other brand words remaining in non-customer-facing class names should be reviewed, not mechanically renamed; changing Vue/CSS identifiers without verifying paired references can break the build.

## Critical acceptance tests before first push

1. Confirm source checkout hash exactly `5a3761d54e7f9e56eb6032d1993e34d21c868106`; stop and re-audit if upstream changes.
2. Run the staging kit; inspect `CLIENT_STAGING_REPORT.json` and confirm no `bypassgrill.baconologies.com` or source-owned login/receipt/customer-facing content.
3. Run `php -l` on all modified PHP files, `composer install --no-dev --optimize-autoloader --no-interaction`, and `php artisan migrate --force` on an isolated TEST database.
4. Run `npm ci`, `npm run build` with Node 22 and PHP dependencies installed (Vite's Wayfinder integration invokes artisan).
5. Run Laravel feature tests including conditional field tests and offline batch tests. Audit old sample-data-dependent tests after disabling business-specific seeders.
6. Run `cd android-app && npm install && npm run typecheck`, and build a **standalone release APK** in GitHub Actions only after push is approved. Verify embedded JS bundle and package ID before distributing.
7. Cross-check web, Android and server: dine-in with table/no name succeeds; missing table fails; takeout with name succeeds/missing name fails; delivery complete succeeds; missing any one required field fails.
8. Verify offline creation, duplicate-free sync, rejected payload retained with error, correction/retry, payments, and synchronized receipt layout.
9. Inspect responsive welcome, dashboard, POS, inventory, reports, kitchen monitor and print settings for legacy brand/copy and legible neutral colors.
10. Validate print service/Pusher setup, Android Bluetooth pairing, print permissions, upload path, and printer settings sync for this client.
11. Confirm client admin provisioning and authorize who gets first administrator access (NO seeded passwords). Fortify public registration is disabled in the source.
12. Obtain explicit owner approval, **then** make initial GitHub commit/push. Recheck target is still empty immediately before writing.

## Forge settings and deployment order

- Proposed site: `demo-pos.baconologies.com`, PHP 8.4, Node 22, MySQL 8, PHP extensions required by Laravel and dependencies.
- Point Forge's site at the **approved** `baconene/demo-pos` branch `main` and use a new database/DB login exclusive to DEMO POS.
- DNS A record must resolve to the intended Forge server; SSL certificate must cover the domain. Confirm separately.
- Store actual `.env` in Forge's encrypted environment manager or server secure environment file; do not place credentials or `APP_KEY` in GitHub.
- Install composer dependencies before Vite build; Node must be available during build. Ensure queue worker is enabled for `QUEUE_CONNECTION=database` and Laravel scheduler is configured if required.
- First release: `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`; `npm ci`; `npm run build`; `php artisan storage:link`; `php artisan migrate --force`; `php artisan config:cache`; `php artisan route:cache`; `php artisan view:cache`; restart queue workers. Use existing Forge deployment release mechanics instead of changing symlink targets manually.
- Provision admin account using an approved one-time flow after install. Never seed passwords or copy source users.
- Set web printing enabled only once Pusher Channels, printing service, and receipt options are configured per client. Android printer pairing is a separate on-device task.
- Build APK with `EXPO_PUBLIC_API_URL=https://demo-pos.baconologies.com/api/v1`. This environment value is **public and bundled**, not a secret. Verify `com.baconologies.demopos` in release package.

## Remaining client details to collect before go-live

- Initial DEMO POS administrator email (provide privately, without password in chat or repository).
- Actual client logo (neutral `DP` placeholder is temporary).
- Public ordering/contact/social page, if customers will send an order request from the landing page.
- Production MySQL host/name/user provisioned in Forge, plus per-client Pusher configuration and print endpoint. Keep all actual secrets out of chat and GitHub.
- Confirm whether initial push should create the target default `main` branch (recommended for this empty repository).

## Approval gate

**Do not upload, commit, push, deploy, change target branch, or send APK until the user specifically authorizes it.** This document and staging kit are for code review, not proof of a production-ready deployed application.