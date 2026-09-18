# Temporary sessions and authentication release review — 19 September 2026

## Changes reviewed

- Permission-gated, 15-minute, view-only staff visits. The backend checks the original administrator login, current permission, both accounts and expiry on every temporary request. The original login is preserved. The scheduler finalizes expired visits and records missing-token revocations without waiting for another browser request.
- Browser return controls recover from stalled requests after 10 seconds. Refresh, expiry, failed bootstrap, cache separation and original-login preservation have regression coverage.
- Password recovery now requires account/purpose-bound OTP verification followed by a hashed, account-bound, single-use reset proof valid for five minutes. Resending invalidates previous codes and proofs. User-row locks serialize proof issuance, consumption and login-token issuance. Reset revokes all account tokens and affected temporary sessions. Existing verification codes cannot authorize password recovery.
- Public HTTP Artisan utilities and the development permission self-grant route are removed. Administrative maintenance uses the CLI.
- Production Composer autoload includes API response helpers. Client factory namespaces match case-sensitive filesystems.

## Verified locally

| Check | Result |
| --- | --- |
| Complete backend PHPUnit suite | 89 tests, 913 assertions passed |
| Adjacent frontend's isolated release HTTP suite | 21 tests, 370 assertions passed |
| Complete frontend Vitest suite | 994 tests across 46 files passed |
| Frontend production build | Passed |
| Changed JavaScript ESLint | 16 files passed |
| Composer manifest and local production platform requirements | Passed |
| Isolated no-dev optimized autoloader with strict PSR checks | Passed; both response helper functions loaded |
| Workflow YAML parsing | Both workflows passed |

All automated HTTP tests use synthetic accounts and SQLite memory databases. Mail generation is tested with fake mail; these tests do not prove live SMTP delivery. The simulated login/reset race verifies credential revalidation, but SQLite does not reproduce MySQL row-lock contention. Existing build warnings include Sass deprecations and a large main JavaScript chunk; they do not fail the build.

An initial full frontend run timed out in a form test under concurrent test load. The isolated form suite and subsequent full suite passed without weakening timeouts or assertions.

## Application setup

The existing deployment workflows and hook have been restored at the user's request. Deployment safeguards are outside this change. The application requirements below still apply.

1. Use matching frontend and backend versions. OTP verification requires `user_id` and `purpose`; password changes require `reset_token`. Existing recovery attempts should start again.
2. Apply both `2026_09_19_000000_create_user_impersonation_sessions` and `2026_09_19_010000_add_purpose_to_users_verifies` with `php artisan migrate`. Regenerate Composer's autoloader so runtime response helpers are loaded.
3. Add one scheduler entry on the production host, replacing the paths with the real application and PHP paths:

   ```cron
   * * * * * cd /path/to/backend && /usr/bin/php artisan schedule:run >> storage/logs/scheduler.log 2>&1
   ```

   Confirm `php artisan schedule:list` includes `impersonation:expire` every minute and observe one successful run. Configure host log rotation. The Mac login service is local development only and does not run production jobs.
4. Grant `staff_impersonate` only to intended administrators through Staff Permissions or Role Permissions; they also need `staff_list_view` for the Staffs entry point. The migration does not grant it automatically. Refresh the app after assigning it.
5. Verify real mail delivery with an approved test account, then exercise login, recovery, a view-only visit, refresh, early return, expiry, and permission revocation on staging/production. Complete Android Chrome and iPhone Safari device checks before release sign-off.

## Local activation and release status

Both migrations have been applied to local `ngo_db`. The previously requested local permission grants remain in place. A macOS LaunchAgent named `com.ngo.laravel-scheduler` runs `schedule:work` at login; its output is in `storage/logs/scheduler.log` and `scheduler-error.log`.

Changes are committed locally. No push, published release or production deployment has been performed. Host configuration, real email delivery, physical-device checks and live GitHub Actions execution remain deployment verification, not completed local checks. This report covers temporary sessions, connected authentication/recovery, and application API boundaries; it is not a full financial-system security certification.
