# CR-004 — Provider Result Assessment Result

**Status:** APPROVED — IMPLEMENTED LOCALLY — OWNER LOCAL VALIDATION ACCEPTED — STAGING ARTIFACT PREPARED — OWNER RELEASE REVIEW PENDING
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
- No provider credentials, wallet behavior, report generation, notification workflow, migration, schema, existing consultation data, staging or production environment was modified.
- Historical consultations are not rewritten. Their raw responses remain unchanged.
- This closes the local implementation and validation scope. A local staging candidate is prepared and recorded in `docs/production/deployment/CR-004-STAGING-ARTIFACT-MANIFEST.md`; it has not been uploaded or activated. It does not authorize a staging deployment or production changes.

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
