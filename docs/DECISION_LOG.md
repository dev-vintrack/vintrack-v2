# VINTrack — Decision Log

**Status:** APPROVED  
**Version:** 1.0  
**Approval date:** 2026-08-12

## DEC-001 — Separate Notification Process
Notification expedients are separate from consultation history. **APPROVED**

## DEC-002 — Do Not Use vehicles
`vehicles` remains a consolidated/reporting table and is not the expedient master. **APPROVED**

## DEC-003 — consultations.provider_service_id
The singular name `consultations.provider_service_id` is canonical and is already implemented locally and in production. It must not be recreated, renamed or duplicated without explicit Owner authorization. **IMPLEMENTED**

## DEC-004 — First Police User
First qualifying police user becomes responsible. **APPROVED**

## DEC-005 — 90-Day Window
Default 90-day reuse/reference window, configurable. **APPROVED**

## DEC-006 — Previous Expedient Reference
New expedient after the window references the previous expedient where applicable. **APPROVED**

## DEC-007 — Other User
Later user within window does not create a new expedient and is informed of the existing process. **APPROVED**

## DEC-008 — Three Pending Maximum
Maximum three pending expedients. **APPROVED**

## DEC-009 — Fourth Consultation
Fourth consultation is blocked. **APPROVED**

## DEC-010 — Block Before API/Credit
The block occurs before credit/API consumption. **APPROVED**

## DEC-011 — Three Calendar Days
Deadline is consultation + 3 calendar days through 23:59:59. **APPROVED**

## DEC-012 — Deadline Immutable
Rejection/re-capture does not reset the deadline. **APPROVED**

## DEC-013 — Thirty-Day Closure
Open expedient reaches 30 days => `Cerrado por falta de seguimiento`. **APPROVED**

## DEC-014 — Rejection Preserves Number
Rejected process retains its expedient number. **APPROVED**

## DEC-015 — Client Editing
Submitted process cannot be edited by client until Analyst returns it to editable state. **APPROVED**

## DEC-016 — Required Fields
**SUPERSEDED by DEC-032.** The former “Only Placas and IPH” rule is retained only as historical traceability and is no longer authoritative.

## DEC-017 — Evidence Limits
PDF/JPG/PNG, max 3 MB/file, max 8 files. **APPROVED**

## DEC-018 — Administrative History
Administrative portal has global history with client filter. **APPROVED**

## DEC-019 — Client History
Client portal is scoped to authenticated client. **APPROVED**

## DEC-020 — Priority Colors
Own pending = light red; other user's applicable process = light blue. **APPROVED**

## DEC-021 — Audit
Entire notification process must be audited. **APPROVED**

## DEC-022 — Notifications
Relevant events notify by email and in-portal notification. **APPROVED**

## DEC-023 — cPanel Cron
Production automation uses cPanel Cron; do not depend on Laravel Scheduler. **APPROVED**

## DEC-024 — VINTrack Legal Role
VINTrack is a documentary evidence system and does not replace official authorities. **APPROVED**

## DEC-025 — Sprint Gates
Each Sprint requires explicit Owner approval before the next Sprint. **APPROVED**

## DEC-026 — SPRINT-00 Governance Closure
SPRINT-00 — Discovery / Implementation Baseline is **APPROVED WITH OBSERVATIONS** as of 2026-08-12. It must not be repeated. **APPROVED**

## DEC-027 — Current Consultation Technical Baseline
The current implementation follows this technical sequence: request → validations → wallet/balance validation → provider/API → successful response → debit → persistence. This records existing behavior and does not establish a new business rule. Refactoring the normal API/debit order is outside the current scope unless authorized through a later Change Request. The future maximum-three-pending check must execute before provider/API, debit and any billable external operation. **APPROVED AS TECHNICAL BASELINE**

## DEC-028 — Official Module Timezone
The official business timezone and persisted `DATETIME` semantics for all functional notification-case dates are `America/Mexico_City`. `notification_deadline_at` is `DATETIME(0)` ending exactly at 23:59:59; other timestamps may retain required precision. No implicit MySQL/MariaDB, `TIMESTAMP` or browser conversion is authoritative. **APPROVED — DOCUMENTAL DESIGN, NOT IMPLEMENTED**

## DEC-029 — Provisional Retention Without Purge
Provisional retention is five years for cases, documents/evidence and audit/events, and two years for portal notifications. No automatic purge, physical deletion or anonymization is authorized until legal and Project Owner approval. Functional document removal remains logical. **APPROVED — DOCUMENTAL POLICY, NOT IMPLEMENTED**

## DEC-030 — cPanel PHP CLI Primary Path
The approved primary future path is cPanel Cron → `/usr/local/bin/php` → a specific Artisan command → one reusable application service. The absolute project path and final command name remain undefined; effective PHP 8.3/version/extensions/access/limits require predeployment verification. Signed HTTPS is contingency only; generic Artisan/SQL/migration endpoints are prohibited. **APPROVED — DOCUMENTAL DESIGN, NOT CONFIGURED**

## DEC-031 — Consultation Identity and Plate VIN Exception
Canonical origin remains `consultations.id`, `user_id`, `provider_service_id`, `criterio`, `valor`; cases must not duplicate source criterion/value/provider service. Normalize historical `niv`/`vin` to VIN identity and `placa` to PLATE without rewriting data. A positive plate consultation without recoverable VIN may create a draft with nullable `vin`/`vin_key`; owner assigns a valid VIN once before submission, audited as `CASE_VIN_ASSIGNED`, then immutable. Provisional plate guard and VIN-lock reconciliation prevent silent duplicates; conflicts create an auxiliary auditable incident/flag, never a seventh status or automatic merge. **APPROVED — DOCUMENTAL DESIGN, NOT IMPLEMENTED**

## DEC-032 — Current Required Fields and VIN Immutability
Before `SUBMITTED`, required: VIN, recovery place, country, state, municipality, recovery date/time, plate, make, model year, origin, authority, investigation file, safekeeping, and at least one of IPH or NUC. Optional: neighborhood, postal code, street, number, model, engine number, color, inventory, notes and attachments. IPH/NUC are separate. VIN is automatic when available and otherwise assignable once only for the approved plate exception; folio is always automatic and immutable. **APPROVED — SUPERSEDES DEC-016; DOCUMENTAL DESIGN, NOT IMPLEMENTED**

## DEC-033 — Notification Module Permission Distribution
Permissions are explicit and server-side across Client/Police, Analyst and Global Administrator. The conceptual catalog is documented in SPRINT-01 RESULT. No actor, including Global Administrator, may silently modify an assigned VIN, reuse folios, alter owner/origin, manually select `CLOSED_NO_FOLLOW_UP`, or erase history without approved exceptional/legal procedure. **APPROVED — DOCUMENTAL DESIGN, NOT IMPLEMENTED**

## DEC-034 — SPRINT-01 Governance Closure
SPRINT-01 — Domain & Data Model Design received `APPROVED WITH OBSERVATIONS`; its six Owner observations are resolved documentally on 2026-08-12. Final status: `APPROVED — OBSERVATIONS RESOLVED`. This approval does not implement functionality and does not authorize SPRINT-02. **APPROVED**

## DEC-035 — SPRINT-02 Governance Closure
SPRINT-02 — Core Domain, Persistence & Consultation Admission received `APPROVED WITH OBSERVATIONS` from the Project Owner on 2026-08-13. OBS-02-01 through OBS-02-06 are mandatory Production Gates: real MariaDB 10.6.27 validation; remaining separate-process concurrency tests; end-to-end request → consultation idempotency; preservation of `VIN_NOT_AVAILABLE` without heuristic plate mapping; historical `adapter_code` migration-chain debt without rewriting historical migrations; and index/plan validation with representative volume. These observations do not reopen SPRINT-02 and are not implemented by this documentary closure. Production and the next Sprint require separate explicit authorization. **APPROVED**

## DEC-036 — Roadmap de implementación posterior a SPRINT-02

**Estado:** APPROVED  
**Aprobado por:** Project Owner  
**Fecha:** 2026-08-13

Se aprueba la siguiente secuencia arquitectónica:

1. SPRINT-03 — Evidence & Secure File Management.
2. SPRINT-04 — Client Portal — Notification Process.
3. SPRINT-05 — Administrative Portal — Review & Validation.
4. SPRINT-06 — Vehicle Consultation Histories + Server-side DataTables.
5. SPRINT-07 — Notifications, Outbox Delivery & Automation.
6. SPRINT-08 — Hardening & Production Readiness.
7. PRODUCTION GATE — únicamente después de aprobación explícita del Project Owner.

Cada Sprint requiere autorización explícita, debe terminar en `READY FOR OWNER REVIEW` y no autoriza iniciar automáticamente el siguiente. La aprobación del roadmap no autoriza ejecutar ningún Sprint, desplegar, ejecutar migrations/SQL, configurar Cron ni modificar producción.

SPRINT-03 implementará exclusivamente evidencia y manejo seguro de archivos sobre el núcleo de SPRINT-02; SPRINT-04 consumirá ese subsistema en el Portal Cliente; SPRINT-05 incorporará revisión/validación administrativa; SPRINT-06 implementará historiales y DataTables server-side; SPRINT-07 activará delivery/outbox/automatización; y SPRINT-08 concentrará hardening y los Production Gates pendientes de SPRINT-02, salvo dependencias necesarias para validar correctamente un Sprint anterior.

Las reglas aprobadas en SPRINT-01 y las implementadas en SPRINT-02 permanecen contractuales salvo Change Request aprobado. Producción permanece fuera de alcance hasta superar SPRINT-08 y recibir autorización explícita. **APPROVED**
