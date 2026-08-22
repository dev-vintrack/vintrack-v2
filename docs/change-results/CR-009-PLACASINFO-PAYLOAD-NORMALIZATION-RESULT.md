# CR-009 — PlacasInfo Payload Normalization — Result

**Status:** IMPLEMENTED LOCALLY — OWNER LOCAL VISUAL VALIDATION ACCEPTED — STAGING NOT AUTHORIZED
**Date:** 2026-08-19

## Root cause

`consultations.id=64` preserved PGJ as an object and correctly became `ACTIVE_QUALIFYING`; `id=65` preserved the same documented current PGJ theft as a one-item list. The prior evaluator only read the object path, ignored the list entry and therefore used only OCRA's recovery predicate, producing a false negative.

## Correction

- PGJ and Aviso now normalize object, list, `XCURSOR` wrapper and `data.XCURSOR` wrapper forms before evaluating every record.
- Recognized provider error shapes become `INDETERMINATE` rather than `CLEAR` when no decisive source result exists.
- RAPI current/historical states and REPUVE BAJA are evaluated according to the supplied PlacasInfo guide.
- Current alert predicates take classification priority over concurrent historical/unavailable evidence, while the complete evidence remains in the assessment snapshot.
- Assessment version is `cr-004-v2` for new consultations only. Historical rows are untouched.

## Tests

- Production-derived fixture from consultation 64: PGJ object + OCRA recovered → active/qualifying, `pgj_robo=1`.
- Production-derived fixture from consultation 65: PGJ list + OCRA recovered → active/qualifying, `pgj_robo=1`.
- PGJ/OCRA/Aviso recognized provider error variants → `INDETERMINATE`.
- Existing Placas/NMVTIS and notification-case regressions remain covered.
- Focused regression: **30 tests, 142 assertions, 0 failures**.
- Full local regression: **142 tests, 651 assertions, 0 failures**.
- Pint and `git diff --check`: PASS.

## Scope

No provider call, database operation, migration, schema/data rewrite, wallet change, case-state change, outbox, Cron, SMTP, staging, or production action was performed.

READY FOR OWNER REVIEW

## Owner local visual validation — accepted

On 2026-08-20 the Owner confirmed positive local visual validation. No provider call, database operation, staging action or production action was performed by this validation.
