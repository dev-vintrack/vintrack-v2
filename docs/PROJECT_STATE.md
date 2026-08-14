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
SPRINT GOVERNANCE / MANDATORY PRE-PRODUCTION FOLLOW-UP

## Phase 0 Documentation
APPROVED — Version 1.0

## Last Completed Sprint
SPRINT-07 — Notifications, Outbox Delivery & Automation

## Last Sprint Status
APPROVED WITH OBSERVATIONS

## Current Sprint
None

## Current Sprint Status
NO ACTIVE SPRINT

## Next Sprint
SPRINT-08 — Hardening & Production Readiness

## Next Sprint Status
PENDING OWNER AUTHORIZATION

## Canonical Provider Service Field
`consultations.provider_service_id`

## Approval
SPRINT-07 fue aprobado por el Project Owner como `APPROVED WITH OBSERVATIONS` el 2026-08-14. DEC-043 registra el cierre. Las observaciones no reabren ni modifican la implementación. SPRINT-08 requiere autorización explícita y producción permanece no autorizada.

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

## SPRINT-06 Closure Observations

1. `OBS-06-01`: EXPLAIN con volumen representativo e índices adicionales sólo con evidencia permanecen Production Gate.
2. `OBS-06-02`: normalización actual de placas aceptada; no inferir identidad ausente; formatos futuros requieren evidencia.
3. `OBS-06-03`: DataTables 1.13.6 vía CDN aceptado; revisar disponibilidad/CSP/dependencias externas antes de producción si aplica.
4. `OBS-06-04`: permanecen todos los Production Gates acumulados no cerrados.

## SPRINT-07 Closure Observations

1. `OBS-07-01`: SMTP se acepta como at-least-once; no afirmar exactly-once externo sin garantía verificable del proveedor.
2. `OBS-07-02`: clasificar destinatario inexistente/email inválido como non-retryable/skipped queda como hardening para SPRINT-08.
3. `OBS-07-03`: PHP CLI/cPanel/Artisan, working directory, Cron, logs/cache, URL, SMTP/TLS/remitente, SPF/DKIM/DMARC, límites y rebotes permanecen Production Gates.
4. `OBS-07-04`: deuda histórica del rollback global en `2026_08_05_000002_drop_label_icon_from_menu_permissions_tables` preservada; ciclos reversibles recientes aceptados.

## Accumulated Production Gates

1. MariaDB 10.6.27 real.
2. Idempotencia end-to-end request → consulta.
3. EXPLAIN/selectividad con volumen representativo.
4. Deuda histórica de migrations.
5. Malware scanning.
6. Storage, fileinfo, GD y permisos reales en Neubox.
7. Backup y rollback.
8. DataTables CDN/CSP si la política técnica aplica.
9. PHP CLI, ruta PHP, cPanel y ejecución Artisan.
10. Working directory, frecuencia Cron, timeout y overlap.
11. Logs, cache y URL productiva.
12. SMTP, TLS y remitente.
13. SPF, DKIM y DMARC.
14. Límites/rate de correo y observabilidad de rebotes.
15. Autorización productiva explícita.

## Approved Implementation Roadmap

1. SPRINT-03 — Evidence & Secure File Management.
2. SPRINT-04 — Client Portal — Notification Process.
3. SPRINT-05 — Administrative Portal — Review & Validation.
4. SPRINT-06 — Vehicle Consultation Histories + Server-side DataTables.
5. SPRINT-07 — Notifications, Outbox Delivery & Automation.
6. SPRINT-08 — Hardening & Production Readiness.
7. PRODUCTION GATE — explicit Project Owner authorization required.

DEC-036 es el roadmap canónico. SPRINT-06 está cerrado mediante DEC-042. SPRINT-07 está cerrado como APPROVED WITH OBSERVATIONS mediante DEC-043. SPRINT-08 está pendiente de autorización explícita y producción permanece no autorizada.

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
16. Production is not authorized; SPRINT-07 is ready for Owner review.
17. DEC-036 establishes SPRINT-03 through SPRINT-08 as the canonical approved roadmap; SPRINT-05 is closed with observations.
18. DEC-038 prohíbe `recovered_at` futuro y fija `America/Mexico_City` para su comparación server-side.
19. DEC-039 registra el cierre de SPRINT-04 como APPROVED WITH OBSERVATIONS.
20. DEC-040 exige motivo textual obligatorio, normalizado, persistente, histórico y visible al Cliente para todo rechazo administrativo.
21. DEC-041 registra el cierre de SPRINT-05 como APPROVED WITH OBSERVATIONS.
22. DEC-042 registra el cierre de SPRINT-06 como APPROVED WITH OBSERVATIONS.
23. SPRINT-07 implementa localmente delivery/outbox/portal/commands; cPanel Cron y SMTP productivo no están configurados.

## SPRINT-07 Approved Result

- Outbox processor, retry/backoff/dedup y canales independientes: implementados localmente.
- Portal notifications propias, read/unread, contador, listado y enlace autorizado: implementados localmente.
- Email de expedientes mediante Laravel Mail: implementado con transporte de pruebas; SMTP productivo no configurado.
- Commands discretos para outbox, deadlines y auto-close: implementados localmente.
- Scheduler no es requisito productivo; Cron real no configurado.
- SPRINT-08 no iniciado; pendiente de autorización explícita.
