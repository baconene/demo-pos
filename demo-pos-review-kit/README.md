# DEMO POS — pre-push review and implementation kit

**STATUS: Historical pre-push review kit.** The DEMO POS application is now published to `baconene/demo-pos/main`. The one-time initializer was retired after the source and validation checks passed. This folder documents the reproducible source conversion; the repository root README is the current deployment guide.

Client configuration:
- Master/source: `baconene/bypassgrill` at commit `5a3761d54e7f9e56eb6032d1993e34d21c868106`, branch `feature/android-pos-app` (read-only source).
- Client/destination: `baconene/demo-pos` (verified public and empty when audited).
- Site: `https://demo-pos.baconologies.com`; display name: **DEMO POS**; address: **DEMO ADDRESS**.
- Visual style: neutral minimalist, slate/white. Logo is a **temporary neutral DP placeholder** until client-supplied logo is available.
- Platforms: Laravel/Inertia/Vue web, offline React Native/Expo Android, web cloud printing and Android Bluetooth printing.
- Order default: `dine_in`. Conditional customer fields:
  - Dine-in: table number required, name optional.
  - Takeout: customer name/alias required.
  - Delivery: name, contact and address required.

## Important risk findings from read-only source audit

1. `android-app/src/api/mobilePos.ts` embeds Bypass Grill's production API base. Replacing the Android app display name alone **would not** isolate the client. The prepared script substitutes a DEMO POS address and a public build-time Expo variable.
2. The source's Android APK workflow is triggered on `feature/android-pos-app`, not the destination's default branch. The kit adjusts trigger and artifact naming.
3. `DatabaseSeeder` calls product/ingredient/recipe/user seeders that contain source-specific data. Some source seeders include preset accounts and passwords. The kit disables those data seeders for the client. Initial production admin access MUST be provisioned separately using an approved, secure onboarding process.
4. Web/Android/API order validation is inconsistent in the source: web requires a customer name for all orders, while normal API fields are nullable and offline batch sync also accepts blank fields. The kit adds matching conditional checks for all three layers.
5. No Forge deployment was run and no Laravel/Vite or Android release build has been executed against the fully prepared repository. The kit includes tests and a deployment checklist; they must be run on an actual source checkout before approval to push.
6. The master Bypass Grill repository has hardcoded marketing material and brand-specific screens. The kit applies initial neutral branding and produces a **residual-branding report**. It is a blocking review requirement, not a guarantee that all visible UI is finished.

## What is in this kit

- `client.json`: nonsecret, portable client config.
- `.env.example`: safe Laravel/Forge environment template (never an actual `.env`).
- `android.env.example`: safe public Expo API example; **do not** put tokens in `EXPO_PUBLIC_` keys.
- `prepare_demo_pos.py`: stages a client-specific source copy from an authorized local checkout. It does **not** push and never calls GitHub.
- `tests/test_prepare_demo_pos.py`: unit tests for the essential transformations.
- `DEPLOYMENT_REVIEW.md`: review gates, installation and Forge runbook.

## Stage locally (does NOT upload anything)

First obtain a local checkout of the source's pinned commit using a trusted GitHub workflow or computer with access to that repository. This environment couldn't reach GitHub from the local shell, so the full source has **not** been cloned here.

```bash
python prepare_demo_pos.py --source /path/to/bypassgrill-checkout --destination /path/to/demo-pos-staging
```

The script creates the staging copy and a JSON transformation report. It refuses an existing destination and skips `.env`, `.git`, installed dependencies, caches, build outputs, and private local workspace configuration. It checks `git rev-parse HEAD` read-only against the pinned upstream commit. It never runs `git push` or deploys to Forge.

```bash
python -m unittest discover -s tests -v
```

Review `demo-pos-staging/CLIENT_STAGING_REPORT.json` and fix all remaining customer-facing references or unvalidated asset dependencies. **The staging output is not approved for production merely because the script completed.**

## Approval gate

Wait for explicit user approval before any commit, push, repository initialization, Forge deployment, or Android distribution. The target repository remains untouched in this phase.