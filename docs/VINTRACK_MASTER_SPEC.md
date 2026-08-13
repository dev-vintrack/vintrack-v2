# VINTrack — Master Project Specification

**Phase:** 0 — Project Governance  
**Status:** APPROVED  
**Version:** 1.0  
**Approval date:** 2026-08-12  
**Framework:** Laravel 12  
**PHP:** 8.3

## 1. Purpose
This is the authoritative functional and architectural specification for the VINTrack notification-evidence/expedient process. Every Sprint must follow it.

## 2. Source of Truth
Authority order:
1. Explicitly approved Owner changes.
2. This Master Specification once approved.
3. Approved Decision Log.
4. Approved Sprint specifications/results.
5. Existing code/database.
6. Conversation context.

If sources conflict, stop and report the conflict; do not infer.

## 3. Existing System
VINTrack has a Client Portal and Administrative Portal sharing one database. Individual vehicle consultations are stored in `consultations`. `vehicles` is a consolidated/reporting structure and must not become the notification-expedient master.

## 4. Existing Refactoring
`consultations.provider_service_id` has already been implemented in local MySQL 8.4.3 and production MariaDB 10.6.27. Do not recreate it.

## 5. Fundamental Separation
Consultation history and notification expedients are separate concerns. A consultation is a historical event; an expedient is a documentary business process associated with a qualifying consultation and responsible user.

## 6. Qualifying Result
A notification process applies when a consultation produces a positive robbery/theft or fraud result according to VINTrack's existing provider/service interpretation.

VINTrack is exclusively a documentary evidence system. It does not replace official authorities or certify legal validity.

## 7. Responsible User
The first police user who performs a qualifying positive consultation becomes responsible for the applicable notification expedient.

## 8. Ninety-Day Rule
The reuse/reference window defaults to 90 calendar days and is configurable. A qualifying consultation within the window does not create another expedient. After the window, a new expedient may be created and should reference the previous expedient number when applicable.

## 9. Pending
Pending means an applicable notification expedient that has NOT been validated by the Analyst.

## 10. Maximum Pending
A user may have at most three pending expedients. With exactly three pending expedients, a new consultation is blocked before credit consumption, balance deduction, or provider API invocation.

The restriction does not block access to or completion of existing expedients.

## 11. Three-Day Deadline
Deadline = consultation date + 3 calendar days, through exactly 23:59:59. It remains unchanged after rejection/re-capture. The official business timezone and persisted `DATETIME` semantics for this module are `America/Mexico_City`; no implicit database/browser timezone conversion is allowed. `notification_deadline_at` has second precision.

## 12. Thirty-Day Closure
An open expedient must not remain open beyond 30 calendar days. It must automatically become **Cerrado por falta de seguimiento**. This transition must be audited.

## 13. Client Capture
Before submission the required fields are: VIN, recovery place, country, state, municipality, recovery date/time, license plate, make, model year, origin, authority, investigation file, safekeeping, and at least one of IPH or NUC. IPH and NUC are independent. Optional fields are neighborhood, postal code, street, number, model, engine number, color, inventory, notes and attachments.

VIN comes automatically from `consultations.valor` for normalized criteria `niv`/`vin`, or from an unequivocal provider response for `placa`. Only a plate-originated draft without recoverable VIN may keep VIN null temporarily; its owner must assign a valid VIN exactly once before `SUBMITTED`, after which it is immutable. Folio is always automatic and immutable.

## 14. Editing/Rejection
After client submission, the police user cannot edit. The Analyst may return the process to an editable state.

The Analyst may reject. Rejection preserves the same expedient number. The original deadline does not reset.

## 15. Evidence
Allowed: PDF, JPG, PNG. Maximum 3 MB per file. Maximum 8 files. Validate server-side. Store securely and do not expose files publicly without authorization.

## 16. General Status
The sole canonical state has exactly: `PENDING`, `SUBMITTED`, `UNDER_REVIEW`, `REJECTED`, `VALIDATED`, `CLOSED_NO_FOLLOW_UP`. UI notification/validation statuses are projections, not persisted parallel states.

## 17. Client Portal
Add **Historial de Vehículos Consultados**, scoped only to the authenticated client. Show notification-related status information and actions.

When another user owns an applicable process, show **Expediente de notificación en proceso por otro usuario**, visually lower priority (light blue). The authenticated user's own pending processes use light red.

## 18. Administrative Portal
Add **Historial Global de Vehículos Consultados**, conceptually equivalent to the Client Portal history but globally scoped. It must show all clients and allow filtering by a specific client. Analysts can access authorized notification/evidence operations.

## 19. Status Columns
Where applicable:
- Status Robo
- Status Notificado por Cliente
- Status Validado por el Analista
- Fecha límite para notificar
- Estado General del Proceso
- Acciones

## 20. Audit
The complete process must be auditable, including actor, action, timestamp, entity, previous/new state, reason/comment where applicable, and document actions.

## 21. Notifications
Relevant events must notify by email and in-portal notification. Delivery must be auditable.

## 22. Authorization
Enforce authorization server-side. A client must never access another client's expedient by manipulating IDs, URLs, payloads or DataTable parameters. File downloads must also be authorized.

## 23. Idempotency
Automated jobs must be idempotent: repeated execution must not duplicate expedients, notifications, or automatic state transitions.

## 24. Database Compatibility
Local: MySQL 8.4.3. Production: MariaDB 10.6.27. Use InnoDB and utf8mb4. Validate cross-engine behavior.

## 25. Production Constraints
Neubox shared hosting provides cPanel, PHP 8.3, SFTP, File Manager and Cron Jobs. It has no SSH, no interactive PHP console or Git Deployment, and Laravel Scheduler cannot be relied upon. The approved primary future automation path is cPanel Cron → `/usr/local/bin/php` → a specific Artisan command → one reusable application service. Effective PHP version/extensions, project access, permissions, frequency and hosting limits require predeployment verification. A signed HTTPS endpoint is contingency only and can never expose generic Artisan, SQL or migrations.

## 25.1 Provisional Retention
Provisional targets are five years for cases, evidence and audit events, and two years for portal notifications. No automatic purge, physical deletion or anonymization is authorized. Legal and Owner approval is mandatory before any future retention action; logical document removal remains non-destructive.

## 25.2 Consultation Identity and Permissions
The canonical origin is `consultations.id/user_id/provider_service_id/criterio/valor`; do not duplicate criterion, value or provider service in `notification_cases`. Normalize `niv`/`vin` to VIN identity and `placa` to PLATE identity without rewriting history. Plate drafts without VIN use a concurrent provisional guard derived from provider service + PLATE + normalized plate, then reconcile under VIN lock before first assignment. Conflicts create an auditable reconciliation incident/flag, never a seventh case state or automatic merge.

Authorization is capability-based and server-side. Client, Analyst and Global Administrator have the approved distribution recorded in DEC-033; no actor may silently change VIN after first assignment, folio, owner or origin, manually choose `CLOSED_NO_FOLLOW_UP`, or erase history without legal policy.

## 26. Sprint Governance
Each Sprint must:
1. Read this specification.
2. Read `PROJECT_STATE.md`.
3. Read `DECISION_LOG.md`.
4. Read the Sprint specification.
5. Inspect actual code/database.
6. Implement only approved scope.
7. Test.
8. Produce a result report.
9. Stop for Owner approval.

Never continue automatically to the next Sprint.

## 27. Change Governance
Changes to approved requirements must be recorded in `CHANGE_REQUESTS.md` and approved before becoming authoritative. Do not silently alter this specification.

## 28. Production Safety
No destructive production operation, data deletion, database reset, or unreviewed broad update/delete is allowed. Production changes require explicit Owner authorization.

## 29. Definition of Done
The project is complete only when all approved Sprints, tests, authorization checks, file security, auditability, automation, deployment documentation and production validation are complete.
