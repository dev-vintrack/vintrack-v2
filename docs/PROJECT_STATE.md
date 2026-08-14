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
SPRINT-06 LOCAL IMPLEMENTATION / OWNER REVIEW

## Phase 0 Documentation
APPROVED — Version 1.0

## Last Completed Sprint
SPRINT-05 — Administrative Portal — Review & Validation

## Last Sprint Status
APPROVED WITH OBSERVATIONS

## Current Sprint
SPRINT-06 — Vehicle Consultation Histories + Server-side DataTables

## Current Sprint Status
READY FOR OWNER REVIEW

## Next Sprint
SPRINT-07 — Notifications, Outbox Delivery & Automation

## Next Sprint Status
NOT AUTHORIZED

## Canonical Provider Service Field
`consultations.provider_service_id`

## Approval
SPRINT-05 fue aprobado por el Project Owner como `APPROVED WITH OBSERVATIONS` el 2026-08-13. DEC-041 registra el cierre y DEC-040 conserva la regla del motivo obligatorio de rechazo. Las observaciones no reabren ni modifican la implementación. Producción permanece no autorizada y SPRINT-06 requiere autorización explícita.

## Production Status
NOT AUTHORIZED

## Canonical Roadmap Decision
DEC-036

## Mandatory Pre-Production Follow-up

1. `OBS-02-01`: execute migrations and relevant behavior on real MariaDB 10.6.27.
2. `OBS-02-02`: CLOSED LOCALLY for double submit, double VIN assignment, validate vs reject, validate vs auto-close and reject vs auto-close. Reopen only if later MariaDB/production validation fails. General deadlock/retry behavior remains subject to later production-readiness validation where applicable.
3. `OBS-02-03`: close end-to-end request → consultation idempotency before external provider/API reinvocation can occur on a late retry.
4. `OBS-02-04`: preserve `VIN_NOT_AVAILABLE` for plate consultations without a contractually verified VIN path; no heuristics.
5. `OBS-02-05`: retain the historical MySQL migration-chain `adapter_code` dependency as technical debt; do not rewrite historical migrations in this closure.
6. `OBS-02-06`: validate indexes and execution plans with representative volume.

These gates are additional to backup, rollback, compatibility validation and explicit production authorization.

## SPRINT-03 Production Gates

1. `OBS-03-02`: evaluate the accepted residual malware-scanning risk before production; no scanner is currently integrated.
2. `OBS-03-03`: verify real fileinfo and GD availability, private path, read/write permissions, absence of direct HTTP access, and streaming/download behavior on Neubox.
3. `OBS-03-04`: validate relevant behavior on real MariaDB 10.6.27.

## SPRINT-04 Closure Observations

1. `OBS-04-01`: migration reversible de datos del menú aceptada; debe incorporarse al futuro procedimiento de deployment.
2. `OBS-04-02`: doble submit real aceptado; queda cerrada esa parte de `OBS-02-02`. Un código de dominio más específico para el segundo submit es mejora futura no bloqueante.
3. `OBS-04-03`: permanecen todos los Production Gates acumulados no cerrados.
4. `OBS-04-04`: el flujo administrativo/follow-up posterior debe considerar explícitamente placa sin VIN y conciliación, sin ampliar SPRINT-04.

## SPRINT-05 Closure Observations

1. `OBS-05-01`: migration reversible de datos del menú administrativo aceptada; debe incorporarse al futuro procedimiento de deployment.
2. `OBS-05-02`: no existe capability administrativa aprobada para VIN excepcional; no se expuso UI. Doble asignación VIN cerrada localmente; una decisión funcional futura no reabre SPRINT-05.
3. `OBS-05-03`: timeline con nombres técnicos aceptado; humanización futura es mejora UX no bloqueante.
4. `OBS-05-04`: permanecen MariaDB 10.6.27, idempotencia request → consulta, índices/EXPLAIN con volumen, deuda histórica de migrations, malware scanning, verificaciones Neubox, backup/rollback y autorización productiva.

Carreras cerradas localmente: doble submit, doble VIN, validate vs reject, validate vs auto-close y reject vs auto-close.

## Approved Implementation Roadmap

1. SPRINT-03 — Evidence & Secure File Management.
2. SPRINT-04 — Client Portal — Notification Process.
3. SPRINT-05 — Administrative Portal — Review & Validation.
4. SPRINT-06 — Vehicle Consultation Histories + Server-side DataTables.
5. SPRINT-07 — Notifications, Outbox Delivery & Automation.
6. SPRINT-08 — Hardening & Production Readiness.
7. PRODUCTION GATE — explicit Project Owner authorization required.

DEC-036 es el roadmap canónico. SPRINT-04 está cerrado mediante DEC-039. SPRINT-05 está cerrado como APPROVED WITH OBSERVATIONS mediante DEC-041. SPRINT-06 fue implementado localmente y queda READY FOR OWNER REVIEW. SPRINT-07 y producción permanecen no autorizados.

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
16. Production is not authorized; SPRINT-06 is pending explicit Owner authorization.
17. DEC-036 establishes SPRINT-03 through SPRINT-08 as the canonical approved roadmap; SPRINT-05 is closed with observations.
18. DEC-038 prohíbe `recovered_at` futuro y fija `America/Mexico_City` para su comparación server-side.
19. DEC-039 registra el cierre de SPRINT-04 como APPROVED WITH OBSERVATIONS.
20. DEC-040 exige motivo textual obligatorio, normalizado, persistente, histórico y visible al Cliente para todo rechazo administrativo.
21. DEC-041 registra el cierre de SPRINT-05 como APPROVED WITH OBSERVATIONS.
