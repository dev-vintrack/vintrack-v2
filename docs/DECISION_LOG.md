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

## DEC-037 — SPRINT-03 Governance Closure and Canonical Roadmap Confirmation

**Estado:** APPROVED WITH OBSERVATIONS
**Aprobado por:** Project Owner
**Fecha:** 2026-08-13

SPRINT-03 — Evidence & Secure File Management queda cerrado como `APPROVED WITH OBSERVATIONS` sin reabrir ni modificar su implementación.

DEC-035 conserva exclusivamente su significado histórico como cierre de SPRINT-02 y no debe renumerarse, sobrescribirse ni reutilizarse. **DEC-036 queda confirmado como identificador canónico y oficial del roadmap posterior a SPRINT-02.** La referencia a DEC-035 en el rector histórico de SPRINT-03 es un error documental histórico y no se modifica para ocultarlo.

Observaciones y Production Gates de SPRINT-03:

1. Ausencia actual de scanner antivirus/antimalware aceptada como riesgo residual; no se autoriza instalar servicios, SaaS o scanner en este cierre.
2. Antes de producción deben verificarse en Neubox fileinfo, GD, ruta privada efectiva, permisos de lectura/escritura, inaccesibilidad HTTP directa y streaming/download.
3. MariaDB 10.6.27 real permanece pendiente antes de producción.

Estos gates se acumulan con OBS-02-01 a OBS-02-06. Producción permanece `NOT AUTHORIZED`. SPRINT-04 — Client Portal — Notification Process queda previsto por DEC-036, pero requiere autorización explícita separada del Project Owner. **APPROVED WITH OBSERVATIONS**

## DEC-038 — Recovered-at Future Date Rule

**Estado:** APPROVED / IMPLEMENTED LOCALLY IN SPRINT-04
**Aprobado por:** Project Owner
**Fecha:** 2026-08-13

`recovered_at` debe ser menor o igual al datetime empresarial actual. No se permiten fechas u horas futuras. La timezone contractual es `America/Mexico_City`.

La regla se valida obligatoriamente server-side dentro del servicio de ciclo de vida del expediente, tanto al guardar el borrador como al enviar. La restricción del navegador es exclusivamente una mejora UX y no constituye autoridad ni control de seguridad. **APPROVED**

## DEC-039 — SPRINT-04 Governance Closure

**Estado:** APPROVED WITH OBSERVATIONS
**Aprobado por:** Project Owner
**Fecha:** 2026-08-13

SPRINT-04 — Client Portal — Notification Process queda cerrado como `APPROVED WITH OBSERVATIONS` sin reabrir ni modificar su implementación.

Observaciones aceptadas:

1. `OBS-04-01`: se acepta la migration reversible de datos del menú por ser necesaria para integrar el Portal Cliente con el sistema persistente de menús; no cambia schema, no requiere Change Request y deberá incluirse en el futuro procedimiento de deployment.
2. `OBS-04-02`: la prueba real de doble submit cierra esa parte de `OBS-02-02`; demostró una transición, un evento, un outbox, estado final `SUBMITTED` y `lock_version` consistente. El mensaje del segundo proceso se acepta; un código como `CASE_ALREADY_SUBMITTED` queda como mejora futura no bloqueante.
3. `OBS-04-03`: permanecen pendientes los Production Gates acumulados: MariaDB 10.6.27; idempotencia end-to-end request → consulta; doble asignación VIN; validación versus auto-close; deadlock/retry; índices/EXPLAIN con volumen representativo; deuda histórica de migrations; malware scanning; storage/fileinfo/GD/permisos reales en Neubox; y autorización separada de producción.
4. `OBS-04-04`: SPRINT-04 no expone una operación general para modificar VIN. VIN permanece inmutable en la experiencia normal; los casos por placa sin VIN y su conciliación deberán considerarse explícitamente en un flujo administrativo/follow-up posterior respetando la asignación única existente.

DEC-036 permanece como roadmap canónico, DEC-037 conserva el cierre de SPRINT-03 y DEC-038 conserva la regla contractual de `recovered_at`. Producción y SPRINT-05 permanecen no autorizados hasta instrucción explícita separada del Project Owner. **APPROVED WITH OBSERVATIONS**

## DEC-040 — Mandatory Administrative Rejection Reason

**Estado:** APPROVED / IMPLEMENTED LOCALLY IN SPRINT-05
**Aprobado por:** Project Owner
**Fecha:** 2026-08-13

Toda transición administrativa a `REJECTED` requiere un motivo textual no vacío, validado y normalizado server-side conforme a la política textual contractual. El motivo queda asociado inequívocamente al evento append-only `CASE_REJECTED` mediante `notification_case_events.reason`, con actor y timestamp; permanece recuperable en todos los ciclos posteriores y se muestra al Cliente para permitir corrección y reenvío.

La infraestructura existente de eventos es suficiente: `reason` es `TEXT`, la historia se ordena por expediente/fecha/id y no se sobrescribe. No se autoriza ni requiere una columna, tabla o migration de schema nueva para esta regla. La longitud máxima de entrada es 2000 caracteres y no existe truncamiento silencioso. **APPROVED**

## DEC-041 — SPRINT-05 Governance Closure

**Estado:** APPROVED WITH OBSERVATIONS
**Aprobado por:** Project Owner
**Fecha:** 2026-08-13

SPRINT-05 — Administrative Portal — Review & Validation queda cerrado como `APPROVED WITH OBSERVATIONS` sin reabrir ni modificar su implementación.

Observaciones aceptadas:

1. `OBS-05-01`: se acepta la migration reversible de datos del menú administrativo; no cambia schema, fue probada `up → rollback → up`, no requiere Change Request y deberá incorporarse al procedimiento posterior de deployment.
2. `OBS-05-02`: no existe capability administrativa aprobada para asignación excepcional de VIN y SPRINT-05 no la expone. La carrera técnica de doble asignación VIN queda cerrada localmente en MySQL. Exponer esa capability queda como follow-up separado. VIN normal y folio permanecen inmutables.
3. `OBS-05-03`: se acepta el timeline con nombres técnicos de eventos. Su humanización es una mejora UX futura no bloqueante que no altera la auditoría subyacente.
4. `OBS-05-04`: permanecen como Production Gates MariaDB 10.6.27 real; idempotencia end-to-end request → consulta; índices/EXPLAIN con volumen; deuda histórica de migrations; malware scanning; storage/fileinfo/GD/permisos Neubox; backup/rollback y autorización productiva explícita.

Quedan cerradas localmente y no deben volver a registrarse como pendientes salvo fallo posterior en MariaDB/producción las carreras: doble submit, doble asignación VIN, validate vs reject, validate vs auto-close y reject vs auto-close.

DEC-036 permanece como roadmap canónico, DEC-039 como cierre de SPRINT-04 y DEC-040 como regla contractual del motivo obligatorio de rechazo. Producción y SPRINT-06 permanecen no autorizados hasta instrucción explícita separada del Project Owner. **APPROVED WITH OBSERVATIONS**

## DEC-042 — SPRINT-06 Governance Closure

**Estado:** APPROVED WITH OBSERVATIONS
**Aprobado por:** Project Owner
**Fecha:** 2026-08-14

SPRINT-06 — Vehicle Consultation Histories + Server-side DataTables queda cerrado como `APPROVED WITH OBSERVATIONS`, sin reabrir ni modificar su implementación.

Observaciones aceptadas:

1. `OBS-06-01`: se acepta no crear índices especulativos con el volumen local. Arquitectura server-side, ausencia de N+1, `LIMIT/OFFSET`, filtros SQL y ordering allowlist quedan aceptados localmente. Repetir EXPLAIN con volumen representativo, validar selectividad/planes y crear índices sólo con evidencia permanece Production Gate.
2. `OBS-06-02`: se acepta la normalización actual de placas para separadores persistidos comunes. No se infiere ni fabrica identidad ausente. Formatos históricos adicionales sólo se evaluarán con evidencia real futura.
3. `OBS-06-03`: se acepta DataTables 1.13.6 mediante el CDN del baseline existente. Disponibilidad, CSP y dependencias externas se revisarán antes de producción si la política técnica lo requiere.
4. `OBS-06-04`: permanecen como Production Gates MariaDB 10.6.27 real; idempotencia end-to-end request → consulta; índices/EXPLAIN con volumen representativo; deuda histórica de migrations; malware scanning; storage/fileinfo/GD/permisos Neubox; backup/rollback y autorización productiva explícita.

Quedan formalmente aceptados ambos historiales, DataTables server-side, paginación/búsqueda/filtros/ordering server-side, filtro Cliente, scope autenticado, resolución histórica Consultation → Case, 90 días, múltiples cases, prevención retroactiva, ownership proyectado, `VIN_NOT_AVAILABLE`, estados derivados, deadline/primer submit, acciones server-side, IDOR, ausencia de N+1, regresión y tests documentados.

DEC-036 permanece como roadmap canónico y DEC-041 como cierre de SPRINT-05. Producción permanece `NOT AUTHORIZED`. SPRINT-07 queda `PENDING OWNER AUTHORIZATION` y no fue iniciado. **APPROVED WITH OBSERVATIONS**

## DEC-043 — SPRINT-07 Governance Closure

**Estado:** APPROVED WITH OBSERVATIONS
**Aprobado por:** Project Owner
**Fecha:** 2026-08-14

SPRINT-07 — Notifications, Outbox Delivery & Automation queda cerrado como
`APPROVED WITH OBSERVATIONS`, sin reabrir ni modificar su implementación.

Observaciones aceptadas:

1. `OBS-07-01`: el contrato SMTP es at-least-once con deduplicación interna,
   retries controlados, trazabilidad y minimización de duplicados. No se afirmará
   exactly-once externo sin garantía o idempotency key verificable del proveedor.
2. `OBS-07-02`: recipient inexistente o email inválido puede agotar actualmente
   el retry acotado. SPRINT-08 deberá evaluar clasificación determinística entre
   fallo SMTP temporal retryable y destinatario inválido non-retryable/skipped.
3. `OBS-07-03`: PHP CLI/cPanel/Artisan, working directory, frecuencia,
   timeout/overlap, logs/cache, URL productiva, SMTP/TLS/remitente,
   SPF/DKIM/DMARC, límites de correo y rebotes permanecen Production Gates.
4. `OBS-07-04`: se conserva la deuda histórica del rollback global en
   `2026_08_05_000002_drop_label_icon_from_menu_permissions_tables`; no pertenece
   a SPRINT-07. Los ciclos reversibles recientes quedan aceptados.

Quedan aceptados el processor del outbox único, canales Portal/Email
independientes, deduplicación, retry/backoff con máximo cinco intentos,
lease/claim, límites de lote/tiempo, Portal Notification Center, read/unread/count,
IDOR/XSS, Mail de pruebas sin attachments, recipient server-side, recordatorios,
auto-close por lifecycle, comandos Artisan discretos sin dependencia productiva
de Scheduler/daemon y la evidencia de concurrencia, tests y regresión.

DEC-036 permanece como roadmap canónico y DEC-042 como cierre de SPRINT-06.
Producción permanece `NOT AUTHORIZED`. SPRINT-08 requiere autorización explícita
separada del Project Owner. **APPROVED WITH OBSERVATIONS**
