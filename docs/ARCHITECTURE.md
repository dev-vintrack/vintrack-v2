# VINTrack — Architecture Baseline

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
- No SSH / interactive PHP console / Git Deployment
- Laravel Scheduler cannot be relied upon

## Existing Consultation Architecture
`consultations` is the authoritative source of individual consultation events.
`vehicles` is a consolidated/reporting structure and must not become the notification-expedient master.
`consultations.provider_service_id` is already implemented and must not be recreated.

## Provider Result Assessment
Provider adapters retain responsibility for acquiring and preserving raw responses. A common Domain/Application assessment policy interprets each result using the immutable `provider_services.service_code` and versioned, source-specific predicates; controllers, views and generic JSON text scans are not the authority for qualification.

The assessment produces `ACTIVE_QUALIFYING`, `HISTORICAL_RECORD`, `NON_QUALIFYING_WARNING`, `CLEAR` or `INDETERMINATE`. Only `ACTIVE_QUALIFYING` flows to the compatibility projection `alerta_robo` and then to notification-case creation. Historical/warning categories may be rendered as report context but cannot enter the case workflow. `INDETERMINATE` is fail-closed for qualification. The notification-case service remains responsible for idempotent case creation and the transactional outbox remains the sole durable source of Portal/Email delivery intent.

## New Notification Architecture
Dedicated structures are expected for:
- notification expedient;
- evidence/document metadata;
- audit/history;
- configuration/reference data where needed.

Final schema names and relationships must be derived from the actual database during Discovery/Data Model work.

`notification_cases` references `consultations` and its immutable owner only. Origin provider service, criterion and value remain canonical in `consultations`; they are not duplicated in the case. VIN is normally copied into the snapshot, except a plate-originated draft without recoverable VIN may assign it exactly once before submission. A provisional plate guard and VIN reconciliation incident/flag handle concurrency without adding a seventh status.

## Separation of Concerns
- Consultation = historical query event.
- Notification expedient = documentary follow-up process.
- Document = evidence attached to an expedient.
- Audit = record of actions/state changes.
- Notification = communication to the user.

## Application Services
Business logic should preferably be implemented in dedicated services/domain-oriented classes instead of being duplicated across controllers/views. Exact architecture must follow the existing project conventions.

## Authorization
Client scope: authenticated client only.
Administrative scope: global, with client filtering and role/permission checks.
Capabilities are split among Client/Police, Analyst and Global Administrator according to DEC-033; even Global Administrator cannot silently alter immutable identity/history.

## DataTables
Both portals use DataTables:
- Client: own consultations.
- Administrative: all consultations, filterable by client.
Queries must be efficient and server-side scoped.

SPRINT-06 materializa esta arquitectura mediante una proyección de lectura
`ConsultationHistoryQuery`: parte siempre de `consultations`, resuelve el case
históricamente por consulta origen, identidad persistida y cronología de 90
días, y expone páginas JSON limitadas. `notification_cases` y sus eventos sólo
enriquecen la proyección; `vehicles` no participa.

## Files
Evidence must be stored securely, with server-side validation and authorized download endpoints.

## Automation
Production automation uses cPanel Cron with `/usr/local/bin/php` and a specific future Artisan command as primary path; a signed HTTPS endpoint is contingency only. Jobs must be idempotent and auditable. No Cron is implemented by this decision.

SPRINT-07 implementa localmente tres comandos Artisan discretos y acotados:
`notifications:process-outbox`, `notifications:queue-deadline-reminders` y
`notifications:auto-close`. El primero reclama mensajes mediante transacciones
cortas y entrega fuera del lock; los otros reutilizan el deadline persistido y
el lifecycle canónico. Ninguno requiere Scheduler, daemon, queue worker,
Supervisor u Horizon. La configuración real de cPanel Cron permanece pendiente
de Production Gate.

El outbox transaccional existente es la única fuente de intenciones de entrega.
Email y Portal se modelan como mensajes independientes, con `dedup_key` propio;
un fallo de canal no revierte el dominio ni el otro canal. Portal persiste en
`portal_notifications`; email reutiliza `notification_deliveries` como registro
auditable y Laravel Mail como transporte.

## Time and Retention
Functional module `DATETIME` values have `America/Mexico_City` semantics end-to-end, without implicit engine/browser conversion. Deadline uses `DATETIME(0)` at exactly 23:59:59; other timestamps may use microseconds. Provisional retention is five years for cases/evidence/audit and two years for portal notifications; no automatic purge is authorized.

## Deployment
Local testing precedes production. Production deployment requires backup, approved migration mechanism, file/config deployment, Cron configuration, validation and rollback readiness.
