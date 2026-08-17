# VINTrack — Change Request Log

All changes to approved requirements must be recorded here.

## Required Fields
- ID
- date
- requester
- requested change
- reason
- impacted documents
- impacted application/database areas
- risk
- status
- approval

## Statuses
PROPOSED / ANALYSIS / APPROVED / REJECTED / IMPLEMENTED / CANCELLED

## CR-001
Date: 2026-08-12  
Requester: Project Owner  
Requested change: Correct the accidental documentation reference `consultations.provider_services_id` to the canonical singular name `consultations.provider_service_id`.  
Reason: The code, migration and databases use `provider_service_id`; the plural form existed only as a documentation error.  
Impacted documents: `AGENTS.md` and the applicable governance and Sprint documents under `docs/`.  
Impacted application/database areas: None. No source-code, migration, schema or database change is authorized or required.  
Risk: Low; documentation-only normalization.  
Status: APPROVED — IMPLEMENTED  
Approval: APPROVED by Project Owner on 2026-08-12.  
Outcome: The singular name is confirmed as canonical. This is not a functional change or refactoring.

## Governance Rule
The AI must never silently modify the Master Specification to resolve an implementation problem. Requirement changes must be proposed and approved.

## CR-002
Date: 2026-08-12  
Requester: Project Owner  
Requested change: Close and harmonize the six approved SPRINT-01 observations: official `America/Mexico_City` temporal semantics; provisional retention without purge; cPanel `/usr/local/bin/php` primary Cron path; canonical consultation identity with one-time VIN assignment for plate drafts; replacement of historical Placas/IPH-only requirements; and explicit Client/Analyst/Global Administrator permissions.  
Reason: Resolve Owner observations and remove contradictions before any implementation Sprint.  
Impacted documents: Master Specification, Architecture, Business Rules, Data Model, Decision Log, Change Requests, Project State, SPRINT-01 rector and SPRINT-01 result.  
Impacted application/database areas: Future notification-case module only. No source code, migration, schema, data, configuration, Cron or production change is authorized or performed.  
Risk: Medium documentation risk if historical wording remains; future implementation risk around plate/VIN reconciliation, timezone consistency, retention and hosting limits.  
Status: APPROVED — DOCUMENTATION COMPLETED  
Approval: APPROVED by Project Owner on 2026-08-12.  
Outcome: Documentation harmonized; DEC-028 through DEC-034 record the final decisions. This is not functional implementation and does not authorize SPRINT-02.

## CR-003
Date: 2026-08-15
Requester: Project Owner
Requested change: Restore access to the existing HTML consultation report from the Client and Administrative consultation-history DataTables, grouping it in the existing final `Acciones` column.
Reason: The report endpoint already existed, but the History/DataTable refactor no longer exposed row-level access to it. This restores existing capability; it does not add report generation.
Impacted documents: `CHANGE_REQUESTS.md`, `PROJECT_STATE.md`, and `docs/change-results/CR-003-RESTORE-REPORT-ACCESS-FROM-HISTORY-TABLES-RESULT.md`.
Impacted application/database areas: Read-model projection for consultation histories, the two History DataTable views, and controlled 404 handling for consultations without an available report. No provider, workflow, wallet, schema, migration, or data change.
Risk: Low. The report route keeps server-side ownership/administrator authorization; the UI is not the authorization authority.
Status: APPROVED — IMPLEMENTED
Approval: Explicit Project Owner Change Request, 2026-08-15.
Outcome: Successful consultations expose `📄 Ver` in `Acciones`; failed/non-reportable consultations expose no report link. Existing notification-case actions remain grouped in the same column. Direct missing, failed, and unauthorized report requests receive controlled 404 responses.

## CR-004
Date: 2026-08-17
Requester: Project Owner
Requested change: Establish one normalized, source-specific assessment of provider results for the immutable services `placas_service` and `nmvtis_plus`. Only an explicitly mapped, current robbery/theft or fraud signal may qualify a consultation for a notification case and its consequential notifications.
Reason: Generic text/flag aggregation can classify historical, technical, financial, safety or report-severity signals as `alerta_robo`. A VINData `Recovered Theft` event is historical according to the provider but currently matches active-theft text detection. The PlacasInfo review likewise identified source-specific statuses that must not be inferred from generic text.
Impacted documents: `VINTRACK_MASTER_SPEC.md`, `BUSINESS_RULES.md`, `DATA_MODEL.md`, `ARCHITECTURE.md`, `DECISION_LOG.md`, `PROJECT_STATE.md`, and this log.
Impacted application/database areas: Provider-result interpretation for `provider_services.service_code = placas_service` and `nmvtis_plus`; consultation risk flags; history projection; report warning presentation; notification-case admission/creation; audit and outbox intent. No schema, migration, historical data rewrite, provider call, wallet, Cron, SMTP, deployment or production action is authorized by this record.
Risk: Medium. An incorrect mapping can create false-positive notification cases, trigger Portal/Email notifications, or hide a truly qualifying event. The implementation must be contract-tested with provider fixtures, preserve raw provider evidence, remain concurrent-safe and retain server-side authorization and outbox guarantees.
Status: APPROVED — IMPLEMENTED LOCALLY — OWNER LOCAL VALIDATION ACCEPTED — STAGING DEPLOYED AND VALIDATED WITH OBSERVATIONS — PRODUCTION RELEASE NOT AUTHORIZED
Approval: Explicit Project Owner approval on 2026-08-17.
Outcome / approved scope:
1. Select behavior by immutable `provider_services.service_code`, never by mutable service ID, key or display name.
2. Introduce a common provider-result assessment with explicit source predicates and classifications equivalent to `ACTIVE_QUALIFYING`, `HISTORICAL_RECORD`, `NON_QUALIFYING_WARNING`, `CLEAR` and `INDETERMINATE`.
3. Only `ACTIVE_QUALIFYING` may set the compatibility projection `alerta_robo`, create/reuse a notification case, or enqueue its case-driven Portal/Email notifications. Historical and non-qualifying warnings remain visible/auditable but do not enter the notification workflow.
4. Preserve the raw provider response and an auditable normalized assessment snapshot; do not rewrite historical consultations automatically. Provider failures, unavailable data and unknown payload variants must fail closed for qualification and be visible as indeterminate rather than clean.

Provider contract baseline:
- `placas_service`: use official PlacasInfo source-specific status fields, not generic JSON word matching. CARFAX `data.robo = false` is not a robbery signal; REPUVE vehicle data is not independently a theft determination.
- `nmvtis_plus`: `Active Theft` is potentially qualifying; `Recovered Theft` is historical and non-qualifying. Open lien, recall, towing/impound, odometer, title-brand, junk, salvage and insurance-total-loss signals are not, by themselves, robbery/fraud qualification. A generic red/yellow provider summary is not a qualifying predicate.
- A fraud qualification requires an explicit, provider-documented current fraud signal approved in the implementation mapping. Title washing, VIN cloning and title brands remain risk/history information unless such a predicate exists.

Implementation outcome: versioned contract fixtures cover active, historical, warning, clear and unknown-payload outcomes for both services. Unit and integration regressions verify that `Recovered Theft` is historical and cannot create a case or case outbox intent, while `Active Theft` creates the normal case and Portal intent. The immediate Placas banner also uses the persisted assessment, preventing a generic compatibility flag from reintroducing the CARFAX `robo=false` false positive. The full local suite passed with 130 tests and 605 assertions; the Owner accepted local visual validation. No external billable provider calls were made. Runtime source commit `c23d662803c89f47477bd77803e2a90587746693` was packaged in the Windows-compatible ZIP identified by `docs/production/deployment/CR-004-STAGING-ARTIFACT-MANIFEST.md`, then Owner-uploaded and extracted into `dev.vintrack.com.mx` / `public_html_dev`, preserving `.env` and `storage/`.

Staging validation accepted on 2026-08-17: Administrator authentication; Global History `Acciones → Ver`; existing Placas report with `robo = No` without a red theft alert; existing VinData reports with historical/warning data without creating a case or notification; no HTTP 500; and no new vehicle consultation, provider call, SMTP, Cron, Artisan or SQL operation. The following observations remain uncorrected and do not change CR-004 qualification behavior: **OBS-CR004-STG-01** existing CARFAX data in report #32 exposes the technical historical message `Cannot read properties of null (reading 'statusCode')`; **OBS-CR004-STG-02** the Notification Process page displays UTF-8 mojibake in headings. Any production release remains separately authorized and is currently not authorized.
