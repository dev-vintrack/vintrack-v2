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

## DEC-044 — SPRINT-08 Governance Closure

**Estado:** APPROVED WITH OBSERVATIONS
**Aprobado por:** Project Owner
**Fecha:** 2026-08-14

SPRINT-08 — Hardening & Production Readiness queda formalmente cerrado como `APPROVED WITH OBSERVATIONS`, incluidos sus dos Targeted Remediation Passes. Production Readiness queda aprobado como `READY WITH CONDITIONS`. Production Authorization permanece `NOT AUTHORIZED` y Deployment `NOT EXECUTED`.

Observaciones aceptadas:

1. `OBS-08-01`: PG-05 permanece `OPEN — OWNER RISK/ARCHITECTURE DECISION REQUIRED`. MIME y SHA-256 no equivalen a malware scanning. Cuarentena + scanner verificable queda recomendada para decisión posterior; este cierre no instala software o servicios.
2. `OBS-08-02`: PG-18 permanece OPEN. DataTables CDN se acepta como baseline; CDN, CSP, SRI o distribución local requieren decisión explícita antes de producción. Este cierre no modifica frontend.
3. `OBS-08-03`: PG-01, PG-06, PG-08–PG-17 y PG-20 permanecen `BLOCKED_EXTERNAL`. No se consideran verificados MariaDB, Neubox, cPanel Cron, SMTP, DNS ni configuración productiva.
4. `OBS-08-04`: el RESULT se armoniza documentalmente distinguiendo estado inicial y estado final después de remediation, sin eliminar la historia.

Quedan `CLOSED` con evidencia local reproducible: PG-02 idempotencia request→consultation (`1/1/1/1` same key; `2/2/2/2` different keys), PG-03 performance/EXPLAIN sobre dataset 200/100,000/10,000/10,000 sin escenarios medidos >5 s, PG-04 estrategia de migrations (bootstrap 43 tablas/53 FK/54 migrations y forward-only + restore/forward-fix), PG-07 backup/restore `VERIFIED LOCALLY` y PG-21 secrets/log sanitization `CLOSED LOCALLY`.

La evidencia de performance es local y no constituye SLA productivo. `READY WITH CONDITIONS` no significa Ready for Deployment ni autoriza producción. DEC-036 permanece como roadmap canónico y DEC-043 como cierre de SPRINT-07. No se crea SPRINT-09. El siguiente paso posible es `Production Environment Verification Gate`, exclusivamente bajo autorización posterior del Project Owner. **APPROVED WITH OBSERVATIONS**

## DEC-045 — Malware Scanning Architecture for Evidence

**Estado:** APPROVED
**Aprobado por:** Project Owner
**Fecha:** 2026-08-14

La arquitectura contractual aprobada para PG-05 es **QUARANTINE-FIRST + MALWARE SCANNING + FAIL-CLOSED**.

Reglas contractuales:

1. Toda nueva evidencia comienza en cuarentena privada. Un upload exitoso no equivale a evidencia limpia.
2. Los estados mínimos equivalentes son `PENDING`, `SCANNING`, `CLEAN`, `INFECTED` y `ERROR`.
3. Sólo `CLEAN` puede usarse normalmente. `PENDING`, `SCANNING`, `ERROR` e `INFECTED` permanecen bloqueados.
4. Un fallo del scanner nunca equivale a `CLEAN`. Los documentos `INFECTED` no se eliminan automáticamente.
5. `SUBMIT` y `RESUBMIT` se bloquean server-side si existe evidencia activa que no esté `CLEAN`; el servidor es la autoridad.
6. El scanner debe exponerse detrás de una abstracción de Application/Domain; Infrastructure contiene el adapter del provider.
7. Ningún provider específico está aprobado o seleccionado todavía.
8. SHA-256 puede apoyar cache/deduplicación de resultados, pero nunca produce confianza permanente por sí solo.
9. Los retries son acotados y todo scanning debe quedar auditado.
10. SPRINT-03 conserva la autoridad contractual de storage y authorization: storage privado, sin URLs públicas y sin bypass del agregado Evidence.
11. No se usarán servicios públicos que compartan muestras de evidencia.

PG-05 cambia de `OPEN — OWNER RISK/ARCHITECTURE DECISION REQUIRED` a `DECIDED — IMPLEMENTATION REQUIRED BEFORE PRODUCTION`. No queda CLOSED. Para cerrarlo todavía se requieren implementación, tests, selección/aprobación de provider y verificación de integración productiva.

Esta decisión es exclusivamente arquitectónica/documental: no implementa scanner, no selecciona provider, no autoriza uploads productivos ni inicia Production Environment Verification. Production Readiness permanece `READY WITH CONDITIONS`; Production Authorization `NOT AUTHORIZED`; Deployment `NOT EXECUTED`. **APPROVED**

## DEC-046 — Frontend Critical Assets Self-Hosting and CSP Strategy

**Estado:** APPROVED
**Aprobado por:** Project Owner
**Fecha:** 2026-08-14

La arquitectura contractual aprobada para PG-18 es **SELF-HOST CRITICAL FRONTEND ASSETS**.

Reglas contractuales:

1. DataTables y las extensiones realmente utilizadas por VINTrack deben formar parte del artifact versionado de producción. No se dependerá de `cdn.datatables.net` durante runtime productivo.
2. La migración inicial conserva la versión exacta actualmente utilizada y probada: DataTables `1.13.6`. PG-18 no autoriza actualizarla. Cualquier upgrade futuro requiere decisión independiente y regresión propia.
3. Antes de implementar se hará inventario completo de recursos frontend externos, incluyendo como mínimo DataTables, jQuery, Bootstrap, icon libraries, fonts, JavaScript y CSS externos. Eliminar sólo el CDN de DataTables no se considera suficiente para CSP.
4. Los assets críticos self-hosted deben estar versionados, formar parte del artifact, tener versión explícita o package lock equivalente, poder construirse/empacarse localmente, funcionar sin acceso al CDN y no requerir Node/NPM/Composer frontend en producción.
5. No existirá fallback silencioso al CDN.
6. La futura CSP partirá del inventario real, minimizará origins externos, preferirá `'self'` para scripts/styles y evitará `unsafe-inline`/`unsafe-eval` cuando sea razonablemente posible. Toda excepción necesaria deberá documentarse. No se fija todavía una CSP productiva sin inventario completo.
7. Para recursos externos deliberadamente conservados se evaluará/exigirá SRI cuando sea técnicamente aplicable. Para assets locales, la integridad se controla mediante source control, commit aprobado, build reproducible, manifest/hash del artifact y verificación de deployment; SRI no es el control principal.

PG-18 cambia de `OPEN` a `DECIDED — LOCAL ASSET MIGRATION REQUIRED BEFORE PRODUCTION`. No queda CLOSED. Para cerrarlo todavía se requieren inventario frontend, migración local aprobada, cero dependencia CDN crítica no aprobada, regresión verde, smoke test sin CDN y assessment CSP actualizado.

Esta decisión es exclusivamente arquitectónica/documental: no modifica frontend, views o packages; no descarga assets; no actualiza DataTables; no implementa PG-18 ni inicia Production Environment Verification. Production Readiness permanece `READY WITH CONDITIONS`; Production Authorization `NOT AUTHORIZED`; Deployment `NOT EXECUTED`. **APPROVED**

## DEC-047 — Local Production Hardening Gate PG-05 + PG-18 Governance Closure

**Estado:** APPROVED WITH OBSERVATIONS
**Aprobado por:** Project Owner
**Fecha:** 2026-08-14

El Local Production Hardening Gate PG-05 + PG-18 queda formalmente cerrado como `APPROVED WITH OBSERVATIONS`.

1. `OBS-LPH-01 — Malware Provider Pending`: PG-05 queda `IMPLEMENTED LOCALLY — PROVIDER SELECTION AND PRODUCTION VERIFICATION REQUIRED` y no CLOSED. Quedan pendientes selección/aprobación del provider real, revisión de privacidad/retención/contrato, adapter real, tests del adapter, integración autorizada y verificación productiva. `FakeMalwareScanner` demuestra el workflow VINTrack, no scanning productivo.
2. `OBS-LPH-02 — Production CSP Pending`: PG-18 queda `CLOSED LOCALLY` respecto de inventario, assets críticos self-hosted, eliminación de dependencias CDN críticas en runtime y verificación offline. CSP productiva no está activada; scripts/styles inline y verificación de artifact/hash en el entorno objetivo permanecen pendientes.
3. `OBS-LPH-03 — Laravel Welcome Scaffold`: el scaffold `welcome` no utilizado conserva Bunny Fonts. Antes de CSP productiva deberá excluirse de routing/artifact productivo o eliminarse/limpiarse. Este cierre no lo modifica.

Para PG-05 se acepta quarantine-first, estados persistentes `PENDING/SCANNING/CLEAN/INFECTED/ERROR`, fail-closed, descarga y submit/resubmit sólo con CLEAN, retención aislada de INFECTED, retry acotado, stale-claim recovery, comando discreto, abstracción `MalwareScanner` y fake/test únicamente. Con dos processors sobre el mismo documento: scanner calls=1, persisted attempts=1 y estado final=CLEAN.

Para PG-18 se acepta el baseline self-hosted jQuery 3.7.1, Bootstrap 5.3.2, Bootstrap Icons 1.11.2, DataTables 1.13.6, Buttons 2.4.2, Responsive 2.5.0, JSZip 3.10.1, pdfmake 0.2.7 y DataTables Spanish 1.13.6. No hubo upgrade ni fallback CDN; se retiraron los orígenes críticos aprobados y la verificación offline quedó aceptada.

Baseline de calidad: 112 tests/496 assertions/0 failures. Final: 116/545/0. Migration up/down/up, Pint afectados y `git diff --check`: PASS.

DEC-044 permanece cierre de SPRINT-08; DEC-045 y DEC-046 conservan autoridad sobre PG-05 y PG-18. Production Readiness permanece `READY WITH CONDITIONS`; Production Authorization `NOT AUTHORIZED`; Deployment `NOT EXECUTED`; Production Environment Verification `NOT STARTED`. Ningún Gate `BLOCKED_EXTERNAL` se cierra. No se crea SPRINT-09. El siguiente paso es `Production Environment Verification Gate — PENDING OWNER AUTHORIZATION`. **APPROVED WITH OBSERVATIONS**

## DEC-048 — PG-01 Production Schema Reconciliation and MariaDB Deployment Rehearsal Closure

**Estado:** APPROVED / CLOSED — VERIFIED FOR SCHEMA COMPATIBILITY
**Aprobado por:** Project Owner
**Fecha:** 2026-08-14

PG-01 queda formalmente cerrado como `CLOSED — VERIFIED FOR SCHEMA COMPATIBILITY`.

Se acepta el rehearsal local, aislado y desechable sobre `10.6.27-MariaDB` usando el ZIP oficial Windows x64 con SHA-256 oficial/local coincidente `cc18bc6a0d42df6990ed91d1d7695e893b15168779272df43adf45dd75705a50`. El baseline productivo estructural fue 31 tablas, 11 migrations y batch máximo 2. Los seis archivos SQL aprobados conservaron sus hashes y pasaron en orden. El target resultó en 44 tablas, 462 columnas, 147 índices, 54 FKs, cero objetos faltantes y ocho defaults `notification_case_*` correctos.

El ledger final contiene 55 migrations únicas: 34 reconciliaciones `APPLIED_UNREGISTERED` en batch 3 y 10 migrations de deployment en batch 4. La historia anterior permanece `FORWARD-ONLY`; recuperación se realiza mediante backup/restore o forward-fix, no mediante una supuesta reversibilidad histórica.

La compatibilidad Laravel read-only pasó para consultations/history, notification cases, Evidence/scans, events, outbox, portal notifications y consultation operations. La segunda pasada confirmó `APPLY-ONCE BUNDLE WITH STRICT PRECHECK`: PRECHECK rechazó el baseline actualizado, POSTCHECK pasó y 02–05 no se repitieron.

Duración con filas productivas, metadata locks, backfill de `normalized_value`, construcción de índices, espacio temporal/disco, tráfico concurrente y comportamiento Linux/Neubox se clasifican como `DEPLOYMENT OPERATIONAL RISKS`; no reabren PG-01.

Production Readiness permanece `READY WITH CONDITIONS`; Production Authorization `NOT AUTHORIZED`; Deployment `NOT EXECUTED`. Esta decisión no autoriza SQL, phpMyAdmin ni deployment productivo. **APPROVED**

## DEC-049 — Database Promotion Strategy: Migrate Staging → Validate → Clone to Production

**Estado:** APPROVED — DOCUMENTAL ONLY
**Aprobado por:** Project Owner
**Fecha:** 2026-08-14

`vintrack_system_db` es la base legacy de 11 tablas usada exclusivamente por el PHP legacy de `vintrack.com.mx`; queda fuera del proyecto Laravel, del bundle 01–06 y de cualquier migration/schema delta. Debe permanecer intacta como rollback del legacy.

`vintrack_dev` es la base Laravel de staging de 31 tablas usada por `dev.vintrack.com.mx`. Es el baseline canónico del bundle 01–06 y de los rehearsals PG-01: el bundle transforma **31 → 44 tablas** con datos existentes preservados. No transforma `vintrack_system_db`.

El modelo aprobado es: aplicar el bundle en `vintrack_dev`; validar el target Laravel de 44 tablas en staging; y sólo después crear `vintrack_app` mediante clon completo de schema, datos, índices, constraints, migration ledger y configuración/referencia requerida desde el staging validado. `vintrack_app` no se crea vacía, no se inicializa desde legacy y no se crea antes de la validación salvo nueva decisión explícita.

No existe tráfico de clientes ni sincronización incremental requerida durante esta transición controlada; no se autorizan CDC, replicación, dual-write, merge ni limpieza de datos. La recuperación continúa siendo backup/restore o forward-fix, pues el bundle es forward-only. PG-01 permanece `CLOSED — VERIFIED FOR SCHEMA COMPATIBILITY`; PG-06 permanece `PARTIALLY VERIFIED — FINAL LAYOUT/STORAGE VERIFICATION REQUIRED`.

Esta decisión no autoriza SQL, bundle 01–06, backups, clones, creación de `vintrack_app`, cambios de hosting, Document Root ni deployment. Production Readiness permanece `READY WITH CONDITIONS`; Production Authorization `NOT AUTHORIZED`; Deployment `NOT EXECUTED`. **APPROVED**

## DEC-050 — Provider Result Assessment and Notification Qualification

**Estado:** APPROVED — IMPLEMENTED LOCALLY — OWNER LOCAL VALIDATION ACCEPTED — STAGING DEPLOYED AND VALIDATED WITH OBSERVATIONS — PRODUCTION RELEASE NOT AUTHORIZED
**Aprobado por:** Project Owner
**Fecha:** 2026-08-17

La calificación de resultados de proveedor se decide mediante una evaluación normalizada y explícita por el inmutable `provider_services.service_code`; no depende de `provider_service_id`, `key` o `name` como contrato de negocio. La evaluación distingue, como mínimo, `ACTIVE_QUALIFYING`, `HISTORICAL_RECORD`, `NON_QUALIFYING_WARNING`, `CLEAR` e `INDETERMINATE`.

Sólo `ACTIVE_QUALIFYING`, sustentado por un predicado específico y documentado del proveedor para robo/theft o fraude actual, puede proyectarse como `alerta_robo`, crear/reutilizar `notification_cases` y causar las intenciones de entrega Portal/Email derivadas del expediente. Los avisos históricos, de seguridad, gravamen, daño, odómetro, marca, recall o severidad visual no originan por sí mismos un expediente ni una notificación de caso.

Para `placas_service`, la interpretación debe usar los campos/estados fuente-específicos de PlacasInfo; no se autoriza inferir robo mediante búsqueda genérica de palabras en el JSON. Para `nmvtis_plus`, `Active Theft` es potencialmente calificable y `Recovered Theft` es histórico no calificable; el resumen de color, lien, recall, towing/impound, odómetro, title-brand y junk/salvage/total-loss no califican por sí solos. Un resultado de fraude exige una señal actual, explícita y documentada por el proveedor; no se infiere de marcas o antecedentes.

La respuesta cruda del proveedor se conserva como evidencia y la implementación debe guardar una instantánea normalizada y auditable de su evaluación. No se reescribe automáticamente el historial. Fallos, falta de datos o variantes de payload desconocidas son `INDETERMINATE` para calificación, nunca `CLEAR`; la integración debe fallar cerrada respecto de la creación del expediente.

La implementación debe seguir la arquitectura Application/Domain/Infrastructure existente, usar fixtures de contrato versionados, preservar idempotencia/concurrencia, autorización, la regla de 90 días y el outbox de Portal/Email independiente. La implementación local fue realizada con fixtures sin llamadas facturables, sin schema, migrations, cambios de datos, Cron o SMTP. El Owner aceptó la validación local y, mediante autorización separada, validó en staging el artifact de runtime `c23d662` el 2026-08-17 sin consultas nuevas, provider calls, SMTP, Cron, Artisan o SQL. La evidencia de staging acepta dos observaciones no corregidas: mensaje técnico histórico de CARFAX visible en un reporte y mojibake de codificación en Proceso de Notificaciones. Ninguna cambia la calificación CR-004 ni autoriza producción. **APPROVED**

## DEC-051 — Cloudmersive Provider Selection for PG-05

**Estado:** APPROVED — LOCAL INTEGRATION IMPLEMENTED — EXTERNAL VERIFICATION PENDING
**Aprobado por:** Project Owner
**Fecha:** 2026-08-17

Cloudmersive Virus Scan API is the selected PG-05 provider. VINTrack uses `POST /virus/scan/file/advanced` behind `MalwareScanner`; it sends only the quarantined private file stream and maps `CleanResult=true` exclusively to `CLEAN`. All other result classes remain blocked. The adapter applies content restrictions for `.pdf,.jpg,.jpeg,.png` and disables executables, invalid files, scripts, encrypted files, macros, XXE, HTML, unsafe archives, OLE objects and unwanted actions.

The API key is an environment secret, never source-controlled or logged. The approved subscription constraint is 600 calls/month, 1 call/second and 3.5 MB/file; VINTrack retains its existing 3 MB business limit and bounded processor. HTTP 429/5xx and connectivity failures are retryable; authentication/configuration/client errors are fail-closed.

Pending external evidence: provider privacy/DPA/contract, retention, confirmation that uploaded Evidence is not reused/shared, and exact North America processing conditions. Until approved, production activation and any real Evidence upload to Cloudmersive remain unauthorized. Neubox HTTPS connectivity and cPanel Cron verification are separately pending execution in the target environment. **APPROVED**

## DEC-052 — PlacasInfo Payload Normalization and Result Priority

**Estado:** APPROVED — IMPLEMENTED LOCALLY — OWNER LOCAL VISUAL VALIDATION ACCEPTED — STAGING NOT AUTHORIZED
**Aprobado por:** Project Owner
**Fecha:** 2026-08-19

For immutable `placas_service`, PGJ and Aviso payload sections may be object, list, documented `XCURSOR` wrapper, empty, or recognized provider-error shape. The evaluator must normalize all record-bearing forms and inspect every record. A current source-specific predicate has priority for qualification: PGJ `ID_ESTATUS_VHI_ROBO=1`; OCRA `conReporteRoboRecuperacion=true` plus `reporte.roboORecuperacion=1`; Aviso `ID_MOVIMIENTO` 1 or 3; RAPI `tiene_delito=true` plus `estado_vehiculo` `PROCEDENCIA ILICITA` or `ROBADO`; CARFAX `data.robo=true`.

Historical predicates remain non-qualifying: PGJ 4/12, OCRA 2, Aviso 0/2, RAPI `RECUPERADO`/`ENTREGADO`, and REPUVE `TIPO_MOVIMIENTO=2`. Recognized source errors are `INDETERMINATE` when no current/historical authoritative predicate is available; no unavailable/error source may be classified `CLEAR`. An active predicate remains qualifying even if another source carries historical or unavailable evidence; all supplied evidence remains preserved in the auditable assessment snapshot. No historical consultation is rewritten.

Owner local visual validation was confirmed positive on 2026-08-20. Staging and production remain unauthorized.
