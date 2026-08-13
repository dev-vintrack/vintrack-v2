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
SPRINT-03 IMPLEMENTATION COMPLETE / OWNER REVIEW

## Phase 0 Documentation
APPROVED — Version 1.0

## Last Completed Sprint
SPRINT-02 — Core Domain, Persistence & Consultation Admission

## Last Sprint Status
APPROVED WITH OBSERVATIONS

## Current Sprint
SPRINT-03 — Evidence & Secure File Management

## Current Sprint Status
READY FOR OWNER REVIEW

## Next Sprint
SPRINT-04 — Client Portal — Notification Process

## Next Sprint Status
PLANNED BY DEC-036 — NOT AUTHORIZED — EXPLICIT PROJECT OWNER AUTHORIZATION REQUIRED

## Canonical Provider Service Field
`consultations.provider_service_id`

## Approval
SPRINT-02 — Core Domain, Persistence & Consultation Admission was approved by the Project Owner as `APPROVED WITH OBSERVATIONS` on 2026-08-13. The observations are mandatory follow-up before production but do not reopen SPRINT-02. Production deployment and every following Sprint remain unauthorized.

## Mandatory Pre-Production Follow-up

1. `OBS-02-01`: execute migrations and relevant behavior on real MariaDB 10.6.27.
2. `OBS-02-02`: complete real concurrency tests for double submit, double VIN assignment, validation versus auto-close, and deadlock/retry.
3. `OBS-02-03`: close end-to-end request → consultation idempotency before external provider/API reinvocation can occur on a late retry.
4. `OBS-02-04`: preserve `VIN_NOT_AVAILABLE` for plate consultations without a contractually verified VIN path; no heuristics.
5. `OBS-02-05`: retain the historical MySQL migration-chain `adapter_code` dependency as technical debt; do not rewrite historical migrations in this closure.
6. `OBS-02-06`: validate indexes and execution plans with representative volume.

These gates are additional to backup, rollback, compatibility validation and explicit production authorization.

## Approved Implementation Roadmap

1. SPRINT-03 — Evidence & Secure File Management.
2. SPRINT-04 — Client Portal — Notification Process.
3. SPRINT-05 — Administrative Portal — Review & Validation.
4. SPRINT-06 — Vehicle Consultation Histories + Server-side DataTables.
5. SPRINT-07 — Notifications, Outbox Delivery & Automation.
6. SPRINT-08 — Hardening & Production Readiness.
7. PRODUCTION GATE — explicit Project Owner authorization required.

DEC-036 approves the sequence. SPRINT-03 received separate explicit Project Owner authorization on 2026-08-13 and is now ready for review. Later Sprints and production remain unauthorized.

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
17. DEC-036 establishes SPRINT-03 through SPRINT-08 as the approved roadmap; SPRINT-03 was explicitly authorized and is ready for Owner review; SPRINT-04 remains unauthorized.
