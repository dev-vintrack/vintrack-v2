# CR-003 — Restore Report Access from History Tables — Result

**Status:** IMPLEMENTED LOCALLY — READY FOR OWNER REVIEW
**Date:** 2026-08-15
**Authorization:** Project Owner Class A Change Request

## Scope completed

- Restored access to the pre-existing HTML report endpoint from both consultation-history DataTables.
- Reused the existing final `Acciones` column. No dedicated report column was added.
- A successful consultation exposes `📄 Ver`, linking to the existing `GET /reports/{consultation_id}` route.
- Notification-case actions remain available in the same extensible action collection.
- A failed/non-reportable consultation exposes no report link (`—` when there are no other actions).

## Authorization and hardening

- The existing report controller was inspected and already scopes client access to the consultation owner and administrative access to administrators.
- The controller now returns the same controlled 404 response for a failed/non-reportable consultation as it does for a nonexistent or unauthorized report.
- UI visibility is advisory only; the server-side authorization check remains authoritative.
- No report-generation implementation, provider call, wallet operation, notification workflow, schema, migration, or database data was modified.

## Evidence

Focused local regression command:

```text
php artisan test --compact tests/Feature/ConsultationHistories/ConsultationHistoryTest.php tests/Feature/Customer/CustomerAccountPrivacyTest.php tests/Feature/Consultations/VinDataConsultationTest.php
```

Result: **17 passed, 90 assertions, 0 failures**.

Assertions cover client report visibility, absence of a failed-report action, retention of the notification-case action, administrator visibility for another user's report, direct failed/nonexistent report 404 responses, existing cross-client report denial, and existing administrator report access.

No screenshot was generated locally because this Class A validation used deterministic feature tests and did not perform a staging or production action. A later Owner-authorized staging validation may capture the rendered `Acciones` controls.

## Remediation 1 — DataTables initial renderer

Owner staging evidence on 2026-08-16 showed `—` in every `Acciones` cell despite reportable consultations being present. Read-only comparison of the deployed artifact and the staging database established that the `actions` payload and the report-availability condition were present; the fault was confined to the browser renderer.

The two views had initialized DataTables with the legacy singular `action` field and then attempted to mutate DataTables internals after initialization. DataTables had already captured the initial renderer, so the later mutation did not take effect. The final column now uses `actions` and its renderer in the initial `columns` configuration. The post-initialization mutation was removed.

Validation after the correction:

```text
php artisan test --filter=ConsultationHistoryTest
php artisan test
vendor/bin/pint --dirty
git diff --check
```

Result: **117 passed, 552 assertions, 0 failures**; Pint and diff whitespace checks passed. The browser-side renderer now deterministically maps a report action to `📄 Ver`, while an empty action collection maps to `—`. No hosting, staging, or production action was performed for this remediation.

## Affected files

- `app/Application/ConsultationHistories/ConsultationHistoryRow.php`
- `app/Presentation/Http/Controllers/Web/ReportController.php`
- `resources/views/customer/consultations.blade.php`
- `resources/views/admin/consultations/index.blade.php`
- `tests/Feature/ConsultationHistories/ConsultationHistoryTest.php`
- `docs/CHANGE_REQUESTS.md`
- `docs/PROJECT_STATE.md`
- `docs/change-results/CR-003-RESTORE-REPORT-ACCESS-FROM-HISTORY-TABLES-RESULT.md`

## Decision-log impact

No Decision Log entry is required: this restores an existing endpoint's access in the approved History `Acciones` column and introduces no architecture or business-rule change.

## Production impact

None. Production Authorization remains `NOT AUTHORIZED`; deployment remains `NOT EXECUTED`.

READY FOR OWNER REVIEW
