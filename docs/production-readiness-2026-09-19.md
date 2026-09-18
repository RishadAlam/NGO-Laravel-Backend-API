# Temporary sessions and authentication release review — 19 September 2026

## Changes reviewed

- Permission-gated, 15-minute, view-only staff visits. The backend checks the original administrator login, current permission, both accounts and expiry on every temporary request. The original login is preserved. The scheduler finalizes expired visits and records missing-token revocations without waiting for another browser request.
- Browser return controls recover from stalled requests after 10 seconds. Refresh, expiry, failed bootstrap, cache separation and original-login preservation have regression coverage.
- Password recovery now requires account/purpose-bound OTP verification followed by a hashed, account-bound, single-use reset proof valid for five minutes. Resending invalidates previous codes and proofs. User-row locks serialize proof issuance, consumption and login-token issuance. Reset revokes all account tokens and affected temporary sessions. Existing verification codes cannot authorize password recovery.
- Public HTTP Artisan utilities and the development permission self-grant route are removed. Administrative maintenance uses the CLI.
- The deployment hook requires a nonempty configured secret in `X-Deploy-Key`, never generates APP_KEY or runs demo seeders, serializes activation, and fails on unsuccessful Artisan commands.
- Production Composer autoload includes API response helpers. Client factory namespaces match case-sensitive filesystems.

## Verified locally

| Check | Result |
| --- | --- |
| Complete backend PHPUnit suite | 92 tests, 929 assertions passed |
| Adjacent frontend's isolated release HTTP suite | 21 tests, 370 assertions passed |
| Complete frontend Vitest suite | 994 tests across 46 files passed |
| Frontend production build | Passed |
| Changed JavaScript ESLint | 16 files passed |
| Composer manifest and local production platform requirements | Passed |
| Isolated no-dev optimized autoloader with strict PSR checks | Passed; both response helper functions loaded |
| Cached route smoke check | New deployment controller loaded; removed permission endpoint absent |
| Workflow YAML parsing | Both workflows passed |

All automated HTTP tests use synthetic accounts and SQLite memory databases. Mail generation is tested with fake mail; these tests do not prove live SMTP delivery. The simulated login/reset race verifies credential revalidation, but SQLite does not reproduce MySQL row-lock contention. Existing build warnings include Sass deprecations and a large main JavaScript chunk; they do not fail the build.

An initial full frontend run timed out in a form test under concurrent test load. The isolated form suite and subsequent full suite passed without weakening timeouts or assertions.

## Production activation

1. Back up the production database and preserve the existing APP_KEY. Confirm PHP 8.3 and Composer platform requirements on the host. Configure `APP_ENV=production`, `APP_DEBUG=false`, HTTPS application/API URLs, mail delivery, and a nonempty `DEPLOY_KEY` matching the deployment secret. Do not run `RolePermissionSeeder` on production to enable this feature: it creates a demo administrator and grants broad permissions.
2. Deploy the backend and frontend together in a maintenance window. The recovery API now requires `user_id` and `purpose` for OTP verification and `reset_token` for password changes. An older frontend cannot use the new recovery contract. Existing recovery attempts should start again.
3. The backend release workflow installs locked dependencies, tests, builds a no-dev vendor tree and a fresh route cache, then uploads them. It preserves the server's `bootstrap/cache/config.php` until the authenticated hook rebuilds it. Shipping the new route cache is necessary: an old cached deployment closure would otherwise keep running after source upload. Different production release tags share a concurrency group to prevent overlapping FTP uploads. FTP deployment is not atomic; use the maintenance window to avoid requests during file replacement.
4. The hook runs migrations and rebuilds config, routes and views. Both `2026_09_19_000000_create_user_impersonation_sessions` and `2026_09_19_010000_add_purpose_to_users_verifies` must be applied. For a manual deployment, run `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache` using the application's user. Preserve existing storage and create the public storage symlink through the host CLI if needed.
5. Add one scheduler entry on the production host, replacing the paths with the real application and PHP paths:

   ```cron
   * * * * * cd /path/to/backend && /usr/bin/php artisan schedule:run >> storage/logs/scheduler.log 2>&1
   ```

   Confirm `php artisan schedule:list` includes `impersonation:expire` every minute and observe one successful run. Configure host log rotation. The Mac login service is local development only and does not run production jobs.
6. Grant `staff_impersonate` only to intended administrators through Staff Permissions or Role Permissions; they also need `staff_list_view` for the Staffs entry point. The migration does not grant it automatically. Refresh the app after assigning it.
7. Verify real mail delivery with an approved test account, then exercise login, recovery, a view-only visit, refresh, early return, expiry, and permission revocation on staging/production. Complete Android Chrome and iPhone Safari device checks before release sign-off.

## Local activation and release status

Both migrations have been applied to local `ngo_db`. The previously requested local permission grants remain in place. A macOS LaunchAgent named `com.ngo.laravel-scheduler` runs `schedule:work` at login; its output is in `storage/logs/scheduler.log` and `scheduler-error.log`.

Changes are committed locally. No push, published release or production deployment has been performed. Host configuration, real email delivery, physical-device checks and live GitHub Actions execution remain deployment verification, not completed local checks. This report covers temporary sessions, connected authentication/recovery, and deployment boundaries; it is not a full financial-system security certification.
