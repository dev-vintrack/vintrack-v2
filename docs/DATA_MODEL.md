# VINTrack — Data Model Governance

**Status:** APPROVED  
**Version:** 1.1
**Approval date:** 2026-08-17

## Existing Baseline
`consultations.provider_service_id` already exists locally and in production. Do not recreate it.

## Provider Result Assessment
The provider response remains the immutable consultation evidence. CR-004 preserves both the raw response and an auditable normalized assessment snapshot in existing `consultations.flags_json`. The snapshot contains the immutable `service_code`, mapping/assessment version, classification, qualifying predicate(s) and source evidence references; the related immutable `consultations.created_at` anchors the evaluation time. The approved minimum classifications are `ACTIVE_QUALIFYING`, `HISTORICAL_RECORD`, `NON_QUALIFYING_WARNING`, `CLEAR` and `INDETERMINATE`.

`alerta_robo` remains a compatibility/read-model projection only: it is true exclusively for `ACTIVE_QUALIFYING`. It must not be a lossy aggregate of unrelated provider warnings. The assessment does not duplicate the canonical consultation identity into `notification_cases`, and this documentation decision does not prescribe or authorize a new schema, migration or historical backfill.

## vehicles
`vehicles` is a consolidated/reporting structure and must NOT be the notification-expedient master because it lacks the required direct relationship to the user who performed the consultation.

## Required Data Areas
The implementation is expected to require dedicated structures for:
1. Notification expedient.
2. Evidence/document metadata.
3. Audit/history.
4. Configuration/reference values where appropriate.

## Expedient Data
The final design should support:
- expedient number;
- responsible user/customer;
- originating consultation;
- relevant vehicle/service identifiers;
- status;
- deadline;
- lifecycle dates;
- previous expedient reference where applicable;
- timestamps.

The case stores `consultation_id` and immutable owner `user_id`. It must not duplicate `source_criterion`, `source_value` or `provider_service_id`; those remain reachable through `consultations`. `vin`/`vin_key` are nullable only for a `placa`-originated draft without recoverable VIN, required before `SUBMITTED`, and immutable after first assignment. A provisional guard uses the related consultation's provider service + normalized PLATE + normalized plate value. VIN conflicts are represented by an auxiliary auditable reconciliation incident/flag, never a seventh case status.

## Evidence Data
Support:
- expedient relation;
- original filename;
- secure storage path/key;
- MIME/content type;
- size;
- upload metadata;
- timestamps.

## Audit Data
Support:
- actor;
- action;
- entity;
- entity identifier;
- previous/new state;
- reason/metadata;
- timestamp.

Include `CASE_VIN_ASSIGNED` for the idempotent first VIN assignment and reconciliation events. Pre-case events may have nullable case reference only under their explicit allowlist.

## Notification Delivery

SPRINT-07 reutiliza `notification_outbox` como única cola durable. Cada fila es
un canal (`PORTAL` o `EMAIL`) y su `dedup_key` es único. Los estados operativos
son `PENDING`, `PROCESSING`, `DELIVERED` y `FAILED`; `available_at`, intentos y
lease permiten retry acotado y recuperación de claims abandonados.

`portal_notifications.outbox_id` único materializa exactamente una proyección
local por mensaje y mantiene scope por `recipient_user_id` y read/unread.
`notification_deliveries.dedup_key` único registra el intento de correo
existente. La identidad del destinatario siempre se resuelve desde `users` en
servidor; el payload no es autoridad de routing.

## Configuration
The final design must support configurable business values such as the 90-day reuse window, 30-day maximum open period, 3-day deadline, maximum pending count and file limits.

## Integrity
Use InnoDB, utf8mb4, appropriate foreign keys and indexes. Design for efficient lookup by user/customer, consultation, VIN/reference, status and dates.

Functional temporal columns use `DATETIME` with contractual `America/Mexico_City` semantics; `notification_deadline_at` is `DATETIME(0)` and other timestamps may be `DATETIME(6)`. Do not rely on `TIMESTAMP`, database session timezone or browser conversion.

## Retention
Provisional targets: cases, documents and audit events 5 years; portal notifications 2 years. These targets do not authorize automatic purge, physical deletion or anonymization. Future disposal requires legal and Owner approval, auditability, idempotency and preservation of historical chains.

## Migration Safety
Migrations must be tested locally first, be reversible where practical, and be compatible with MySQL 8.4.3 and MariaDB 10.6.27.

## Important
Do not invent final foreign keys or table names until the actual application/database has been inspected during Discovery.
