# VINTrack — Architecture Baseline

**Status:** APPROVED  
**Version:** 1.0  
**Approval date:** 2026-08-12

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

## Files
Evidence must be stored securely, with server-side validation and authorized download endpoints.

## Automation
Production automation uses cPanel Cron with `/usr/local/bin/php` and a specific future Artisan command as primary path; a signed HTTPS endpoint is contingency only. Jobs must be idempotent and auditable. No Cron is implemented by this decision.

## Time and Retention
Functional module `DATETIME` values have `America/Mexico_City` semantics end-to-end, without implicit engine/browser conversion. Deadline uses `DATETIME(0)` at exactly 23:59:59; other timestamps may use microseconds. Provisional retention is five years for cases/evidence/audit and two years for portal notifications; no automatic purge is authorized.

## Deployment
Local testing precedes production. Production deployment requires backup, approved migration mechanism, file/config deployment, Cron configuration, validation and rollback readiness.
