# VINTrack — SPRINT-04: Client Portal — Notification Process — RESULT

**Fecha:** 2026-08-13
**Entorno:** local/testing
**Estado:** APPROVED WITH OBSERVATIONS

## 1. Executive Summary

Se implementó localmente el Portal Cliente para listar, abrir, capturar, corregir, adjuntar evidencia y enviar expedientes propios. La UI reutiliza el ciclo de vida de SPRINT-02 y Evidence de SPRINT-03. Suite final: **89 tests, 344 assertions, 0 fallos**. Producción no fue modificada.

## 2. Scope Completed

Menú, listado paginado, detalle responsive, guardado parcial, seis estados canónicos, fecha límite, normalización, regla IPH/NUC, regla temporal de recuperación, upload/list/download/remove, submit/resubmit, mensajes, autorización, IDOR, mass assignment, CSRF y doble submit real.

## 3. Explicit Exclusions

Sin Portal Administrativo, Historial de Vehículos Consultados, DataTables SPRINT-06, Notification Center/worker, delivery, emails, Cron/cPanel, auto-close programado, scanner, retroactivos, purga, deployment, producción o SPRINT-05.

## 4. Governance / Decisions

DEC-036 sigue siendo el roadmap canónico. DEC-037 conserva el cierre de SPRINT-03. Se registró **DEC-038** para `recovered_at <= current business datetime` en `America/Mexico_City`, server-side. PROJECT_STATE y AGENTS reflejan SPRINT-04 listo para revisión.

## 5. Baseline

Antes de cambios: Laravel 12.62.0, PHP 8.3.30, timezone efectiva `America/Mexico_City`; `php artisan test --no-ansi`: **82 passed, 295 assertions, 0 failed**, 7.88 s.

## 6. Preflight Findings

Git tenía cambios documentales del Owner en DECISION_LOG, PROJECT_STATE, SPRINT-03-RESULT y el rector no versionado; se preservaron. El esquema ya tenía todos los campos. El servicio de ciclo de vida no validaba futuro para `recovered_at`. Menús/capabilities son persistentes. Los documentos estaban protegidos por servicio, no por UI. Timezone efectiva correcta.

## 7. Client Portal Architecture

Controller web delgado + FormRequests + servicios existentes. Las reglas de estado, ownership, normalización, auditoría, outbox, evidencia y locking no fueron duplicadas en Blade/JavaScript.

## 8. Routes / Authorization

Cuatro rutas cliente: index, show, update y submit. Index usa `auth + active.customer + customer.menu`; detalle/mutaciones usan `auth + active.customer` y ownership server-side. Documentos reutilizan las cuatro rutas seguras SPRINT-03.

## 9. Notification Process Listing

Query estricta por `auth.user.id`, eager loading de consulta, conteo de documentos activos y paginación Laravel de 15. Presenta folio, VIN, vehículo, deadline, estado, actualización y acción. No implementa historial de consultas.

## 10. Form Implementation

Campos contractuales agrupados en una vista responsive Bootstrap. Folio/VIN son read-only y no se mapean desde payload. Estado es exclusivamente visual. PENDING/REJECTED permiten edición; los demás son read-only.

## 11. Snapshot / Defaults

La vista utiliza directamente el snapshot persistido del caso. No llama providers, no consume crédito y no consulta `vehicles`.

## 12. Validation Rules

FormRequest valida tipos, longitudes, año, formato temporal, versión y request key. Submit conserva la validación completa autoritativa de `NotificationCaseLifecycleService`.

## 13. IPH / NUC

Se mantienen separados. Submit acepta IPH solo, NUC solo o ambos y rechaza ambos vacíos mediante el servicio existente.

## 14. Text Normalization

El servicio SPRINT-02 persiste texto funcional en mayúsculas sin acentos/diéresis y espacios normalizados. La UI no es autoridad.

## 15. recovered_at Rule

Guardado y submit verifican contra `CarbonImmutable::now(NotificationCaseSettings::timezone())`. El FormRequest entrega feedback temprano y el input `datetime-local` tiene `max` UX. Se probaron pasado, instante actual/futuro inmediato y persistencia sin alteración ante rechazo.

## 16. Save/Edit

Guardado parcial usa allowlist, `lock_version`, request key idempotente, `FOR UPDATE`, normalización y auditoría `CASE_DRAFT_UPDATED` de SPRINT-02.

## 17. Evidence Integration

La UI invoca endpoints SPRINT-03 para upload/list/download/remove. Conserva private storage, MIME/extensión real, 3 MiB, ocho activos, SHA-256, soft removal, autorización e idempotencia. No expone keys/paths/hashes.

## 18. Submit / Resubmit

Controller delega a `NotificationCaseLifecycleService::submit`; no asigna status. Incluye confirmación explícita, deshabilita edición posterior y conserva mismo folio/deadline en REJECTED.

## 19. State / Capability Matrix

PENDING y REJECTED: edit/document/submit. SUBMITTED, UNDER_REVIEW, VALIDATED y CLOSED_NO_FOLLOW_UP: solo lectura para Cliente. La UI deriva del enum/servicio central y los endpoints rechazan operaciones forjadas.

## 20. Deadline Presentation

Usa `notification_deadline_at` persistido y lo muestra con segundos en la timezone empresarial. No recalcula en JavaScript.

## 21. UX / Accessibility

Menú contractual, prioridad visual con texto además de color, labels, indicators, mensajes, confirmación, layout responsive y controles con texto. Upload informa formatos, límites y conteo.

## 22. IDOR Protection

Listado nunca acepta owner del browser. Detalle ajeno devuelve 404; FormRequests de mutación ajena devuelven 403 antes de validar payload; Evidence conserva 403/404 y binding dentro del case.

## 23. Mass Assignment Protection

Payload de VIN, folio, owner, consultation, status, deadline y clocks se ignora/rechaza. Solo campos capturables de la allowlist llegan al lifecycle.

## 24. CSRF / HTTP

CSRF permanece activo. GET solo lee; PUT guarda; POST envía/carga; DELETE remueve lógicamente. Fetch documental envía token CSRF e Idempotency-Key.

## 25. Audit / Outbox Integration

Draft, submit/resubmit y documentos reutilizan auditoría existente. Submit genera outbox transaccional deduplicado; no existe delivery en este Sprint.

## 26. Tests Added

`CustomerNotificationCasePortalTest`: **7 tests / 49 assertions** sobre scope, menú/rol, IDOR, normalización, temporalidad, mass assignment, IPH/NUC, submit/read-only y seis estados. Tres scripts nuevos implementan prepare/worker/cleanup del doble submit MySQL.

## 27. Tests Executed

- Baseline: **82/295**, 0 fallos.
- Portal focalizado: **7/49**, 0 fallos, 1.28 s.
- Suite final: **89/344**, 0 fallos, 9.44 s.
- Pint inicial: detectó cuatro archivos nuevos sin formato; se corrigieron.
- Pint final sobre archivos PHP afectados: **passed**.
- `route:list`: cuatro rutas cliente registradas.
- `migrate:status`: migración de menú batch 13 `Ran` en MySQL local.

## 28. Real Double-submit Concurrency

MySQL 8.4.3 local, mismo case PENDING completo, dos procesos PHP y conexiones separados liberados por una barrera compartida:

- worker 1: `OK`, `SUBMITTED`, `lock_version=1`;
- worker 2: `DomainException`, resultado estable `No autorizado para enviar este expediente.`;
- final: `SUBMITTED`, `lock_version=1`;
- eventos submit: **1**;
- outbox: **1**;
- cleanup de datos aislados: `true`.

La parte doble-submit de **OBS-02-02 queda cerrada**. Las carreras de doble VIN, validación/auto-close y deadlock/retry permanecen Production Gates. Doble asignación VIN: `NOT APPLICABLE TO SPRINT-04`; el portal no expone esa operación.

## 29. Regression

Suite completa verde incluye admission, límite 3, 90 días, deadline, estados, documentos, carrera 7+2, IDOR, idempotencia, auditoría, outbox, wallet/provider y correo existente sin envío real.

## 30. Files Modified

Nuevos: controller cliente, dos FormRequests, dos vistas, migración de datos de menú, test portal, tres scripts de concurrencia y este RESULT. Modificados: lifecycle, enum de estado, rutas, AGENTS, DECISION_LOG y PROJECT_STATE. Los cambios documentales previos del Owner se preservaron.

## 31. Database Operations

Se ejecutó una migración local de datos del menú y un harness aislado sobre `vintrack_dev`; el harness eliminó sus filas. No se ejecutó SQL productivo, broad update/delete ni operación destructiva fuera del cleanup verificado.

## 32. Migrations

Una migración necesaria, sin cambio de schema: registra el menu item y permisos para roles cliente soportados. `down` elimina exclusivamente esa ruta. No modifica tablas/columnas de expedientes.

## 33. Limitations / Risks

Sin scanner malware (gate aceptado SPRINT-03). MariaDB/Neubox no probados. La asignación VIN excepcional no se expone en este portal por el contrato read-only de SPRINT-04. Los errores documentales JSON se presentan mediante alert UX sencilla; no existe Notification Center persistente.

## 34. Production Gates

Permanecen OBS-02-01, OBS-02-03 a 06; de OBS-02-02 quedan doble VIN, validate/auto-close y deadlock/retry; permanecen OBS-03-02 a 04. Se requiere backup, rollback, compatibilidad y autorización expresa antes de producción.

## 35. Requirement Traceability

DEC-038/recovered_at; DEC-032/campos e identidad; DEC-033/capabilities; BR-015–19/edición, captura y evidencia; BR-020/audit; BR-026/estado; SPRINT-02 lifecycle/outbox; SPRINT-03 Evidence; rector SPRINT-04 §5–44.

## 36. Acceptance Criteria

Governance, listing, form, evidence, states, submit, seguridad y calidad: **PASS**. Cero migrations no era viable para el menú persistente; se usó una migración de datos mínima y necesaria, sin schema. Producción: **NOT AUTHORIZED / NOT MODIFIED**.

## 37. Recommended Follow-up

Revisión y aprobación expresa del Owner. No iniciar SPRINT-05 automáticamente. Los Production Gates acumulados deben cerrarse en el Sprint autorizado correspondiente.

## 38. Sprint Conclusion

Portal Cliente — Proceso de Notificaciones: IMPLEMENTADO LOCALMENTE

Portal Administrativo: NO IMPLEMENTADO EN ESTE SPRINT

Historial de Vehículos Consultados: NO IMPLEMENTADO EN ESTE SPRINT

DataTables SPRINT-06: NO IMPLEMENTADOS

Notification Delivery: NO IMPLEMENTADO

Email: NO ENVIADO

Cron/cPanel: NO CONFIGURADO

Producción: NO MODIFICADA

SPRINT-05: NO INICIADO

READY FOR OWNER REVIEW

STOP.

## Owner Review / Governance Closure

**Decisión formal:** `SPRINT-04 — APPROVED WITH OBSERVATIONS`
**Fecha de aprobación:** 2026-08-13
**Decisión de cierre:** `DEC-039`

El Project Owner acepta formalmente la implementación documentada de SPRINT-04 sin requerir su reapertura ni modificaciones de código, migrations, base de datos o tests.

### OBS-04-01 — Migration de menú

La migration de datos del menú se acepta como válida y necesaria para el sistema persistente de menús. No cambia schema, permanece reversible, no requiere Change Request y deberá incluirse posteriormente en el procedimiento de deployment.

### OBS-04-02 — Segundo submit concurrente

La carrera real demostrada cierra la parte correspondiente de OBS-02-02: una única transición, un evento, un outbox, estado final `SUBMITTED` y `lock_version` consistente. El mensaje `No autorizado para enviar este expediente.` se acepta para SPRINT-04. Evaluar `CASE_ALREADY_SUBMITTED` o equivalente queda como mejora futura no bloqueante y no se implementa en este cierre.

### OBS-04-03 — Production Gates

Permanecen pendientes: MariaDB 10.6.27; idempotencia end-to-end request → consulta; doble asignación VIN; validación versus auto-close; deadlock/retry; índices/EXPLAIN con volumen representativo; deuda histórica de migrations; malware scanning; storage/fileinfo/GD/permisos reales en Neubox; y autorización separada de producción. No se resolvieron durante este cierre documental.

### OBS-04-04 — VIN excepcional

Se acepta que SPRINT-04 no expone una operación general para modificar VIN. VIN continúa inmutable en la experiencia normal. Los casos por placa sin VIN y su conciliación deberán considerarse explícitamente en el flujo administrativo/follow-up posterior, respetando las reglas de asignación única ya implementadas.

### Elementos aceptados

Quedan aceptados el Portal Cliente — Proceso de Notificaciones; listado paginado propio; ownership server-side; formulario, snapshot/defaults, folio/VIN inmutables, campos contractuales, IPH OR NUC, normalización, `recovered_at` no futuro, guardado parcial, submit/resubmit, seis estados, matriz de mutabilidad, Evidence SPRINT-03, IDOR, mass assignment, CSRF, auditoría/outbox, doble submit real, tests y regresión documentados.

DEC-036 permanece como roadmap canónico. DEC-037 permanece como cierre de SPRINT-03. DEC-038 permanece como decisión contractual de `recovered_at`. SPRINT-05 y producción requieren autorización explícita separada.

`APPROVED WITH OBSERVATIONS`
