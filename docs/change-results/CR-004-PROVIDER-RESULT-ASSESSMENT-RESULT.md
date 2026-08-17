# CR-004 — Provider Result Assessment Result

**Status:** APPROVED — IMPLEMENTED LOCALLY — OWNER LOCAL VALIDATION ACCEPTED — STAGING DEPLOYED AND VALIDATED WITH OBSERVATIONS — PRODUCTION RELEASE NOT AUTHORIZED
**Date:** 2026-08-17
**Authorization:** Project Owner authorization to implement CR-004 using contract fixtures and without billable provider calls.

## Scope completed

- Added a common domain assessment with the classifications `ACTIVE_QUALIFYING`, `HISTORICAL_RECORD`, `NON_QUALIFYING_WARNING`, `CLEAR` and `INDETERMINATE`.
- Selected behavior through immutable `provider_services.service_code` for `placas_service` and `nmvtis_plus`.
- Preserved raw provider data in `consultations.response_json` and stored the versioned normalized assessment snapshot in existing `consultations.flags_json`; no schema change was required.
- Changed the compatibility projection `alerta_robo` so that, for assessed services, it is true only for `ACTIVE_QUALIFYING`.
- Aligned the immediate Placas response banner with the persisted consultation decision, rather than re-evaluating compatibility flags.
- Preserved the existing notification-case creation/reuse, 90-day, admission, authorization and outbox mechanisms. They are reached only after an active qualifying assessment.

## Source-specific behavior

| Service code | Current qualifying predicates | Non-qualifying handling |
| --- | --- | --- |
| `placas_service` | PGJ `ID_ESTATUS_VHI_ROBO=1`; OCRA explicit report plus `roboORecuperacion=1`; Aviso `ID_MOVIMIENTO=1/3`; CARFAX `data.robo=true` | PGJ recovered/delivered states, OCRA recovered, cancelled/historical Aviso states, CARFAX `false`, REPUVE data and unmapped RAPI crime status do not qualify automatically. |
| `nmvtis_plus` | Exact `otherInformation[].event = Active Theft` | Exact `Recovered Theft` is historical. Liens, title brands, junk/salvage/total-loss, odometer and other report severity/warnings do not qualify by themselves. |

Unknown, unavailable and unmapped payload forms are `INDETERMINATE`; they fail closed for notification-case qualification.

## Tests and assertions

- Contract fixture unit tests: active, historical, warning, clear and unknown payloads for both services.
- Notification-case integration: `Recovered Theft` produces no case, event or case outbox intent; `Active Theft` produces the normal case, audit event and Portal `CASE_CREATED` intent.
- Relevant consultation, history, report, authorization and notification tests passed.
- Full suite: **130 tests, 605 assertions, 0 failures**.
- Pint affected files: PASS.
- `git diff --check`: PASS.
- Owner local visual validation: ACCEPTED.

## Constraints and production impact

- No external provider HTTP request was made by the tests.
- No provider credentials, wallet behavior, report generation, notification workflow, migration, schema or existing consultation data was modified. The Owner separately authorized and performed the staging artifact upload/extraction in `dev.vintrack.com.mx` / `public_html_dev`, preserving `.env` and `storage/`.
- Historical consultations are not rewritten. Their raw responses remain unchanged.
- The authorized staging deployment and manual validation are recorded below and in `docs/production/deployment/CR-004-STAGING-ARTIFACT-MANIFEST.md`. This does not authorize production changes.

## Staging validation evidence — 2026-08-17

- Artifact: `vintrack-staging-cr004-c23d662-20260817-windows.zip`, SHA-256 `df55e505fd370f4ace987d4e165ea9937678ecc792ca2eae88006dda76676b72`.
- Owner confirmed complete local Windows Explorer extraction before upload; remote upload transferred the expected `66,835,751` bytes.
- Owner extracted the archive into `public_html_dev`; `.env` and `storage/` were preserved. cPanel reported a backslash-separator warning, then the Owner confirmed expected source paths exist as real paths and no literal backslash-named object exists.
- Administrator login and Global History were functional; applicable rows exposed `Ver` in `Acciones`.
- An existing Placas report with `robo = No` did not show a red theft alert.
- Existing VinData reports showed non-qualifying historical/warning data without creating a notification case or a new notification. `Active Theft` remained visibly serious; `Open Lien` remained a warning.
- Owner reported no HTTP 500 or visible runtime failure attributable to the release, and performed no new vehicle consultation, provider request, SMTP, Cron, Artisan or SQL operation.

### Accepted staging observations — not corrected in CR-004

1. **OBS-CR004-STG-01 — Historical CARFAX technical message exposed.** Existing report #32 displays `Error: Sí` and `Cannot read properties of null (reading 'statusCode')` from persisted CARFAX data. This is not an HTTP 500 and does not change the CR-004 assessment or notification outcome, but it is a future presentation/log-sanitization hardening item.
2. **OBS-CR004-STG-02 — Notification Process mojibake.** The staging Notification Process page displays malformed UTF-8 labels such as `VehÃ­culo` and `EnvÃ­o / actualizaciÃ³n`. This is outside CR-004 and requires separate encoding diagnosis before correction.

## Files added or modified for CR-004

- `app/Domain/Consultas/Services/ProviderResultAssessor.php`
- `app/Domain/Consultas/ValueObjects/ProviderResultAssessment.php`
- `app/Domain/Consultas/ValueObjects/ConsultationRequest.php`
- `app/Domain/Consultas/ValueObjects/ConsultationResponse.php`
- `app/Domain/Consultas/Entities/Consultation.php`
- `app/Application/Consultas/Services/ConsultationService.php`
- `app/Application/Consultas/Services/ConsultationOperationService.php`
- `app/Infrastructure/External/Providers/Placas/PlacasProviderAdapter.php`
- `app/Infrastructure/External/Providers/VinData/VinDataProviderAdapter.php`
- `app/Presentation/Http/Controllers/Web/ConsultationController.php`
- `tests/Fixtures/ProviderResults/`
- `tests/Unit/Consultas/ProviderResultAssessorTest.php`
- `tests/Feature/Consultations/ConsultationTest.php`
- `tests/Feature/NotificationCases/NotificationCaseCoreTest.php`

LOCAL IMPLEMENTATION CLOSED — DEPLOYMENT AUTHORIZATION REQUIRED
