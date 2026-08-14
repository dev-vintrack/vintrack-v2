# VINTrack — SPRINT-05: Administrative Portal — Review & Validation — RESULT

**Fecha:** 2026-08-13
**Entorno:** local/testing, MySQL 8.4.3
**Estado:** APPROVED WITH OBSERVATIONS
**Producción:** NOT AUTHORIZED / NOT MODIFIED

## 1. Executive Summary

Se implementó localmente el módulo administrativo **Proceso de Notificaciones** con listado paginado y filtrado, detalle global autorizado, correcciones auditadas, revisión, validación, rechazo con motivo obligatorio, Evidence privado de solo lectura e historial funcional. El motivo se conserva en eventos append-only y se muestra al Cliente. Suite final: **94 tests, 387 assertions, 0 fallos**.

## 2. Scope Completed

Portal administrativo, filtros SQL, detalle, identificación del responsable, correcciones permitidas, auditoría before/after, start review, validate, reject, motivo histórico, feedback Cliente, descarga privada, protección de estados y concurrencia real.

## 3. Explicit Exclusions

Sin Historial de Vehículos Consultados, Historial Global, DataTables SPRINT-06, delivery, emails reales, worker outbox, Cron/cPanel, Scheduler, producción, reapertura de VALIDATED, cierre manual, upload/remove administrativo, edición general del VIN ni SPRINT-06.

## 4. Governance / Decisions

DEC-036 continúa como roadmap canónico y DEC-039 como cierre de SPRINT-04. Se registró **DEC-040**: todo rechazo administrativo requiere motivo textual no vacío, normalizado, histórico y visible al Cliente. No se renumeró ninguna decisión.

## 5. Baseline

Antes de cambios: Laravel 12.62.0, PHP 8.3.30, timezone `America/Mexico_City`; `php artisan test --no-ansi`: **89 passed, 344 assertions, 0 failed**, 16.15 s. El worktree contenía cambios documentales del Owner, preservados.

## 6. Preflight Findings

`notification_case_events.reason` ya era `TEXT`; la tabla append-only tenía actor, timestamp, `field_name`, `old_value`, `new_value`, metadata e índice `(notification_case_id, occurred_at, id)`. Lifecycle ya tenía `FOR UPDATE`, `lock_version`, máquina de estados, outbox y auto-close invocable. No existía UI/routing administrativo de expedientes. La asignación VIN única existía solo para owner de draft por placa. Evidence permitía lectura administrativa y técnicamente gestión; SPRINT-05 restringió esta última server-side.

## 7. Administrative Portal Architecture

Controller web delgado, dos FormRequests, lifecycle/Application como autoridad, modelos de persistencia y vistas Blade sobre layout existente. No se duplicó la máquina de estados, normalizador ni almacenamiento documental.

## 8. Routes / Authorization

Seis rutas `admin.notification-cases.*`: index, show, update, start-review, validate y reject. Middleware `role:analista` conserva acceso de `admin` por el patrón existente. Lifecycle verifica capabilities y estado; `soporte` y Clientes reciben 403. CSRF permanece activo.

## 9. Administrative Case Listing

Muestra folio, VIN, responsable, placas, marca/modelo/año, apertura, deadline, estado, envío, actualización, evidencia y acción. No mezcla consultas ni usa `vehicles`.

## 10. Filters / Pagination

Filtros por folio, VIN, responsable, estado y rango de apertura se aplican en SQL. Paginación Laravel de 20, orden determinista y prioridad SUBMITTED/UNDER_REVIEW. Eager loading y conteo SQL evitan N+1.

## 11. Case Detail

Presenta identificación, consulta origen, responsable, snapshot/captura, fechas, evidencia activa, motivos de rechazo, timeline funcional y acciones legales. No expone paths, keys, hashes, payload provider, wallet ni metadata técnica.

## 12. Administrative Corrections

Solo `UNDER_REVIEW` admite corrección administrativa. Mapping allowlist reutiliza campos/normalizador contractuales; folio, VIN, owner, consultation, status, deadlines, clocks y lock arbitrario no se mapean. `recovered_at` futuro se rechaza en FormRequest y servicio.

## 13. Before/After Audit

Cada campo efectivamente cambiado genera `ADMIN_FIELD_CORRECTED` con actor, timestamp, correlación, campo, valor anterior/nuevo y versión. Un no-op no incrementa versión ni crea eventos falsos.

## 14. Evidence Review

Se reutiliza exclusivamente SPRINT-03 para listar y descargar evidencia privada. Admin/Analista no pueden cargar ni remover; Cliente owner conserva gestión en PENDING/REJECTED. Binding case/document e IDOR permanecen protegidos.

## 15. Start Review

`SUBMITTED → UNDER_REVIEW` se ejecuta por command explícito en lifecycle, con autorización, lock, versión, auditoría y outbox transaccional.

## 16. Validation

`UNDER_REVIEW → VALIDATED` exige capability y todas las invariantes contractuales, incluido VIN, campos obligatorios, IPH OR NUC y `recovered_at` no futuro. Es atómica, auditable y terminal.

## 17. Rejection

`UNDER_REVIEW → REJECTED` usa transición explícita. Conserva case, folio, VIN, owner, deadline, evidencia e historial; habilita corrección/resubmit del Cliente sobre el mismo expediente.

## 18. Rejection Reason Persistence

Null, vacío y whitespace se rechazan server-side. Se normaliza a mayúsculas sin diacríticos y espacios colapsados; máximo 2000 caracteres, sin truncamiento. Persiste en `notification_case_events.reason`. Todos los ciclos quedan históricos; no se añadió schema.

## 19. Client Rejection Feedback

La vista Cliente recibió únicamente un bloque REJECTED con último motivo, fecha e instrucción de corregir y reenviar. Tiene test de visibilidad y resubmit.

## 20. State / Capability Matrix

- SUBMITTED: iniciar revisión.
- UNDER_REVIEW: corregir, validar o rechazar.
- REJECTED: Cliente corrige/evidencia/resubmit; Admin solo consulta.
- VALIDATED y CLOSED_NO_FOLLOW_UP: solo lectura.
- PENDING: Cliente captura; Admin solo consulta.

No existe dropdown libre de status.

## 21. Automatic State Protection

No hay endpoint ni control manual para `CLOSED_NO_FOLLOW_UP`. La única operación sigue siendo `autoCloseDue()`; ahora puede revalidar una versión candidata bajo lock para impedir efectos contradictorios en carreras.

## 22. Exceptional VIN Assessment

No se expuso UI administrativa: el command existente pertenece al owner del draft por placa y no existe capability administrativa específica aprobada. VIN normal y folio permanecen inmutables. La carrera real del command owner sí se ejecutó por instrucción del Owner.

## 23. IDOR / Mass Assignment / CSRF

Se probaron Analista, soporte sin capability y Cliente; IDs/owner/VIN/status/folio forjados no modifican el agregado. Evidence conserva autorización por case y document. GET no muta y CSRF protege PUT/POST.

## 24. Audit / Events / Outbox

Start-review, validate, reject y correcciones generan eventos append-only. Las transiciones generan outbox `PENDING` dentro de la transacción. No se ejecutó delivery ni email.

## 25. Concurrency — Validate vs Reject

MySQL local, procesos/conexiones separados y barrera común. Resultado repetido final: validate `OK`; reject `DomainException: Conflicto de versión`; `VALIDATED`, `lock_version=1`, eventos `[CASE_VALIDATED]`, outbox `[CASE_VALIDATED]`, cleanup `true`. Exactamente una transición efectiva.

## 26. Concurrency — Auto-close Races

- Validate vs auto-close: ganador único; resultado observado final `CLOSED_NO_FOLLOW_UP`, versión 1, un `CASE_AUTO_CLOSED`, un outbox; validate obtuvo conflicto.
- Reject vs auto-close: `REJECTED`, versión 1, un `CASE_REJECTED`, un outbox; auto-close cerró 0 tras revalidación de versión.

No se configuró Cron. Los resultados finales fueron únicos y legales.

## 27. Concurrency — VIN Assignment

Draft por placa sin VIN, dos procesos owner y VIN distintos: uno `OK`, segundo “asignación única no autorizada”; VIN final único, `lock_version=1`, un `CASE_VIN_ASSIGNED`, cero outbox. Cleanup `true`. La carrera cierra la parte de doble asignación de OBS-02-02 en MySQL local; MariaDB real continúa pendiente.

## 28. Deadlock / Retry Findings

No se observaron deadlocks. Las transacciones perdedoras devolvieron conflicto/denegación estables y no dejaron evento, outbox ni cambio parcial. Se conserva retry transaccional acotado a 3 de Laravel; no se añadió retry infinito/global.

## 29. Tests Added

`AdminNotificationCasePortalTest`: **5 tests / 46 assertions**. Tres harnesses soportan preparación, workers separados y cleanup de las cuatro carreras.

## 30. Tests Executed

- Baseline: `php artisan test --no-ansi` → **89/344**, 0 fallos.
- Focalizado Admin: **5/46**, 0 fallos.
- Suite final: `php artisan test --no-ansi` → **94/387**, 0 fallos, 16.11 s.
- Regresión SPRINT-04: `CustomerNotificationCasePortalTest` → **7/49**, 0 fallos, 2.30 s.
- `vendor/bin/pint <PHP afectados>` → passed tras correcciones automáticas.
- `git diff --check` → passed.
- `route:list --path=admin/proceso-notificaciones` → 6 rutas.
- No existe script/configuración de análisis estático adicional en `composer.json`; **NOT APPLICABLE**.

## 31. Regression

Suite completa preservó admission gate, límite 3, 90 días, deadline, estados, documentos, IDOR, idempotencia, auditoría, outbox, wallet/provider y notificaciones existentes sin correo real. Regresión SPRINT-04 verde separadamente.

## 32. Files Modified

Servicios lifecycle/authorization/audit; modelos case/event; controller Cliente; controller y dos requests Admin; rutas; vistas Admin index/show; vista Cliente; migration de menú; test Admin; tres harnesses; DECISION_LOG, PROJECT_STATE y este RESULT. Los documentos del Owner preexistentes se preservaron.

## 33. Database Operations

Se ejecutó únicamente migration local de datos del menú y harnesses aislados sobre `vintrack_dev`; todos los harnesses validaron target por `creation_key` y limpiaron sus filas. No hubo SQL productivo ni operaciones sobre producción.

## 34. Migrations

Una migration reversible de datos, sin schema: registra `admin.notification-cases.index` para roles `admin` y `analista`. Se verificó `up → rollback --step=1 → up` localmente. Motivos y diffs usan columnas existentes.

## 35. Performance Findings

Listado paginado, filtros SQL, eager loading de owner y `withCount` de documentos. No se implementó DataTables. No se demostró necesidad de índice/schema nuevo; EXPLAIN con volumen representativo sigue como Production Gate.

## 36. Limitations / Risks

MariaDB 10.6.27 y Neubox no probados; no scanner malware; sin delivery/Cron. La capability excepcional administrativa de VIN no existe y no se inventó. El timeline muestra nombres técnicos de eventos, suficientemente funcional para SPRINT-05 pero mejorable en UX futura.

## 37. Production Gates

Permanecen: MariaDB 10.6.27; idempotencia end-to-end request→consulta; índices/EXPLAIN con volumen; deuda histórica de migrations; malware scanning; storage/fileinfo/GD/permisos Neubox; backup/rollback y autorización productiva. En MySQL local quedaron cerradas con evidencia las carreras doble submit (SPRINT-04), doble VIN, validate/reject, validate/auto-close y reject/auto-close. No se configuró infraestructura productiva.

## 38. Requirement Traceability

DEC-040/motivo; DEC-038/recovered_at; DEC-032/campos/VIN/folio; DEC-033/capabilities; BR-015–20/edición, rechazo, evidencia y auditoría; SPRINT-02 lifecycle/outbox; SPRINT-03 Evidence; SPRINT-04 Cliente; rector SPRINT-05 §§5–59.

## 39. Acceptance Criteria

Governance, listado, detalle, corrección, revisión, rechazo, validación, estados automáticos, Evidence, seguridad, concurrencia y calidad: **PASS local**. Producción: **NOT AUTHORIZED / NOT MODIFIED**.

## 40. Recommended Follow-up

Revisión formal del Project Owner. No iniciar SPRINT-06. Mantener gates acumulados para el Sprint autorizado correspondiente y procedimiento productivo separado.

## 41. Sprint Conclusion

Portal Administrativo — Proceso de Notificaciones:
IMPLEMENTADO LOCALMENTE

Corrección administrativa:
IMPLEMENTADA LOCALMENTE

Revisión / Validación / Rechazo:
IMPLEMENTADOS LOCALMENTE

Motivo de rechazo:
IMPLEMENTADO Y VISIBLE AL CLIENTE

Evidence:
REUTILIZADO DESDE SPRINT-03

Portal Cliente:
MODIFICADO ÚNICAMENTE EN LO NECESARIO PARA FEEDBACK DE RECHAZO

Historial de Vehículos Consultados:
NO IMPLEMENTADO

DataTables SPRINT-06:
NO IMPLEMENTADOS

Delivery / Email:
NO EJECUTADO

Cron/cPanel:
NO CONFIGURADO

Producción:
NO MODIFICADA

SPRINT-06:
NO INICIADO

READY FOR OWNER REVIEW

STOP.

## Owner Review / Governance Closure

**Decisión formal:** `SPRINT-05 — APPROVED WITH OBSERVATIONS`
**Fecha de aprobación:** 2026-08-13
**Decisión de cierre:** `DEC-041`

El Project Owner acepta formalmente la implementación documentada de SPRINT-05 sin requerir reapertura ni cambios de código, migrations, base de datos o tests.

### OBS-05-01 — Migration de menú

Se acepta la migration reversible de datos del módulo administrativo. No cambia schema, sigue el patrón aprobado, fue probada `up → rollback → up`, no requiere Change Request y deberá formar parte del procedimiento posterior de deployment.

### OBS-05-02 — VIN excepcional

No existe capability administrativa aprobada para asignación excepcional de VIN y se acepta que SPRINT-05 no la exponga. La carrera técnica de doble VIN queda cerrada localmente en MySQL. Exponer o no esa capability es un follow-up funcional separado. VIN normal y folio permanecen inmutables.

### OBS-05-03 — Timeline administrativo

Se acepta el timeline funcional con nombres técnicos de eventos. Traducirlos o humanizarlos queda como mejora UX futura no bloqueante, sin alterar la auditoría subyacente.

### OBS-05-04 — Production Gates restantes

Permanecen: MariaDB 10.6.27 real; idempotencia end-to-end request → consulta; índices/EXPLAIN con volumen representativo; deuda histórica de migrations; malware scanning; storage/fileinfo/GD/permisos reales en Neubox; backup/rollback productivo y autorización explícita de producción.

Quedan formalmente cerradas localmente las carreras de doble submit, doble asignación VIN, validate vs reject, validate vs auto-close y reject vs auto-close. No se volverán a tratar como pendientes salvo fallo posterior en MariaDB/producción.

### Elementos aceptados

Quedan aceptados el Portal Administrativo — Proceso de Notificaciones; listado paginado/filtrado; detalle global autorizado; correcciones administrativas; auditoría before/after; start review; validate; reject; motivo obligatorio e histórico; feedback Cliente; Evidence privado read-only administrativo; protecciones IDOR, mass assignment y CSRF; estado automático protegido; concurrencia documentada; tests y regresión.

DEC-036 permanece como roadmap canónico, DEC-039 como cierre de SPRINT-04 y DEC-040 como decisión contractual del motivo obligatorio. Producción permanece `NOT AUTHORIZED`. SPRINT-06 queda `PENDING OWNER AUTHORIZATION` y no fue iniciado.

`APPROVED WITH OBSERVATIONS`
