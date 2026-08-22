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

## CR-005
Date: 2026-08-17
Requester: Project Owner
Requested change: Remediate `OBS-CR004-STG-01` by presenting persisted provider technical failures in an authorized consultation report through a controlled, user-safe status instead of exposing raw exception text.
Reason: Staging report #32 displays the historical CARFAX message `Cannot read properties of null (reading 'statusCode')`. It is not an HTTP 500 and it does not alter CR-004 qualification, but it exposes implementation detail to an authenticated end user.
Impacted documents: `CHANGE_REQUESTS.md`, `PROJECT_STATE.md`, and a future change-result document. Update other rector documents only if an approved implementation changes an established architectural or business contract.
Impacted application/database areas: Report presentation/read model for already persisted provider responses, including the existing authorized Client/Admin report route. The raw provider response must remain preserved as historical consultation evidence. No provider adapter behavior, new provider call, consultation qualification, `alerta_robo`, notification-case admission, outbox, wallet, schema, migration, backfill, Cron, SMTP, deployment, or production change is in scope.
Risk: Medium security/quality risk. An over-broad sanitizer could hide a legitimate provider business message; an under-broad implementation could continue leaking technical detail. Authorization and controlled 404 behavior must remain intact.
Proposed acceptance criteria:
1. A recognized technical/provider-processing failure is rendered as a stable, non-sensitive availability/status message; raw exception strings, stack traces, credentials and payload internals are not displayed to Client or Administrator report consumers.
2. Legitimate provider business findings remain visible and are not converted into technical errors merely because they contain warning language.
3. The immutable historical raw response remains unchanged; no automatic data rewrite or backfill is permitted.
4. Technical/unavailable payloads remain fail-closed for robbery/fraud qualification and cannot create a notification case or case-driven Portal/Email intent.
5. Tests cover authorized Client/Admin access, unauthorized access, missing report, a persisted technical failure, a legitimate provider warning, and absence of notification-workflow side effects. Local visual validation is required before any staging validation.
Status: APPROVED — IMPLEMENTED LOCALLY — OWNER LOCAL VISUAL VALIDATION ACCEPTED — STAGING NOT AUTHORIZED
Approval: Explicit Project Owner approval on 2026-08-17. Authorization is limited to local implementation and validation. It does not authorize a provider call, staging upload, production action, SQL, migration, Cron or SMTP activity.
Implementation outcome: `PlacasReportPresenter` now recognizes the evidenced historical implementation-error signature `Cannot read properties of null|undefined` (and bounded common exception/stack signatures) and renders the stable `Estado del proveedor: Proveedor temporalmente no disponible.` status. The response payload remains immutable in `consultations.response_json`; legitimate provider business findings remain rendered. This presentation-only branch cannot set `alerta_robo` or create notification cases/outbox intents.
Local validation accepted: On 2026-08-17 the Owner confirmed authenticated visual validation with a positive result. The controlled report presentation was accepted without a new vehicle consultation, provider call, SMTP, Cron, Artisan, SQL, staging or production action.

## CR-006
Date: 2026-08-17
Requester: Project Owner
Requested change: Diagnose and correct `OBS-CR004-STG-02`, UTF-8 mojibake in the Notification Process UI, while preserving the approved notification-case workflow and historical data.
Reason: The staging Notification Process page renders labels such as `VehÃ­culo` and `EnvÃ­o / actualizaciÃ³n` with invalid character decoding. This is a presentation/data-encoding integrity defect distinct from provider-result assessment.
Impacted documents: `CHANGE_REQUESTS.md`, `PROJECT_STATE.md`, and a future change-result document. Update other rector documents only if the diagnosis establishes an approved contractual correction.
Impacted application/database areas: The affected HTTP/Blade/layout/translation/static-text encoding path and its test coverage. Diagnosis must identify whether the source is response headers, template bytes, translation/static assets, or persisted display data before any correction. No notification state-machine, deadline, 90-day rule, audit semantics, outbox delivery, provider behavior, wallet, schema, migration, data conversion, Cron, SMTP, deployment, or production change is in scope.
Risk: Medium presentation and data-integrity risk. A blind global encoding conversion could corrupt correct UTF-8 source files or persisted historical evidence.
Proposed acceptance criteria:
1. Root cause is evidenced before editing; no blanket repository/database recoding is allowed.
2. Affected Notification Process labels render valid Spanish UTF-8 in local and staging browser validation, with an explicit UTF-8 response/content path where applicable.
3. The repair leaves canonical statuses, folios, dates, audit events, notification cases, Evidence metadata, Portal notifications and Email/outbox behavior unchanged.
4. Regression covers the affected page plus Client/Admin authorization and existing notification views; no data mutation is needed to establish the correction.
5. If persisted historical data is found corrupt, stop and submit a separate, explicitly scoped data-remediation proposal rather than altering it under this request.
Status: APPROVED — IMPLEMENTED LOCALLY — OWNER LOCAL VISUAL VALIDATION ACCEPTED — STAGING NOT AUTHORIZED
Approval: Explicit Project Owner approval on 2026-08-17. Authorization is limited to local implementation and validation. It does not authorize a provider call, staging upload, production action, SQL, migration, Cron or SMTP activity.
Implementation outcome: byte-level inspection established that the defect is limited to mojibaked static UTF-8 literals in `resources/views/admin/notification-cases/index.blade.php`; it is not a database, workflow, state, outbox or response-header defect. Only the affected static headings/fallback label were replaced with valid UTF-8. No blanket source/database recoding was performed.
Local validation accepted: On 2026-08-17 the Owner confirmed authenticated visual validation with a positive result. The administrative Notification Process view rendered `Vehículo`, `Apertura / límite` and `Envío / actualización` correctly; no workflow, SQL, provider, SMTP, Cron, staging or production action was performed.

Corrective addendum, 2026-08-17: Owner evidence from the administrative detail route `/admin/proceso-notificaciones/{case}` identified the same mojibake in that distinct `show` template. The defect is again limited to static template literals; the case values shown in fields remain untouched. The detail template was corrected locally for labels, actions and symbols, and the authorized-administrator portal regression passed. Owner visual validation of this addendum remains pending; no new staging or production action is authorized by this correction.

## CR-007
Date: 2026-08-17
Requester: Project Owner
Requested change: Select Cloudmersive Virus Scan API as the real malware-scanning provider for PG-05 and integrate its advanced file scan behind the approved `MalwareScanner` abstraction.
Reason: Close the local fake-only gap while preserving quarantine-first and fail-closed evidence handling.
Impacted documents: `CHANGE_REQUESTS.md`, `DECISION_LOG.md`, `PROJECT_STATE.md`, and the CR-007 result.
Impacted application/database areas: Provider configuration/secrets, Infrastructure malware adapter, private Evidence stream read, service-container binding, tests and the existing bounded Artisan processor. No schema, migration, historical data, consultation, outbox or state-machine change.
Risk: High privacy/availability/cost risk because private Evidence leaves VINTrack through HTTPS. The provider plan is 600 calls/month, 1 call/second and 3.5 MB per file; VINTrack preserves its 3 MB limit. A provider failure remains fail-closed and retryable only for temporary errors.
Status: APPROVED — IMPLEMENTED LOCALLY — EXTERNAL PRIVACY/CONTRACT AND NEUBOX VERIFICATION PENDING
Approval: Explicit Project Owner instruction, 2026-08-17. Cloudmersive is approved conditionally for local integration; production activation requires the pending provider confirmation on privacy, retention, contractual terms and North America processing.
Outcome: The adapter uses `POST /virus/scan/file/advanced`, sends the file only from private storage, restricts accepted content to PDF/JPG/JPEG/PNG and blocks executable, invalid, script, encrypted, macro, XXE, unsafe archive, OLE and unwanted-action content. `CleanResult=true` is the sole `CLEAN` outcome. Missing configuration, malformed responses and provider errors remain blocked. API keys are environment secrets and are not stored in source control.

## CR-008
Date: 2026-08-19
Requester: Project Owner
Requested change: Make provider response waiting configurable for `placas_service` and `nmvtis_plus`, preserve asynchronous Placas polling for several minutes when needed, and replace technical provider synchronization failures shown to the client with a user-safe message.
Reason: Placas may return `204 No Content` while an asynchronous report remains in progress for more than 50 seconds; the existing finite polling window can return the internal `No-JSON (204)` string to an end user. VINData reports can likewise exceed the existing one-minute request timeout.
Impacted application/database areas: Provider adapter runtime configuration, bounded external HTTP/poll behavior, controlled failure presentation in the consultation endpoint/client UI, and tests. No schema, migration, database data, wallet rule, provider-result qualification, notification-case workflow, Cron, SMTP, staging, or production change.
Risk: Medium availability/cost risk. Longer web requests require compatible PHP/FastCGI hosting limits and must not turn transient provider failures into success, duplicate debits, or repeated provider POSTs.
Status: APPROVED — IMPLEMENTED LOCALLY — OWNER LOCAL VISUAL VALIDATION ACCEPTED — STAGING NOT AUTHORIZED
Approval: Explicit Project Owner instruction on 2026-08-19, limited to local implementation and validation.
Outcome: Local automated validation passed and the Owner confirmed local visual validation as correct. Staging remains unauthorized.

## CR-009
Date: 2026-08-19
Requester: Project Owner
Requested change: Correct the PlacasInfo provider-result evaluator so PGJ and Aviso accept documented object/list response forms, source error signatures remain indeterminate rather than clean, and current RAPI/REPUVE provider states are assessed according to the supplied PlacasInfo interpretation guide.
Reason: Production payloads preserved in `consultations.id` 64 and 65 show the same current PGJ theft (`ID_ESTATUS_VHI_ROBO=1`) in object and list forms. The pre-change evaluator read only the object form, producing a false negative for record 65 despite the documented provider signal.
Impacted documents: `CHANGE_REQUESTS.md`, `DECISION_LOG.md`, `PROJECT_STATE.md`, and `docs/change-results/CR-009-PLACASINFO-PAYLOAD-NORMALIZATION-RESULT.md`.
Impacted application/database areas: Versioned `placas_service` result assessment, compatibility flags/assessment snapshot for future consultations, contract fixtures and regressions. No migration, schema/data update, historical rewrite, provider call, wallet, case state-machine, outbox, Cron, SMTP, staging, or production action.
Risk: High classification risk. A false negative can suppress a qualifying notification case; a false positive can create one incorrectly. The change preserves raw payloads, is selected only by immutable `service_code`, and is tested against the two supplied production payload forms.
Status: APPROVED — IMPLEMENTED LOCALLY — OWNER LOCAL VISUAL VALIDATION ACCEPTED — STAGING NOT AUTHORIZED
Approval: Explicit Project Owner instruction on 2026-08-19, limited to local implementation and validation.
Outcome: Full local regression passed. On 2026-08-20 the Owner confirmed positive local visual validation. Staging remains unauthorized.

## CR-010
Date: 2026-08-21
Requester: Project Owner
Requested change: Replace Laravel's technical `419 Page Expired` presentation with a controlled session-expiration experience for HTML forms and asynchronous requests.
Reason: A user who remains on the login form until its CSRF token expires receives a technical error after entering credentials. The same condition can occur on other forms and asynchronous actions.
Impacted documents: `CHANGE_REQUESTS.md` and `docs/change-results/CR-010-SESSION-EXPIRATION-UX-RESULT.md`.
Impacted application/database areas: Laravel exception rendering, authentication/public/application layouts, and browser `fetch` handling. No authentication rule, session lifetime, CSRF protection, database, schema, migration, provider, wallet, notification-case workflow, Cron, SMTP, staging, or production change.
Risk: Medium security/UX risk. An over-broad 419 handler could conceal unrelated errors or repeat a stale request. The implementation must recognize only Laravel's wrapped `TokenMismatchException`, invalidate the affected session, never replay the request, and retain a distinct JSON contract for asynchronous clients.
Status: APPROVED — IMPLEMENTED LOCALLY — OWNER LOCAL VISUAL VALIDATION PENDING — STAGING NOT AUTHORIZED
Approval: Explicit Project Owner instruction on 2026-08-21, limited to local implementation and validation.
Outcome: A Laravel CSRF token mismatch now invalidates the affected session and issues either a login redirect with the flash message `Tu sesión expiró por inactividad. Por favor, ingresa nuevamente.` or a `419` JSON response with stable `SESSION_EXPIRED` code and login redirect URL. Browser fetch handling redirects only for that exact JSON contract. Passwords, OTPs, files and original POST/DELETE operations are never retained or replayed.
