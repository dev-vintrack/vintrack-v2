# VINTrack — Codex Project Instructions

## 1. Purpose

This repository contains VINTrack, a Laravel application for national
and international vehicle-history consultations.

These instructions define how Codex must work in this repository.

The project uses a controlled Sprint-based development process.

Do not rely on conversational memory as the authoritative source for
business rules or architecture.

The project documentation under `docs/` is the persistent source of truth.


# 2. Technology Baseline

Application:

- Laravel 12
- PHP 8.3

Local development:

- Windows
- Laragon 8.6.1
- PHP 8.3
- MySQL 8.4.3

Production:

- Neubox shared hosting
- cPanel
- PHP 8.3
- MariaDB 10.6.27
- InnoDB
- utf8mb4
- SFTP available
- cPanel File Manager available
- cPanel Cron Jobs available

Production restrictions:

- No SSH
- No Git Deployment
- No interactive PHP console
- Do not assume Laravel Scheduler can be used
- Production automation must be compatible with cPanel Cron


# 3. Mandatory Project Documentation

Before performing any significant task, determine whether the following
documents are relevant and read them before modifying code:

- `docs/VINTRACK_MASTER_SPEC.md`
- `docs/ARCHITECTURE.md`
- `docs/BUSINESS_RULES.md`
- `docs/DATA_MODEL.md`
- `docs/DECISION_LOG.md`
- `docs/CHANGE_REQUESTS.md`
- `docs/PROJECT_STATE.md`

For Sprint work, also read:

- the current Sprint specification under `docs/sprints/`
- the previous Sprint RESULT file
- any earlier Sprint RESULT explicitly referenced by the current Sprint


# 4. Authority Hierarchy

When deciding how VINTrack should behave, use this priority:

1. Explicit instruction from the Project Owner for the current task.
2. Approved `VINTRACK_MASTER_SPEC.md`.
3. Approved entries in `DECISION_LOG.md`.
4. Approved Change Requests.
5. Current approved Sprint specification.
6. Previous approved Sprint results.
7. Existing application architecture and code.
8. Conversation context.

Existing code is evidence of current implementation, but it does not
automatically override an approved business requirement.

If two authoritative sources conflict:

STOP.

Do not resolve the conflict by assumption.

Report:

- the conflicting requirements;
- the files/locations involved;
- the technical impact;
- the possible alternatives.

Wait for Project Owner clarification.


# 5. Sprint Governance

VINTrack development is executed through controlled Sprints.

Never start the next Sprint automatically.

Before starting a Sprint:

1. Read this `AGENTS.md`.
2. Read `docs/VINTRACK_MASTER_SPEC.md`.
3. Read `docs/PROJECT_STATE.md`.
4. Read `docs/DECISION_LOG.md`.
5. Read `docs/CHANGE_REQUESTS.md`.
6. Read the current Sprint specification.
7. Read the previous Sprint RESULT.
8. Inspect the actual code/database relevant to the Sprint.
9. Confirm the allowed scope.
10. Identify prohibited changes.

At the end of every Sprint:

1. Run the tests required by the Sprint.
2. Perform applicable regression checks.
3. Document changes and findings.
4. Create/update the corresponding:

   `docs/sprints/SPRINT-XX-RESULT.md`

5. Mark the Sprint:

   `READY FOR OWNER REVIEW`

6. STOP.

Only the Project Owner can approve a Sprint.

Successful tests do not constitute Sprint approval.

Silence does not constitute Sprint approval.


# 6. Change Control

Do not silently change an approved business rule because implementation
would be easier another way.

If an approved requirement needs modification:

1. Stop the affected implementation.
2. Explain the conflict or limitation.
3. Propose alternatives.
4. Record the proposed change in:

   `docs/CHANGE_REQUESTS.md`

5. Wait for Owner approval.

Only approved changes become part of the project specification.


# 7. Existing Database Decision

The following refactoring has already been completed:

`consultations.provider_service_id`

It exists in:

- local MySQL;
- production MariaDB.

Do NOT:

- recreate it;
- create a duplicate field;
- create a replacement field;
- remove it;
- rename it

unless explicitly authorized by the Project Owner.


# 8. consultations Is the Consultation Source

The `consultations` table represents individual vehicle consultation
events.

Notification-expedient functionality must originate from the appropriate
consultation record and its actual relationships.

Before adding any relationship, inspect the current schema, migrations,
models and queries.


# 9. vehicles Is NOT the Notification Master

The existing `vehicles` table is a consolidated/reporting representation
of vehicle information.

It must NOT become the master/owner table for notification expedients.

Notification processes require a direct and auditable relationship with
the originating consultation and responsible user.

Do not introduce a `vehicle_id` dependency on `vehicles` for the
notification-expedient aggregate unless an approved Change Request
explicitly changes this architecture.


# 10. Separation of Concerns

Keep these concepts separate:

Consultation:
- historical vehicle-query event.

Notification Expedient:
- documentary follow-up process resulting from a qualifying consultation.

Evidence:
- files/documents associated with the expedient.

Audit:
- historical record of actions and state transitions.

Notification:
- communication to users.

Do not collapse these concerns into `consultations` or `vehicles` merely
to avoid creating an appropriate domain model.


# 11. VINTrack Legal/Domain Boundary

VINTrack functions exclusively as a documentary evidence-management
system.

VINTrack does NOT:

- replace the Ministerio Público;
- replace law-enforcement authorities;
- make official legal determinations;
- certify that official procedures were legally valid;
- alter official government records.

The system records information/evidence supplied by the police/client
user and its subsequent documentary review by a VINTrack Analyst.


# 12. Responsible Police User

The first police/client user who performs a qualifying consultation with
a positive robbery/fraud result becomes responsible for the applicable
notification expedient.

Responsibility must remain auditable.


# 13. 90-Day Rule

The notification-expedient reuse/reference window is configurable.

Default:

90 calendar days.

If another qualifying consultation for the same applicable vehicle occurs
within this window:

- do not create a new notification expedient;
- preserve the original responsible user;
- inform the later user that an applicable notification process exists.

If the configured window has expired:

- a new expedient may be created;
- it must reference the previous expedient where applicable.

Do not hard-code 90 throughout the application.


# 14. Pending Definition

For consultation-blocking purposes:

Pending means an applicable notification expedient that has NOT been
validated by the Analyst.

The definitive state-to-pending mapping must follow the approved state
machine.

Do not independently reinterpret "pending" in controllers, views or SQL.


# 15. Maximum Pending Expedients

Maximum pending notification expedients per responsible user:

3

When the authenticated user has 3 pending expedients, the next vehicle
consultation must be blocked.

The blocking check MUST happen before:

- credit consumption;
- wallet deduction;
- provider API invocation;
- any billable external operation.

Existing notification processes must remain accessible so the user can
resolve them.

Do not hard-code the maximum throughout the application.


# 16. Notification Deadline

Default notification evidence deadline:

originating consultation date + 3 calendar days.

Deadline ends at:

23:59:59

The deadline:

- is calculated from the originating qualifying consultation;
- must be stored/handled consistently with the application's business
  timezone;
- does not reset after rejection;
- does not reset after re-capture;
- must remain auditable.

Do not hard-code the 3-day value throughout the application.


# 17. Maximum Open Period

A notification expedient must not remain open for more than:

30 calendar days.

At the configured limit, an applicable open expedient automatically
transitions to:

`Cerrado por falta de seguimiento`

This is independent from the 3-day evidence deadline.

Automatic transitions must be:

- idempotent;
- audited;
- safe to execute repeatedly;
- compatible with cPanel Cron.


# 18. Rejection

An Analyst may reject a submitted notification process.

Rejection:

- does NOT create a new expedient;
- does NOT change the expedient number;
- does NOT reset the original notification deadline.

The Analyst may return the same process to an editable state when
additional information or evidence is required.


# 19. Client Editing

After submission, the client/police user cannot edit the submitted
notification process unless an authorized Analyst returns it to an
editable state.

This restriction MUST be enforced server-side.

Never rely only on hidden/disabled UI controls.


# 20. Required Capture Fields

Before submission, the client capture must include:

- VIN;
- recovery place;
- country;
- state;
- municipality;
- recovery date/time;
- license plate;
- make;
- model year;
- origin;
- authority;
- investigation file;
- safekeeping;
- at least one of IPH or NUC.

IPH and NUC are independent fields. One or both may be supplied.

Optional fields follow the approved SPRINT-01 field matrix.

For normalized `niv`/`vin` criteria, VIN comes from
`consultations.valor`. For `placa`, use an unequivocal provider VIN when
available. Only a plate-originated draft without a recoverable VIN may
temporarily keep VIN nullable and permit its responsible owner to assign
a valid VIN exactly once before `SUBMITTED`. After the first valid
assignment VIN is immutable.

The notification-case folio is always automatic and immutable.


# 21. Evidence Files

Allowed evidence formats:

- PDF
- JPG
- PNG

Default limits:

- maximum 3 MB per file;
- maximum 8 files per notification expedient.

Validation must be server-side.

Files must not be exposed as unrestricted public files.

Downloads must enforce authorization.


# 22. Auditability

The complete notification process must be auditable.

Audit relevant actions including:

- expedient creation;
- client submission;
- evidence upload;
- analyst review;
- validation;
- rejection;
- reopening;
- status transitions;
- automatic closure;
- administrative overrides;
- relevant notification events.

Where appropriate capture:

- actor;
- action;
- entity;
- entity ID;
- previous state;
- new state;
- timestamp;
- reason/comment;
- metadata.


# 23. Notifications

Relevant business events must support:

- email notification;
- in-portal notification.

Reuse the application's existing notification infrastructure where
appropriate.

Notification delivery must be idempotent/deduplicated where required.


# 24. Client Portal Scope

The Client Portal history must only expose records authorized for the
authenticated client/user.

The Client Portal will include:

`Historial de Vehículos Consultados`

It must show the authenticated user's consultation history, including
notification status where applicable.

When another user owns an applicable notification process, show the
appropriate informational state without exposing unauthorized evidence
or private process information.

Visual priority:

- own pending process: light red;
- applicable process belonging to another user: light blue.


# 25. Administrative Portal Scope

The Administrative Portal will include:

`Historial Global de Vehículos Consultados`

It shows consultations across all clients/users.

It must support filtering by a specific client.

Administrative actions must still respect the existing role/permission
architecture.


# 26. Authorization

Every new endpoint must enforce authorization server-side.

Protect against:

- IDOR;
- cross-client access;
- unauthorized evidence downloads;
- forged status changes;
- mass assignment;
- privilege escalation.

A user changing an ID in a URL/request must never gain access to another
client's protected notification process.


# 27. DataTables

Existing DataTables may be used as architectural references, but do not
blindly copy client-side pagination for large histories.

New history functionality should be designed for scalable server-side
pagination/filtering where justified.

Avoid:

- loading unbounded histories into memory;
- N+1 queries;
- authorization filtering only in JavaScript.


# 28. Concurrency

Business rules involving:

- first responsible user;
- 90-day reuse;
- maximum 3 pending;
- expedient creation;
- state transitions

must be designed for concurrent requests.

Do not rely exclusively on:

`SELECT -> IF -> INSERT`

when concurrent execution can create duplicates.

Use appropriate:

- transactions;
- constraints;
- locking;
- idempotency;
- retry behavior.


# 29. Idempotency

Automated or retryable operations must be idempotent.

Prevent duplicate:

- expedients;
- automatic closures;
- notifications;
- audit transitions;
- other retryable side effects.

Reuse existing VINTrack patterns where appropriate, but do not blindly
copy unrelated implementations.


# 30. Database Compatibility

All new database design must be compatible with BOTH:

Local:
MySQL 8.4.3

Production:
MariaDB 10.6.27

Use:

- InnoDB
- utf8mb4

Before using engine-specific features, verify compatibility.

Pay particular attention to:

- JSON;
- generated columns;
- indexes;
- foreign keys;
- default expressions;
- timestamps;
- raw SQL;
- collations.

All functional notification-module `DATETIME` values have contractual
`America/Mexico_City` semantics end-to-end. Do not rely on implicit
database-session, `TIMESTAMP`, browser or server timezone conversion.
`notification_deadline_at` uses second precision and ends exactly at
23:59:59; other module timestamps may use microsecond precision where
required.


# 31. Database Safety

Never execute destructive database operations unless explicitly
authorized.

Never:

- reset production;
- truncate production tables;
- drop production tables;
- mass-delete production records;
- run broad UPDATE/DELETE without verified scope.

Database migrations must be tested locally first.

Production migration requires:

- explicit approval;
- backup;
- rollback strategy;
- compatibility validation.


# 32. Production Safety

Never deploy to production merely because a Sprint implementation is
complete.

Production deployment is a separate approved action.

Before production changes:

1. Relevant Sprint must be approved.
2. Local tests must pass.
3. Production impact must be documented.
4. Backup must exist.
5. Rollback must be defined.
6. Project Owner must explicitly authorize deployment.


# 33. Production Automation

Do not assume SSH or interactive Artisan execution in production.

Any scheduled automation must be explicitly designed for the actual
Neubox/cPanel environment.

cPanel Cron Jobs are available.

Laravel Scheduler must not be treated as available unless its execution
mechanism is explicitly verified.


# 34. Testing

For every implementation Sprint:

- identify affected tests before coding;
- add/update tests appropriate to the change;
- run focused tests;
- run relevant regression tests;
- document failures;
- do not hide failing tests.

Do not alter tests merely to make an incorrect implementation pass.

If an existing test conflicts with an approved requirement, report the
conflict.


# 35. Coding Style

Before introducing new architectural patterns, inspect the existing
VINTrack implementation.

Prefer consistency with the existing:

- Domain;
- Application;
- Infrastructure;
- Presentation

architecture discovered in SPRINT-00.

Avoid putting substantial business logic directly in:

- controllers;
- Blade views;
- JavaScript;
- DataTable rendering code.

Business rules should have a single authoritative implementation.


# 36. Documentation

When a Sprint changes architecture, data model or business behavior,
update the appropriate project documentation.

Do not silently allow documentation and implementation to diverge.


# 37. Sprint Result Requirements

Every Sprint RESULT should contain, where applicable:

- scope completed;
- scope not completed;
- files changed;
- database changes;
- architecture decisions;
- tests executed;
- tests passed;
- tests failed;
- security considerations;
- known issues;
- risks;
- production impact;
- rollback considerations;
- open questions;
- recommendation.

End with:

`READY FOR OWNER REVIEW`

unless the Project Owner has explicitly approved closure.


# 38. Current Project State

SPRINT-00 Discovery has been completed.

Its result is:

`docs/sprints/SPRINT-00-RESULT.md`

SPRINT-00 is considered:

`APPROVED WITH OBSERVATIONS`

SPRINT-01 — Domain & Data Model Design is:

`APPROVED — OBSERVATIONS RESOLVED`

The current implemented Sprint is:

`SPRINT-06 — Vehicle Consultation Histories + Server-side DataTables`

SPRINT-06 is `READY FOR OWNER REVIEW` after local implementation and testing
under `docs/sprints/SPRINT-06-VEHICLE-CONSULTATION-HISTORIES.md`.
Production, SPRINT-07, notification delivery and Cron remain unauthorized.


# 39. Golden Rule

When uncertain:

DO NOT GUESS.

Inspect the repository.

If the answer still cannot be established, document:

`NO DETERMINADO`

Explain what information is missing and ask the Project Owner.

Correctness, data integrity, auditability and controlled evolution take
priority over implementation speed.
