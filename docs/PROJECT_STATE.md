# VINTrack — Project State

**Status:** APPROVED  
**Version:** 1.1
**Approval date:** 2026-08-17

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

## Canonical Database Roles

- `vintrack_system_db`: legacy PHP only, 11 tables, tied to `/public_html`; it is outside Laravel and the SQL bundle and remains intact for legacy rollback.
- `vintrack_dev`: Laravel staging, tied to `/public_html_dev`; its historical canonical baseline was 31 tables and the Owner has completed the approved apply-once reconciliation to the validated 44-table target. It is the source for a future full clone to `vintrack_app`; do not rerun the SQL bundle.
- `vintrack_app`: future Laravel production database, not yet created; it will be a full clone of validated `vintrack_dev`, never an empty bootstrap or legacy clone.

## Baseline Already Implemented
`consultations.provider_service_id` exists locally and in production. Do not recreate.

## Current Phase
GOLDEN STAGING CANDIDATE — READY FOR OWNER REVIEW

## Current Class A Changes
- CR-003 — Restore Report Access from History Tables: IMPLEMENTED LOCALLY — READY FOR OWNER REVIEW. It restores the existing `/reports/{consultation_id}` access from Client and Administrative history rows without changing report generation, providers, workflows, schema, migrations, or production.
- CR-004 — Provider Result Assessment for `placas_service` and `nmvtis_plus`: IMPLEMENTED LOCALLY — OWNER LOCAL VALIDATION ACCEPTED — RELEASE COMMIT `c23d662` CREATED — WINDOWS-COMPATIBLE STAGING ARTIFACT REGENERATED — OWNER MANUAL EXTRACTION / RELEASE REVIEW PENDING. DEC-050 establishes explicit, service-code-based qualification; only current documented robbery/theft or fraud signals may create notification cases and their case-driven intents. Contract fixtures, local visual validation and the 130-test/605-assertion regression passed without billable provider calls. The local candidate artifact is identified in `docs/production/deployment/CR-004-STAGING-ARTIFACT-MANIFEST.md`; no upload, activation or production action is authorized or performed.

## Phase 0 Documentation
APPROVED — Version 1.0

## Last Completed Sprint
SPRINT-08 — Hardening & Production Readiness

## Last Sprint Status
APPROVED WITH OBSERVATIONS

## Current Sprint
None

## Current Sprint Status
NONE — SPRINT-08 CLOSED

## Local Production Hardening Gate
APPROVED WITH OBSERVATIONS — DEC-047

## PG-05
IMPLEMENTED LOCALLY — PROVIDER SELECTION AND PRODUCTION VERIFICATION REQUIRED

## PG-18
CLOSED LOCALLY

## Next Sprint
NONE / NOT DETERMINED

## Next Sprint Status
PENDING OWNER DETERMINATION

## Canonical Provider Service Field
`consultations.provider_service_id`

## Approval
SPRINT-08 fue cerrado formalmente mediante DEC-044 como `APPROVED WITH OBSERVATIONS`. El Local Production Hardening Gate PG-05 + PG-18 fue cerrado mediante DEC-047 como `APPROVED WITH OBSERVATIONS`. PG-01 fue cerrado formalmente mediante DEC-048 como `CLOSED — VERIFIED FOR SCHEMA COMPATIBILITY`. PG-05 permanece pendiente de provider/verificación productiva y PG-18 está CLOSED LOCALLY. Los demás Gates del entorno objetivo conservan su estado. Production Readiness es `READY WITH CONDITIONS`; producción no está autorizada y deployment no fue ejecutado.

## Production Status
NOT AUTHORIZED

## Production Readiness
READY WITH CONDITIONS

## Deployment
NOT EXECUTED

## Next Step
Owner manual Windows File Explorer extraction and review of the regenerated CR-004 staging artifact and its manifest/hash. The runtime source commit is `c23d662803c89f47477bd77803e2a90587746693`; the code, PHP 8.3.32, 44-table `vintrack_dev` target, authentication and authorized Client/Admin validation passed. Do not upload or activate the artifact, create/clone `vintrack_app`, modify production, or perform additional SQL without new Owner authorization.

## Canonical Roadmap Decision
DEC-036

## Mandatory Pre-Production Follow-up

1. `OBS-02-01`: CLOSED for schema compatibility by the MariaDB 10.6.27 portable rehearsal and DEC-048; real-data/Linux operational behavior remains a deployment risk.
2. `OBS-02-02`: CLOSED LOCALLY for double submit, double VIN assignment, validate vs reject, validate vs auto-close and reject vs auto-close. Reopen only if later MariaDB/production validation fails. General deadlock/retry behavior remains subject to later production-readiness validation where applicable.
3. `OBS-02-03`: CLOSED LOCALLY by SPRINT-08/DEC-044 for end-to-end request → consultation idempotency; no exactly-once externo is asserted.
4. `OBS-02-04`: preserve `VIN_NOT_AVAILABLE` for plate consultations without a contractually verified VIN path; no heuristics.
5. `OBS-02-05`: CLOSED LOCALLY by SPRINT-08/DEC-044 through versioned bootstrap plus forward-only/backup-restore/forward-fix strategy; historical migrations were not rewritten.
6. `OBS-02-06`: CLOSED LOCALLY by SPRINT-08/DEC-044 with representative 100k dataset and EXPLAIN Before/After.

These gates are additional to backup, rollback, compatibility validation and explicit production authorization.

## SPRINT-03 Production Gates

1. `OBS-03-02`: evaluate the accepted residual malware-scanning risk before production; no scanner is currently integrated.
2. `OBS-03-03`: verify real fileinfo and GD availability, private path, read/write permissions, absence of direct HTTP access, and streaming/download behavior on Neubox.
3. `OBS-03-04`: CLOSED for schema compatibility by DEC-048; storage/behavior on Neubox remains governed by the other external Gates.

## SPRINT-04 Closure Observations

1. `OBS-04-01`: migration reversible de datos del menú aceptada; debe incorporarse al futuro procedimiento de deployment.
2. `OBS-04-02`: doble submit real aceptado; queda cerrada esa parte de `OBS-02-02`. Un código de dominio más específico para el segundo submit es mejora futura no bloqueante.
3. `OBS-04-03`: permanecen todos los Production Gates acumulados no cerrados.
4. `OBS-04-04`: el flujo administrativo/follow-up posterior debe considerar explícitamente placa sin VIN y conciliación, sin ampliar SPRINT-04.

## SPRINT-05 Closure Observations

1. `OBS-05-01`: migration reversible de datos del menú administrativo aceptada; debe incorporarse al futuro procedimiento de deployment.
2. `OBS-05-02`: no existe capability administrativa aprobada para VIN excepcional; no se expuso UI. Doble asignación VIN cerrada localmente; una decisión funcional futura no reabre SPRINT-05.
3. `OBS-05-03`: timeline con nombres técnicos aceptado; humanización futura es mejora UX no bloqueante.
4. `OBS-05-04`: historical observation. MariaDB schema compatibility, idempotencia, EXPLAIN, migration strategy y backup/restore quedaron cerrados posteriormente; malware provider, verificaciones Neubox y autorización productiva permanecen pendientes.

Carreras cerradas localmente: doble submit, doble VIN, validate vs reject, validate vs auto-close y reject vs auto-close.

## SPRINT-06 Closure Observations

1. `OBS-06-01`: CLOSED LOCALLY by SPRINT-08/DEC-044 con dataset 100k, EXPLAIN Before/After e índices respaldados por evidencia.
2. `OBS-06-02`: normalización actual de placas aceptada; no inferir identidad ausente; formatos futuros requieren evidencia.
3. `OBS-06-03`: DEC-046 implementada localmente. DataTables 1.13.6 y dependencias críticas están self-hosted; inventario/regresión/smoke offline completos. CSP productiva sigue pendiente.
4. `OBS-06-04`: permanecen todos los Production Gates acumulados no cerrados.

## SPRINT-07 Closure Observations

1. `OBS-07-01`: SMTP se acepta como at-least-once; no afirmar exactly-once externo sin garantía verificable del proveedor.
2. `OBS-07-02`: CLOSED LOCALLY by SPRINT-08; destinatario inexistente/email inválido queda non-retryable/skipped y Portal permanece independiente.
3. `OBS-07-03`: PHP CLI/cPanel/Artisan, working directory, Cron, logs/cache, URL, SMTP/TLS/remitente, SPF/DKIM/DMARC, límites y rebotes permanecen Production Gates.
4. `OBS-07-04`: deuda histórica del rollback global en `2026_08_05_000002_drop_label_icon_from_menu_permissions_tables` preservada; ciclos reversibles recientes aceptados.

## Accumulated Production Gates

Closed within their approved evidence scope: PG-01 MariaDB schema compatibility, PG-02 idempotencia, PG-03 EXPLAIN/performance, PG-04 migration strategy, PG-07 backup/restore y PG-21 secrets/log sanitization.

Decided, implementation required before production:

- PG-05 malware scanning: IMPLEMENTED LOCALLY — PROVIDER SELECTION AND PRODUCTION VERIFICATION REQUIRED. No está CLOSED y no existe provider seleccionado.
- PG-18 frontend assets: CLOSED LOCALLY; assets críticos self-hosted, DataTables 1.13.6 preservado y smoke offline verde. CSP productiva no activada.

Closed: PG-01 MariaDB schema compatibility mediante DEC-048.

Partially verified: PG-06 — global/effective web PHP 8.3.32, required extensions, visible limits and basic HTTPS responses verified; final Laravel production root/private-storage writability and non-exposed runtime values remain unverified.

Blocked external: PG-08–PG-17 PHP CLI/cPanel/Cron/logs/cache/APP_URL/SMTP/DNS/límites/rebotes; PG-20 configuración productiva/APP_DEBUG.

Deferred by Owner: PG-19 autorización productiva explícita.

## Approved Implementation Roadmap

1. SPRINT-03 — Evidence & Secure File Management.
2. SPRINT-04 — Client Portal — Notification Process.
3. SPRINT-05 — Administrative Portal — Review & Validation.
4. SPRINT-06 — Vehicle Consultation Histories + Server-side DataTables.
5. SPRINT-07 — Notifications, Outbox Delivery & Automation.
6. SPRINT-08 — Hardening & Production Readiness.
7. PRODUCTION GATE — explicit Project Owner authorization required.

DEC-036 es el roadmap canónico. SPRINT-07 está cerrado mediante DEC-043 y SPRINT-08 mediante DEC-044. No existe Sprint siguiente determinado/autorizado. Producción permanece no autorizada.

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
16. Production is not authorized; SPRINT-08 is closed as APPROVED WITH OBSERVATIONS.
17. DEC-036 establishes SPRINT-03 through SPRINT-08 as the canonical approved roadmap; SPRINT-05 is closed with observations.
18. DEC-038 prohíbe `recovered_at` futuro y fija `America/Mexico_City` para su comparación server-side.
19. DEC-039 registra el cierre de SPRINT-04 como APPROVED WITH OBSERVATIONS.
20. DEC-040 exige motivo textual obligatorio, normalizado, persistente, histórico y visible al Cliente para todo rechazo administrativo.
21. DEC-041 registra el cierre de SPRINT-05 como APPROVED WITH OBSERVATIONS.
22. DEC-042 registra el cierre de SPRINT-06 como APPROVED WITH OBSERVATIONS.
23. SPRINT-07 implementa localmente delivery/outbox/portal/commands; cPanel Cron y SMTP productivo no están configurados.
24. DEC-044 registra SPRINT-08 como APPROVED WITH OBSERVATIONS y Production Readiness como READY WITH CONDITIONS; no autoriza deployment.
25. DEC-045 aprueba la arquitectura quarantine-first/fail-closed de PG-05; implementación y tests locales con fake completados, pero provider aprobado e integración productiva siguen pendientes.
26. DEC-046 aprueba self-hosting de assets frontend críticos; DataTables 1.13.6 no se actualizó y la migración/inventario/smoke quedaron completos localmente; CSP productiva sigue pendiente.
27. DEC-047 cierra el Local Production Hardening Gate PG-05 + PG-18 como APPROVED WITH OBSERVATIONS; PG-05 no queda CLOSED y PG-18 queda CLOSED LOCALLY.
28. DEC-048 cierra PG-01 como CLOSED — VERIFIED FOR SCHEMA COMPATIBILITY; los riesgos de duración, locks, espacio, tráfico real y Linux/Neubox permanecen riesgos operativos de deployment.
29. DEC-049 fija la promoción `vintrack_dev` 31→44 → validar staging → clonar íntegramente a `vintrack_app`; `vintrack_system_db` permanece legacy de 11 tablas e intocable.

## SPRINT-07 Approved Result

- Outbox processor, retry/backoff/dedup y canales independientes: implementados localmente.
- Portal notifications propias, read/unread, contador, listado y enlace autorizado: implementados localmente.
- Email de expedientes mediante Laravel Mail: implementado con transporte de pruebas; SMTP productivo no configurado.
- Commands discretos para outbox, deadlines y auto-close: implementados localmente.
- Scheduler no es requisito productivo; Cron real no configurado.
- SPRINT-08 cerrado mediante DEC-044 como APPROVED WITH OBSERVATIONS. No se inició SPRINT-09. Production Environment Verification completó Phase 1 y una única ejecución read-only Class B #1; no hubo deployment, migrations ni otras acciones Class B.
