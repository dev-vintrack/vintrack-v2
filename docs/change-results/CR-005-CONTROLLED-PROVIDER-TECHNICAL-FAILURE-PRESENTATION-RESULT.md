# CR-005 — Controlled Provider Technical-Failure Presentation — Result

**Status:** IMPLEMENTED LOCALLY — OWNER LOCAL VISUAL VALIDATION ACCEPTED — STAGING NOT AUTHORIZED  
**Date:** 2026-08-17  
**Authorization:** Explicit Project Owner approval for local implementation and validation only.

## Root cause and correction

The report renderer flattened persisted provider response fields and displayed every value verbatim. A historical CARFAX payload therefore exposed the provider-side implementation message `Cannot read properties of null (reading 'statusCode')` to an authorized report consumer.

`PlacasReportPresenter` now detects only bounded implementation-failure signatures: the evidenced null/undefined-property message plus common typed/reference/syntax exception, stack-trace and unhandled-exception signatures. It renders the stable row `Estado del proveedor: Proveedor temporalmente no disponible.` instead of the raw implementation detail.

The raw consultation response remains untouched in `consultations.response_json`. Legitimate provider business findings, including a normal `robo = false` finding, continue to be flattened and shown as provider evidence.

## Authorization and workflow invariants

- The existing `/reports/{consultation}` authorization remains authoritative: clients are scoped to their own consultations and administrators retain their existing authorized access.
- This is report presentation only. It does not call a provider or change provider adapters, report generation, qualification, `alerta_robo`, wallet behavior, notification-case admission, outbox behavior, schema, migrations or historical data.
- The controlled technical status is not an alert row and cannot cause a notification case or Portal/Email intent.

## Automated evidence

- New unit coverage verifies a recognized technical signature is masked and a legitimate business finding remains visible.
- Feature coverage verifies an authorized client receives the controlled status, does not receive the raw exception or theft banner, and no notification case/outbox row is created.
- Existing client ownership denial, administrator report access, missing-report handling and notification authorization regressions remain covered.
- Focused regression: **14 tests, 100 assertions, 0 failures**.
- Full regression: **133 tests, 621 assertions, 0 failures**.
- Pint on affected PHP: PASS.
- `git diff --check`: PASS.

## Owner local visual validation — accepted

On 2026-08-17 the Owner confirmed a positive authenticated local validation of CR-005. The validation did not make a vehicle consultation or invoke a provider, SMTP, Cron, Artisan or SQL operation. The next possible environment step is staging, which remains separately unauthorized.

## Production impact

None. Staging upload/activation and production deployment are not authorized by this result. Production Authorization remains `NOT AUTHORIZED`; Deployment remains `NOT EXECUTED`.

READY FOR OWNER REVIEW
