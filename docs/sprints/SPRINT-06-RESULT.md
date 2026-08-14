# VINTrack — SPRINT-06: Vehicle Consultation Histories + Server-side DataTables — RESULT

**Fecha:** 2026-08-14  
**Entorno:** local/testing, MySQL 8.4.3  
**Estado:** READY FOR OWNER REVIEW  
**Producción:** NOT AUTHORIZED / NOT MODIFIED

## 1. Executive Summary

Se implementaron localmente el Historial de Vehículos Consultados del Cliente y el Historial Global administrativo con DataTables 1.13.6 server-side. La proyección parte de `consultations`, resuelve históricamente el expediente sin usar `vehicles`, deriva estados sin columnas nuevas y calcula acciones server-side. Resultado final: **99 tests, 417 assertions, 0 fallos**.

## 2. Scope Completed

Read model histórico, Consultation → Case, ownership propio/otro, 90 días, `VIN_NOT_AVAILABLE`, mapping Notificado/Validado, endpoints JSON, paginación, búsqueda, filtros, ordering allowlist, vistas, filtro Cliente, autorización, menú reversible y pruebas.

## 3. Explicit Exclusions

Sin producción, deployment, Cron/cPanel, delivery, emails, SPRINT-07, backfill, retroactivos, exportación, analytics, cambios 3/30/90, modificación/merge VIN, providers, créditos, wallet ni schema funcional nuevo.

## 4. Governance

DEC-036 permanece canónico. DEC-041 cierra SPRINT-05. La autorización del Owner activó sólo SPRINT-06 local. Producción y SPRINT-07 siguen no autorizados.

## 5. Baseline

`php artisan test --no-ansi`: **94 passed, 387 assertions, 0 failed**, 18.85 s. Se preservó el worktree previo de SPRINT-05.

## 6. Preflight

MySQL 8.4.3; `consultations`: 8 filas/96 KB; `notification_cases`: 0 filas/64 KB; events: 0. Los historiales usaban `get()` completo y DataTables client-side. Índices existentes: user, valor, `(provider_service_id, criterio, valor, created_at)`, case consultation UQ, VIN/status/dates y event timeline.

## 7. Historical Projection Design

`ConsultationHistoryQuery` construye SQL paginado y `ConsultationHistoryRow` define el contrato semántico. Blade/JavaScript sólo presentan el read model.

## 8. Consultation → Case Resolution

La consulta origen usa `notification_cases.consultation_id`. Una consulta posterior calificante sólo considera cases cuya consulta origen no es posterior y cuya identidad persistida coincide. Gana el case aplicable más reciente anterior; nunca el case actual más reciente sin cronología.

## 9. 90-Day Historical Semantics

Para case validado se respeta `validated_at + notification_case_reuse_days`; para cierre, `closed_at`. La consulta origen siempre resuelve directamente. El valor configurable viene de `NotificationCaseSettings`.

## 10. Own vs Other User Case

Contrato: `OWN_CASE`, `OTHER_USER_CASE`, `NO_CASE`. Cliente con case ajeno recibe sólo mensaje limitado, sin owner id, folio, evidencia, motivo, timeline ni acción privada.

## 11. Client History Architecture

Vista `customer.consultations`; JSON `customer.consultations.data`; scope desde `Auth::id()`. Pendiente propio rojo claro con texto; case ajeno azul claro con mensaje.

## 12. Admin Global History Architecture

Vista `admin.consultations.index`; JSON `admin.consultations.data`; acceso explícito Admin/Analista; soporte/Cliente 403. Enlace al detalle SPRINT-05 sin duplicar transiciones.

## 13. Server-side DataTables Protocol

Soporta `draw`, `start`, `length`, `search[value]`, `order`, filtros y conteos. Máximo `length=100`; `-1` no descarga el universo. No hay export buttons.

## 14. Client Query

Inicia con `consultations.user_id = authenticated user`; total, filtrado y página se calculan server-side. No carga el universo con `get()`.

## 15. Admin Query

Universo autorizado con joins de usuario/servicio y proyección case/eventos; devuelve sólo offset/limit solicitado.

## 16. Client Filter

`user_id` sólo existe en Admin y se aplica SQL. Cliente rechaza cualquier `user_id` manipulado.

## 17. Status Robo

Derivado sólo de `consultations.alerta_robo`; no usa `vehicles`, providers ni APIs.

## 18. Status Notificado Projection

`NO_CASE/PENDING → NO`; `SUBMITTED/UNDER_REVIEW/REJECTED/VALIDATED → SI`; `CLOSED_NO_FOLLOW_UP → SI` sólo con evento `CASE_SUBMITTED`/`CASE_RESUBMITTED`; de lo contrario `NO`.

## 19. Status Validado Projection

Sólo `VALIDATED → SI`; cualquier otro estado o ausencia de case → `NO`.

## 20. General Process Status

Se exponen los seis estados canónicos; ausencia de case muestra `NO APLICA`. Sin estado nuevo.

## 21. Deadline / Notification Date

Deadline viene de `notification_deadline_at`. Primer submit usa el mínimo evento submit/resubmit; nunca `updated_at`.

## 22. Actions Read Model

Backend calcula: PENDING propio `CAPTURAR / CONTINUAR`; REJECTED propio `CORREGIR`; otros propios `VER`; Admin `VER EXPEDIENTE`; case ajeno/no case sin navegación privada.

## 23. VIN_NOT_AVAILABLE

Placa sin VIN muestra `VIN NO DISPONIBLE`. Identidad: provider service + PLATE + placa persistida normalizada; sin inferencia.

## 24. Multiple Cases Same Vehicle

Prueba con Case A validado y Case B posterior demuestra que consulta dentro de ventana proyecta A y la consulta origen posterior a 90 días proyecta B con referencia previa.

## 25. No Retroactive False Association

Prueba crítica: consulta anterior a la consulta origen de un case del mismo VIN permanece `NO_CASE`.

## 26. Authorization / IDOR

Cliente sólo recibe sus consultas; alterar `user_id` falla. Case ajeno no expone privados. Admin/Analista autorizados; soporte/Cliente denegados. El detalle conserva sus policies.

## 27. Search / Filters / Ordering

Búsqueda parametrizada; filtros fecha/robo/estado y Admin user/VIN/placa. Ordering mapea índice UI a allowlist fija. Payload adversarial no llega a `ORDER BY`.

## 28. Performance / Query Count

Paginación usa `LIMIT/OFFSET`; no hay loop de queries por fila ni N+1. Test instrumentado exige máximo 12 queries HTTP y pasó. La proyección case se resuelve en SQL.

## 29. Indexes / EXPLAIN

EXPLAIN local: derived principal estimó 24 filas con temporary/filesort; consultations 8; users `eq_ref PRIMARY`; events usó `notification_case_events_timeline_idx`; subquery case estimó 1 fila y reconoció `consultation_id_unique`/`vin_applicable_idx`, aunque eligió scan por tablas vacías. Volumen insuficiente para demostrar selectividad. **No se creó índice**; OBS-02-06 sigue gate con volumen representativo.

## 30. Menu / Permissions

Migration `2026_08_14_000000_update_vehicle_consultation_history_menus.php`: nombres contractuales, Admin/Analista habilitados, soporte deshabilitado. `up → rollback --step=1 → up` pasó.

## 31. Tests Added

`ConsultationHistoryTest`: **5 tests / 27 assertions**. Se ajustaron regresiones al contrato server-side y capability aprobada.

## 32. Tests Executed

- Baseline: **94/387**, 0 fallos.
- SPRINT-06 focalizado: **5/27**, 0 fallos.
- SPRINT-04/05 + SPRINT-06: **17/124**, 0 fallos.
- Suite final: **99/417**, 0 fallos, 15.34 s.
- Pint PHP afectados: passed tras formato.
- Pint global `--test`: ejecutado; falla por deuda preexistente fuera del alcance, ya registrada históricamente.
- `git diff --check`: passed.

## 33. Regression

SPRINT-04 Cliente y SPRINT-05 Admin verdes; core, Evidence, admission, concurrencia, autorización, outbox, audit y wallet/provider preservados.

## 34. Files Modified

Nuevos: dos clases Application/read model, migration menú, dos vistas, test y este resultado. Modificados: controllers, routes, tests de acceso/privacidad/menú, AGENTS, ARCHITECTURE y PROJECT_STATE. Cambios previos SPRINT-05 preservados.

## 35. Database Operations

Sólo migration local reversible de datos de menú. Sin broad update/delete, backfill, retroactivos ni producción.

## 36. Migrations

Una migration de datos, cero schema y cero índices nuevos. Up/down/up probado.

## 37. Limitations

Dataset local no representativo; EXPLAIN productivo/MariaDB pendiente. Normalización SQL de placa cubre separadores persistidos comunes y no infiere identidad ausente. CDN DataTables permanece como baseline actual.

## 38. Production Gates

Permanecen MariaDB 10.6.27, request→consulta idempotency, EXPLAIN con volumen, deuda migrations, malware scanning, storage/fileinfo/GD/permisos Neubox, backup/rollback y autorización productiva.

## 39. Requirement Traceability

BR-003/005/021/022/023; DEC-001/002/005/006/007/018/019/020/031/036/041; rector SPRINT-06 §§3–68. Mapping explícito del Owner prevalece.

## 40. Acceptance Criteria

Governance, separación, Cliente, Admin, proyección, estados, seguridad y calidad: **PASS local**. Performance server-side/N+1/allowlist: **PASS local**. EXPLAIN representativo: **PENDING PRODUCTION GATE** por volumen insuficiente.

## 41. Recommended Follow-up

Revisión formal del Owner. No iniciar SPRINT-07. Antes de producción, repetir EXPLAIN con volumen representativo y MariaDB 10.6.27.

## 42. Sprint Conclusion

Historial Cliente: IMPLEMENTADO LOCALMENTE

Historial Global Administrativo: IMPLEMENTADO LOCALMENTE

Filtro por Cliente: IMPLEMENTADO LOCALMENTE

Server-side pagination/filtering: IMPLEMENTADO LOCALMENTE

Proceso de Notificaciones Cliente: PRESERVADO

Proceso Administrativo: PRESERVADO

Evidence: PRESERVADO

Notification Delivery: NO IMPLEMENTADO

Cron/cPanel: NO CONFIGURADO

Producción: NO MODIFICADA

SPRINT-07: NO INICIADO

READY FOR OWNER REVIEW

STOP.
