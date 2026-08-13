# VINTrack — Project State

**Status:** APPROVED  
**Version:** 1.0  
**Approval date:** 2026-08-13

## Technology
- Laravel 12
- PHP 8.3

## Local
- Laragon 8.6.1
- MySQL 8.4.3

## Production
- Neubox shared hosting / cPanel
- PHP 8.3
- MariaDB 10.6.27
- InnoDB / utf8mb4
- SFTP / File Manager / Cron
- No SSH / PHP CLI / Git Deployment
- Laravel Scheduler cannot be relied upon

## Baseline Already Implemented
`consultations.provider_service_id` exists locally and in production. Do not recreate.

## Current Phase
SPRINT-04 IMPLEMENTATION COMPLETE / OWNER REVIEW

## Phase 0 Documentation
APPROVED — Version 1.0

## Last Completed Sprint
SPRINT-03 — Evidence & Secure File Management

## Last Sprint Status
APPROVED WITH OBSERVATIONS

## Current Sprint
SPRINT-04 — Client Portal — Notification Process

## Current Sprint Status
READY FOR OWNER REVIEW

## Next Sprint
SPRINT-05 — Administrative Portal — Review & Validation

## Next Sprint Status
NOT AUTHORIZED — EXPLICIT PROJECT OWNER AUTHORIZATION REQUIRED

## Canonical Provider Service Field
`consultations.provider_service_id`

## Approval
SPRINT-04 fue autorizado expresamente por el Project Owner y se implementó localmente. Permanece `READY FOR OWNER REVIEW`; esta finalización no constituye aprobación del Sprint ni autoriza SPRINT-05 o producción.

## Production Status
NOT AUTHORIZED

## Canonical Roadmap Decision
DEC-036

## Mandatory Pre-Production Follow-up

1. `OBS-02-01`: execute migrations and relevant behavior on real MariaDB 10.6.27.
2. `OBS-02-02`: complete real concurrency tests for double submit, double VIN assignment, validation versus auto-close, and deadlock/retry.
3. `OBS-02-03`: close end-to-end request → consultation idempotency before external provider/API reinvocation can occur on a late retry.
4. `OBS-02-04`: preserve `VIN_NOT_AVAILABLE` for plate consultations without a contractually verified VIN path; no heuristics.
5. `OBS-02-05`: retain the historical MySQL migration-chain `adapter_code` dependency as technical debt; do not rewrite historical migrations in this closure.
6. `OBS-02-06`: validate indexes and execution plans with representative volume.

These gates are additional to backup, rollback, compatibility validation and explicit production authorization.

## SPRINT-03 Production Gates

1. `OBS-03-02`: evaluate the accepted residual malware-scanning risk before production; no scanner is currently integrated.
2. `OBS-03-03`: verify real fileinfo and GD availability, private path, read/write permissions, absence of direct HTTP access, and streaming/download behavior on Neubox.
3. `OBS-03-04`: validate relevant behavior on real MariaDB 10.6.27.

## Approved Implementation Roadmap

1. SPRINT-03 — Evidence & Secure File Management.
2. SPRINT-04 — Client Portal — Notification Process.
3. SPRINT-05 — Administrative Portal — Review & Validation.
4. SPRINT-06 — Vehicle Consultation Histories + Server-side DataTables.
5. SPRINT-07 — Notifications, Outbox Delivery & Automation.
6. SPRINT-08 — Hardening & Production Readiness.
7. PRODUCTION GATE — explicit Project Owner authorization required.

DEC-036 es el roadmap canónico. SPRINT-03 permanece cerrado como APPROVED WITH OBSERVATIONS. SPRINT-04 está listo para revisión del Owner; SPRINT-05 y producción permanecen no autorizados.

## Critical Rules
1. Keep notification process separate from consultation history.
2. Do not use `vehicles` as notification-expedient master.
3. Maximum 3 pending.
4. Block the fourth consultation before credit/API consumption.
5. Deadline = consultation + 3 calendar days at 23:59:59.
6. Deadline does not reset after rejection.
7. Open expedient maximum 30 days.
8. Rejection preserves expedient number.
9. 90-day reuse/reference window is configurable.
10. Production automation uses cPanel Cron.
11. Official notification-module timezone is `America/Mexico_City` with local `DATETIME` semantics.
12. Plate-originated draft without recoverable VIN permits one audited VIN assignment before submission; then VIN is immutable.
13. Provisional retention is 5 years for cases/evidence/audit and 2 years for portal notifications; no purge is authorized.
14. Primary future Cron executable is `/usr/local/bin/php`, subject to predeployment verification.
15. SPRINT-02 is closed as APPROVED WITH OBSERVATIONS; its six observations are mandatory before production.
16. Production and the next Sprint are not authorized.
17. DEC-036 establishes SPRINT-03 through SPRINT-08 as the canonical approved roadmap; SPRINT-04 is ready for Owner review and SPRINT-05 remains unauthorized.
18. DEC-038 prohíbe `recovered_at` futuro y fija `America/Mexico_City` para su comparación server-side.
