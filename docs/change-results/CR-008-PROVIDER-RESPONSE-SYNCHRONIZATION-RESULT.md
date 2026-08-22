# CR-008 — Provider Response Synchronization — Result

**Status:** IMPLEMENTED LOCALLY — OWNER LOCAL VISUAL VALIDATION ACCEPTED — STAGING NOT AUTHORIZED

`placas_service` now uses separate configurable connection, initial-request, per-poll, polling-window and execution-budget limits. A `204` during polling is retried until valid JSON or the configured deadline. `nmvtis_plus` uses independent configurable connection, request and execution-budget limits. Timeout, connection and non-JSON responses return the approved user-safe synchronization message.

No schema, migration, data, wallet, qualification, notification-case, Cron, SMTP, staging or production change was performed. Focused regression: 13 tests / 44 assertions passed; full local regression: 139 tests / 637 assertions passed; Pint and `git diff --check` passed.

On 2026-08-19 the Owner confirmed local visual validation as correct. Staging remains unauthorized.

READY FOR OWNER REVIEW
