# Collection approval repair — 18 September 2026

## Scope and behavior

The requested duplicate-approval fix is implemented in both saving and loan collection approval handlers. Already approved collections are now successful no-ops: no extra credit, no counter changes, and no replacement of original approval metadata. Existing responses and first-approval accounting are retained.

Pending rows are selected and locked inside the same transaction that updates approval metadata and account counters. Selection is ordered by ID; only selected row IDs are updated. Transactions retry deadlocks up to three attempts. No schema migration is required.

This prevents new repeat-approval errors; it does not recalculate historical balances. No existing database records or frontend layouts were changed.

## Regression evidence

Before repair, the expanded suite reproduced eight replay/metadata/overlap failures plus the two existing permission failures. After both authorized repairs, the native collection suite passes **29 tests / 583 assertions**, covering:

- Savings/loan pending creation, collector denial, single approval, automatic approval, balances, history, regular category/center queues, prior-day category queues and audit totals.
- Repeated approval, overlapping batches, duplicate IDs, and replay of automatically approved records.
- Preservation of original approver/timestamp and member/cash/installment/principal/interest counters.
- Unknown and soft-deleted IDs remain ignored, preserving existing behavior.
- Failure during the second collection's cash update rolls back the entire batch, including all earlier balance changes and approval metadata.
- Ten HTTP permission cases: each product's regular/pending approvers are accepted; the other product's grants and accounts with no approval grant are denied without changing metadata or balances.

Run from the backend directory (the config-cache file must not exist):

```sh
rtk proxy env APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DATABASE_URL= MAIL_MAILER=array CACHE_DRIVER=array SESSION_DRIVER=array QUEUE_CONNECTION=sync LOG_CHANNEL=stderr APP_CONFIG_CACHE=/private/tmp/ngo-release-verification.7QREOg/nonexistent-config.php php vendor/bin/phpunit --do-not-cache-result tests/Feature/Collections/CollectionApprovalTest.php
```

The test refuses to migrate unless running in testing against SQLite `:memory:`, with no database URL override and array mail/cache/session drivers.

Full backend suite: **65 tests / 766 assertions; 64 pass, 1 existing failure**. The unchanged failure is `PermissionParentCategoryResolverTest::test_it_resolves_known_child_groups_to_parent_category`, expecting `fields` but receiving `field`.

Modified controllers and the new test pass PHP syntax checks; the test passes Pint; diff whitespace checks pass. Independent code review found no introduced blocking issue. The debugging, test-driven-development and verification-before-completion skills guided reproduction before repair and fresh verification afterward.

## Real MySQL concurrency verification

A separate MySQL 9.6 instance ran under `/private/tmp/ngo-approval-race.d3EMdF`, using a private Unix socket with networking disabled. The harness checked its datadir/socket/`skip_networking` before creating synthetic fixtures. It did not load the project's `.env` or contact the existing database server.

Two PHP workers invoked the actual controllers concurrently. A barrier held the first transaction after selection; `performance_schema.data_lock_waits` confirmed the second was waiting before release. Both responses returned 200 / success. All four scenarios passed, including an independent rerun:

| Scenario | Unique collections | Member savings balance | Cash balance | Installments |
| --- | --- | --- | --- | --- |
| Savings: identical requests | 1 | 500 | 500 | 1 |
| Savings: overlapping batches | 3 | 1,500 | 1,500 | 3 |
| Loan: identical requests | 1 | 100 | 210 | 1 |
| Loan: overlapping batches | 3 | 300 | 630 | 3 |

Loan principal/interest and first-approval metadata also matched. These are controller/accounting concurrency checks, not HTTP middleware checks; the PHPUnit HTTP suite covers authorization separately. Deadlock retries were not deliberately forced.

Temporary evidence remains in `verify.php`, `results.json` and logs under the directory above. The owned server is stopped and both synthetic datadirs were removed (approximately 200 MB); the small evidence files remain. No permanent concurrency CI job was added.

## Issue #2: backend permission defect corrected

Following explicit approval, `app/Http/Controllers/Collections/SavingCollectionController.php:38` now checks `regular_saving_collection_approval|pending_saving_collection_approval`. Only the incorrect regular-loan grant was replaced. No existing role grants or records were changed.

The new permission matrix reproduced both failures before the one-line correction. Afterward, savings-only approval succeeds and loan-only approval is denied. Pending savings approval is retained, loan approval rules are unchanged, and denied requests preserve all counters and approval metadata. Regular/pending date and ownership policy have not been redefined.

The original sibling frontend release tests also pass unchanged: **9 collection tests / 314 assertions**, including both original permission reproductions and duplicate-approval cases. Independent code review approved the scoped permission correction.

This is not full deployment sign-off: the previously reported password-recovery defect, existing baseline test mismatch, and remaining inbox/physical-phone checks are unresolved.
