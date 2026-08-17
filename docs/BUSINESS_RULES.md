# VINTrack — Business Rules

**Status:** APPROVED  
**Version:** 1.1
**Approval date:** 2026-08-17

| ID | Rule |
|---|---|
| BR-001 | Positive robbery/theft or fraud consultation can initiate the notification process. |
| BR-002 | First police user with the qualifying consultation becomes responsible. |
| BR-003 | Within configurable 90 days, a later qualifying consultation does not create a new expedient. |
| BR-004 | A later user is informed that another user's applicable process exists. |
| BR-005 | After 90 days, a new expedient may be created and references the previous expedient when applicable. |
| BR-006 | Pending = not validated by Analyst. |
| BR-007 | Maximum 3 pending expedients per user. |
| BR-008 | With 3 pending, the next consultation is blocked. |
| BR-009 | Blocking occurs before credit/API consumption. |
| BR-010 | Existing expedient capture/access remains available while consultation is blocked. |
| BR-011 | Deadline = consultation + 3 calendar days through 23:59:59. |
| BR-012 | Deadline never resets after rejection/re-capture. |
| BR-013 | Open expedient reaches 30 days => `Cerrado por falta de seguimiento`. |
| BR-014 | Rejection preserves the same expedient number. |
| BR-015 | Analyst can return rejected process to editable state. |
| BR-016 | Client cannot edit after submission unless Analyst returns it to editable state. |
| BR-017 | Before submission require VIN, recovery place, country, state, municipality, recovery date/time, plate, make, model year, origin, authority, investigation file, safekeeping, and IPH or NUC (at least one). Optional fields follow the approved SPRINT-01 matrix. |
| BR-018 | For `niv`/`vin`, VIN is `consultations.valor`; for `placa`, use unequivocal provider VIN or permit one audited owner assignment before submission. VIN is immutable after first valid assignment; folio is always immutable. |
| BR-019 | Evidence: PDF/JPG/PNG, max 3 MB/file, max 8 files. |
| BR-020 | All relevant actions and state transitions are audited. |
| BR-021 | Client history shows only authenticated client's consultations. |
| BR-022 | Administrative history shows all clients and supports client filtering. |
| BR-023 | Own pending = light red; another user's applicable process = light blue. |
| BR-024 | Relevant events generate email and in-portal notifications. |
| BR-025 | VINTrack is documentary evidence software, not an official authority. |
| BR-026 | State transitions are explicit and server-side validated. |
| BR-027 | Automated jobs are idempotent. |
| BR-028 | Configurable values must not be scattered as hard-coded literals. |
| BR-029 | Official module timezone and persisted `DATETIME` semantics are `America/Mexico_City`; avoid implicit DB/browser conversions and double conversion. |
| BR-030 | Automated jobs must operate only within their intended scope. |
| BR-031 | Canonical origin identity remains in `consultations.id/user_id/provider_service_id/criterio/valor`; do not duplicate criterion, value or provider service in the case. `niv`/`vin` map to VIN and `placa` maps to PLATE without rewriting history. |
| BR-032 | A plate draft without VIN uses a concurrent provisional identity; before first VIN assignment lock/reconcile against applicable cases. Conflicts create an auditable reconciliation incident/flag, not an automatic merge or seventh state. |
| BR-033 | Provisional retention: cases/evidence/audit 5 years; portal notifications 2 years. No automatic purge or physical deletion is authorized pending legal and Owner approval. |
| BR-034 | Primary future automation is cPanel Cron → `/usr/local/bin/php` → specific Artisan command → reusable application service; signed HTTPS is contingency only. |
| BR-035 | Module permissions are explicit by Client/Police, Analyst and Global Administrator; immutable identity/history and automatic closure cannot be overridden silently. |
| BR-036 | Provider-result qualification is selected by immutable `provider_services.service_code` and uses explicit provider predicates. Only `ACTIVE_QUALIFYING` current robbery/theft or explicitly documented current fraud may set `alerta_robo`, create/reuse a notification case, or cause its case-driven notifications. |
| BR-037 | `HISTORICAL_RECORD`, `NON_QUALIFYING_WARNING`, `CLEAR` and `INDETERMINATE` results never qualify by themselves. `Recovered Theft`, liens, recalls, towing/impound, odometer issues, title brands, junk/salvage/total-loss and provider red/yellow presentation are non-qualifying absent a separate explicit current robbery/fraud predicate. Unknown, unavailable and error payloads fail closed for qualification. |
| BR-038 | Preserve the raw provider response and an auditable normalized assessment snapshot. Do not automatically rewrite historical consultations; provider mappings require versioned contract fixtures and regression of case, history, authorization and independent Portal/Email outbox behavior. |
