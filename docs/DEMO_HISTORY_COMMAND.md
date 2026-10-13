# DEMO POS – configurable fictional history generator

**Fictional training data only. Never run against a real customer database.**

The generic catalog is included in the regular `php artisan db:seed --force`. It creates no users and no historical orders.

The history command is **deliberately separate** so deploying the application or reseeding its roles never fabricates orders, payments or expenses. It reuses production POS `StoreOrderRequest` validation, `OrderService::createOrder`, `PaymentService::processPayment`, `InventoryService` for raw stock purchases, and `FoodProductionService` for prepared Food production. Cash expenses are recorded in the same `financial_transactions` ledger used by Financial Reports. No payment, order total or COGS is inserted outside the application services. Every generated row is synthetic; no real payment gateway is charged and no kitchen receipt is printed.

## Setup – disposable DEMO POS only

1. Back up the DEMO POS database and confirm it has **no real transaction history**.
2. Deploy the updated code, `php artisan migrate --force`, then `php artisan db:seed --force` to create the fictional catalog plus standard roles/payment tenders.
3. Create the real initial administrator at `/setup/admin` with the configured Forge-only setup token. An administrator is required because POS services record the authenticated user ID.
4. Preview the plan (no writes):

   ```bash
   php artisan demo:seed-history --months=4 --orders-per-day=3 --dry-run
   ```

5. In the **disposable** Forge site's Environment variables, temporarily set `DEMO_POS_ALLOW_HISTORY_SEED=true` and clear/rebuild config cache (`php artisan config:cache`). Then run:

   ```bash
   php artisan demo:seed-history --months=4 --orders-per-day=3 --seed=20261013 --confirm-demo
   ```

6. **Immediately remove** the opt-in in Forge, run `php artisan config:cache` again, and keep the site clearly marked as DEMO.

The range covers **the selected number of calendar months, including the current month up to yesterday** in the application's timezone. Default 4 months. For example, on 2026-10-13 the generated range is July 1 – October 12, 2026. Weekends have modestly higher order volume. Default 3 orders on weekdays, 5 on weekends, with occasional promotions. `--orders-per-day` accepts 1–20, `--months` 1–12, and `--seed` 0–999999999. Change these CLI arguments whenever the demo training requirements change. Monthly fictional rent, utilities and marketing amounts are centralized in `config/demo_data.php` and can be revised without changing transactional code.

## Important safeguards

- Refuses to run unless initial setup was completed and an admin exists.
- Refuses if **any** existing order, payment, financial transaction, inventory transaction or inventory cost ledger entry exists, including previous seeded history.
- Refuses if non-demo products are present or any of the 10 expected demo SKUs are missing.
- Requires both `DEMO_POS_ALLOW_HISTORY_SEED=true` and `--confirm-demo` (except harmless `--dry-run`).
- Entire generation happens in one database transaction; failure rolls back new orders, payments, inventory and expense rows together.
- Every paid order calls the POS payment service, and completed sample orders are archived **without** triggering live printer / external notifications.
- No hardcoded administrator or staff password is included.
- Do not run `migrate:fresh` on production. Recreate or replace only a disposable demo database after backup if you want a different existing history, and rerun the one-time admin setup.

## Adjusting the generator later

- `config/demo_data.php` — defaults and monthly overhead expense assumptions.
- `database/seeders/DemoCatalogSamples.php` — fictional products, Food recipes, prices and stock.
- `app/Services/DemoHistoryGenerator.php` — date distribution, tenders, order types, product mix, simulated purchasing and preparation.
- `app/Console/Commands/SeedDemoHistory.php` — command syntax and safety switches.

**Data consistency:** `order` financial entries are references, not revenue cash receipts. `payment` entries are money received. Stock purchases record both actual sample cash outflows and inventory cost ledger purchase entries; the latter are not counted as cash expenses again. Food production moves ingredient value into prepared Food rather than expensing it again. Revenue and COGS reports continue using their normal application logic.
