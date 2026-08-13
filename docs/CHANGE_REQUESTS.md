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
