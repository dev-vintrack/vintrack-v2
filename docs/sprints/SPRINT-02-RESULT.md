# VINTrack — SPRINT-02: Core Domain, Persistence & Consultation Admission — RESULT

**Fecha de ejecución:** 2026-08-12  
**Fecha de aprobación:** 2026-08-13  
**Entorno:** local/testing  
**Estado final:** APPROVED WITH OBSERVATIONS

## 1. Executive Summary

Se implementó localmente el núcleo de expedientes: esquema aditivo, configuración tipada, admisión antes de wallet/API, reservas y guards, creación/reutilización, folio anual, seis estados, reglas 3/30/90, borrador y submit, asignación única de VIN, conciliación, auditoría append-only, outbox sin delivery y autorización de casos de uso.

La suite SQLite completa pasa con **76 tests y 258 assertions**. MySQL 8.4.3 local aceptó las cuatro migrations; el ciclo `migrate → rollback(4) → migrate` pasó en SQLite y en una base MySQL desechable restaurada desde el baseline local. Tres carreras fueron ejecutadas realmente con procesos PHP/conexiones separadas. MariaDB 10.6.27 no estuvo disponible.

No se declara `READY FOR PRODUCTION`: faltan ejecución MariaDB y cobertura concurrente real de todas las carreras contractuales. No se tocó producción.

## 2. Scope Completed

- Persistencia del agregado y tablas auxiliares.
- Configuración central para 3/30/90, máximo pendiente, archivos, timezone y TTL.
- Dominio de estado, criterio, VIN, folio, tiempo y normalización.
- Admission guard antes de wallet, proveedor y débito.
- Reservas idempotentes por request key, guards de usuario/VIN/origen y locks InnoDB.
- Creación/reutilización, owner inmutable, consulta única, folio y `previous_case_id`.
- Borrador por placa sin VIN y asignación única.
- Submit sin UI, IPH OR NUC y transiciones del núcleo.
- Detección/persistencia de conciliación VIN sin merge ni séptimo estado.
- Eventos append-only y outbox deduplicado sin entrega.
- Autorización server-side dentro de casos de uso.
- Tests unitarios, integración, seguridad, persistencia, regresión y harness MySQL concurrente.

## 3. Scope Not Completed / Explicit Exclusions

No se implementaron portales, DataTables, formularios finales, uploads/downloads, storage de evidencia, email, portal delivery, worker, Cron, Scheduler, endpoint HMAC, purga, backfill ni expedientes retroactivos.

No se ejecutaron todas las carreras reales solicitadas: doble submit, doble asignación VIN, validación versus auto-cierre y deadlock/retry están cubiertas por CAS/idempotencia y tests funcionales secuenciales, pero **NOT RUN como concurrencia real**. El replay end-to-end de una consulta ya consumida no reconstruye todavía una respuesta anterior; la idempotencia implementada cubre reserva, bloqueo, creación, eventos y outbox, no exactamente-once del proveedor externo.

## 4. Documentation and Evidence Reviewed

Leídos completamente: `AGENTS.md`, todos los documentos maestros obligatorios, `SPRINT-00-RESULT.md`, rector y resultado de SPRINT-01 y rector SPRINT-02. Se inspeccionaron `ConsultationService`, repositorios/modelos, migrations, roles, configuración, adapters Placas/VINData, payloads presentes y suite existente.

## 5. AGENTS.md Harmonization

Se sustituyó la regla histórica Placas/IPH por la matriz vigente, se incorporó IPH OR NUC, excepción de placa con VIN nullable/asignable una vez, folio inmutable, timezone `America/Mexico_City` y estado autorizado de SPRINT-02. Se releyó el archivo completo después del cambio.

## 6. Architecture Implemented

- `app/Domain/NotificationCases`: enums, value objects y políticas puras.
- `app/Application/NotificationCases`: admisión, creación, ciclo de vida, autorización, settings, auditoría, outbox y mapper.
- `app/Infrastructure/Persistence/Models`: modelos Eloquent del caso/evento/outbox.
- Integración mínima en `ConsultationService` y respuesta de negocio estable en `ConsultationController`.

Las reglas no se colocaron en Blade/JavaScript. No se agregaron rutas temporales.

## 7. Database Migrations and Final Schema

Migrations nuevas:

1. `2026_08_12_220000_add_notification_case_settings_to_global_configuration.php`
2. `2026_08_12_220100_create_notification_case_guards_and_sequences.php`
3. `2026_08_12_220200_create_notification_cases_table.php`
4. `2026_08_12_220300_create_notification_case_support_tables.php`

Tablas nuevas: `notification_cases`, `notification_case_documents` (metadata), `notification_case_events`, `notification_outbox`, `portal_notifications`, secuencias, tres guards, reservas y conciliaciones. Todas son InnoDB/utf8mb4 en MySQL local. Se contaron 18 FK del módulo y 11 índices/constraints en `notification_cases`.

No existen en `notification_cases`: `vehicle_id`, `provider_service_id`, `source_criterion`, `source_value`. No se alteró `consultations.provider_service_id`.

## 8. Domain Model and State Machine

Estados exactos: `PENDING`, `SUBMITTED`, `UNDER_REVIEW`, `REJECTED`, `VALIDATED`, `CLOSED_NO_FOLLOW_UP`.

Se rechaza `SUBMITTED → VALIDATED`; terminales no mutan; cierre por falta de seguimiento solo se ejecuta por servicio automático invocable. `lock_version` protege mutaciones.

## 9. Consultation Admission Guard

`ConsultationAdmissionService::reserve()` se invoca en `ConsultationService::consult()` después de resolver el servicio y antes de sincronizar/validar wallet, adapter/API y débito. Regla: pendientes + reservas vivas >= configuración máxima bloquea con `MAX_PENDING_NOTIFICATION_CASES`, evento y outbox portal no entregado.

## 10. Reservations, Locks and Idempotency

- User guard bloqueado con `FOR UPDATE`.
- Reserva única por `request_key`, TTL 150 segundos, sin transacción remota.
- VIN/source guards serializan identidad.
- `consultation_id`, `creation_key`, folio, event key, outbox dedup e incident key son únicos.
- Deadlocks usan retry transaccional de Laravel hasta tres intentos en operaciones principales.

Limitación: el resultado completo de proveedor no se memoiza por request key; ver §24.

## 11. Case Creation / Reuse / Previous Case

Una consulta calificante crea como máximo un caso. Activos siempre se reutilizan; validados se reutilizan inclusivamente hasta 90 días; después puede crearse un caso nuevo con el anterior más reciente. Owner se toma de `consultations.user_id`.

## 12. VIN Mapper Inventory

| provider_service_id local | adapter | criterio | VIN | resultado sin evidencia |
|---:|---|---|---|---|
| 1 | `PlacasProviderAdapter` | `niv`, `placa` | `niv`: `consultations.valor`; `placa`: no existe path/fixture inequívoco | `VIN_NOT_AVAILABLE` |
| 2 | `VinDataProviderAdapter` | `vin` | `consultations.valor` | no aplica |
| 3 | `VinDataProviderAdapter` | `vin` | `consultations.valor` | no aplica |

No se consulta `vehicles`. El mapper de placa rechaza inferencia heurística incluso si aparece una clave anidada llamada VIN.

## 13. Plate-Origin VIN Assignment and Reconciliation

Owner + origen placa + estado editable + VIN NULL son precondiciones. La primera asignación bloquea caso/VIN, audita `CASE_VIN_ASSIGNED` y vuelve VIN inmutable. Un conflicto conserva ambos casos, crea una incidencia OPEN única, audita y bloquea submit. No hay merge ni nuevo estado.

## 14. Temporal Rules and Configuration

Defaults centralizados: 3, 30, 90, 3 pendientes, 8 archivos, 3,145,728 bytes, `America/Mexico_City`, TTL 150 segundos. Deadline es tercer día a `23:59:59`, vigente `<=` y vencido `>`. Auto-cierre usa `opened_at + 30 días` y no reinicia.

## 15. Audit and Outbox

Eventos son append-only a nivel de modelo, con FK RESTRICT y event key único. Solo `CONSULTATION_BLOCKED` admite caso NULL. Metadata se filtra por allowlist. Outbox se escribe en la misma transacción, con payload mínimo y dedup key; no existe delivery.

## 16. Authorization and Security

Los servicios verifican owner/estado para editar, asignar VIN y enviar; Analista/Admin para revisar/rechazar/validar. Mass assignment excluye identidad, folio, VIN, estado y clocks. IDOR de mutación, owner/folio forjados y cierre manual se probaron.

## 17. Files Modified

- `AGENTS.md`, `config/app.php`, `docs/PROJECT_STATE.md`, este resultado.
- Cuatro migrations SPRINT-02.
- 7 archivos de dominio bajo `app/Domain/NotificationCases`.
- 9 archivos de aplicación bajo `app/Application/NotificationCases`.
- 3 modelos nuevos; `GlobalConfiguration` actualizado.
- `ConsultationService` y `ConsultationController` actualizados.
- 3 clases de test y 4 scripts de harness concurrente.

`.devin/` y documentación previa ya estaban sin seguimiento y se preservaron.

## 18. Database Operations Executed

- Backup lógico local: `storage/app/backups/sprint02-pre-migration-20260812.sql`.
- SQLite temporal: migrate; primer rollback no acotado alcanzó una migration histórica incompatible; después `rollback --step=4` y migrate pasaron.
- MySQL principal `vintrack_dev`: cuatro migrations aditivas. La cuarta falló inicialmente por nombre FK >64; se verificaron cinco tablas parciales vacías, se eliminaron solo esas tablas nuevas, se acortaron constraints y el retry pasó.
- MySQL desechable verificada `vintrack_sprint02_test`: recreada desde backup local; migrate → rollback 4 → migrate pasó.
- En `vintrack_dev` quedaron 0 casos/eventos/outbox/documentos/notificaciones de portal; no hubo backfill.

## 19. Tests Added

- `NotificationCaseDomainTest`: catálogo, transiciones, pendiente, VIN, criterio, folio, normalización y fronteras temporales.
- `NotificationCasePersistenceTest`: tablas/columnas y exclusiones.
- `NotificationCaseCoreTest`: admisión, consulta positiva, placa/VIN, submit, estados, auto-cierre, conciliación y seguridad.
- Harness de procesos separados para reservas, creación única y folios.

## 20. Tests Executed and Exact Results

- `php artisan test --no-ansi`: **76 passed, 258 assertions, 0 failed**, 12.63 s en ejecución final.
- Suite enfocada previa: **13 passed, 73 assertions**.
- `vendor/bin/pint --test` sobre archivos afectados: **passed**.
- `vendor/bin/pint --test` global: **failed por deuda de formato preexistente** en numerosos archivos no afectados; no se reformateó masivamente el repositorio.
- Lint PHP de archivos SPRINT-02: **0 errores**.

## 21. Concurrency Evidence

Procesos PHP separados contra MySQL 8.4.3:

1. Dos pendientes + dos reservas: `RESERVED` y `BLOCKED`; una sola reserva cruzó a capacidad 3.
2. Misma `consultation_id`: ambos procesos devolvieron `case_id=8`, `case_number=NT-2026-000001`.
3. Creaciones distintas simultáneas: folios únicos `NT-2026-000002` y `NT-2026-000003`.

Doble submit, doble VIN, validación/cierre y deadlock/retry: **NOT RUN AS REAL CONCURRENCY**; no se presentan como probadas concurrentemente.

## 22. MySQL/MariaDB Compatibility Evidence

MySQL 8.4.3: migrations y esquema ejecutados; InnoDB/utf8mb4 confirmados; sin columnas prohibidas; constraints e índices inspeccionados. SQLite: suite y ciclo de las migrations nuevas pasan.

MariaDB 10.6.27: **NOT RUN — ENVIRONMENT UNAVAILABLE**. Revisión estática: no ENUM, generated/functional/partial indexes, TIMESTAMP funcional ni SQL crítico específico; JSON crítico se almacena como TEXT en eventos/outbox.

Una deuda histórica impide migrar desde cero MySQL con toda la cadena: migration 2026-07-17 consulta `adapter_code` antes de crearse en 2026-07-31. No se modificó por estar fuera del alcance; la prueba SPRINT-02 usó baseline restaurado.

## 23. Regression Results

Suite existente completa: **62 tests originales pasaron** dentro del total final de 76. Flujos de consulta, wallet, reportes, roles, privacidad y notificaciones existentes permanecen verdes.

## 24. Failures, Limitations and Known Issues

1. MariaDB no disponible.
2. Cuatro carreras contractuales no ejecutadas con procesos separados.
3. Idempotencia end-to-end de consulta después de proveedor/consumo no reconstruye el resultado previo; no se duplican caso/evento/outbox, pero un retry tardío requiere una ampliación futura del vínculo request→consulta para evitar reinvocar proveedor.
4. Placas no tiene path VIN verificable; se crea borrador VIN NULL conforme al contrato.
5. La cadena histórica de migrations no parte limpia en MySQL por deuda previa descrita en §22.
6. `EXPLAIN` sobre tabla vacía eligió PRIMARY; los índices contractuales sí existen, pero falta volumen representativo.

## 25. Production Impact

Producción no fue conectada, migrada, desplegada ni modificada. Estas migrations no se consideran aprobadas para producción.

## 26. Rollback and Recovery

Las cuatro migrations revierten en orden en bases desechables. En local principal existe respaldo lógico previo. Con datos futuros, se recomienda roll-forward; un `down` elimina estructuras del módulo y no debe ejecutarse con evidencia/casos sin autorización y backup.

## 27. Requirement Traceability

- BR-001–010: admisión, calificación, owner, 90 días y máximo pendiente.
- BR-011–018: tiempo, estados, submit, matriz y VIN.
- BR-020, 026–032, 035: auditoría, transiciones, configuración, timezone, identidad, conciliación y permisos.
- Evidencia/portales/delivery: solo schema mínimo expresamente autorizado; comportamiento final excluido.

## 28. Acceptance Criteria Verification

- Gobernanza/documentación: PASS.
- Datos/migrations locales: PASS; MariaDB NOT RUN.
- Dominio/submit/3-30-90/VIN: PASS.
- Bloqueo antes de wallet/API: PASS.
- Concurrencia real 2+2, consulta única y folio: PASS.
- Resto de matriz concurrente: PARTIAL / NOT RUN.
- Seguridad núcleo: PASS para casos cubiertos.
- Regresión: PASS.
- Alcance negativo: PASS.

Por las limitaciones explícitas, SPRINT-02 queda listo para revisión técnica del Owner, no para producción.

## 29. Recommended Follow-up Sprint Boundaries

No se inicia otro Sprint. Antes de cualquier despliegue: ejecutar MariaDB 10.6.27, completar harness de carreras, cerrar idempotencia request→consulta, validar EXPLAIN con volumen y definir el mapper Placas solo con fixture contractual.

## 30. Sprint Conclusion

El núcleo local queda implementado y demostrable para la secuencia consulta admitida → caso único → borrador → VIN excepcional → submit → revisión/rechazo/validación → auditoría/outbox, y para bloqueo de la cuarta consulta antes de wallet/API. Las limitaciones impiden declarar preparación productiva.

```text
Producción: NO MODIFICADA
Cron/cPanel: NO CONFIGURADO
Emails/notificaciones: NO ENTREGADOS
Archivos/evidencias: UI Y STORAGE NO IMPLEMENTADOS
Portales/DataTables: NO IMPLEMENTADOS
Expedientes retroactivos: NO CREADOS
```

## Owner Review / Governance Closure

**Decisión formal del Project Owner:** `SPRINT-02 — APPROVED WITH OBSERVATIONS`  
**Fecha de aprobación:** 2026-08-13

Las observaciones siguientes son follow-up obligatorio antes de producción. No reabren SPRINT-02, no autorizan su implementación en este cierre y no autorizan producción ni el siguiente Sprint.

### OBS-02-01 — MariaDB 10.6.27 real

Ejecutar y validar las migrations y el comportamiento relevante contra MariaDB 10.6.27 real antes de autorizar producción.

### OBS-02-02 — Concurrencia real pendiente

Completar pruebas con procesos/conexiones separados para doble submit, doble asignación VIN, validación versus auto-cierre y deadlock/retry.

### OBS-02-03 — Idempotencia end-to-end

Cerrar la idempotencia request → consulta para impedir que un retry tardío reinvoque innecesariamente un provider/API externo después de una operación previamente consumida.

### OBS-02-04 — VIN por placa

Mantener `VIN_NOT_AVAILABLE` mientras no exista evidencia contractual verificable del path del VIN. No implementar inferencias heurísticas.

### OBS-02-05 — Cadena histórica de migrations

Registrar como deuda técnica la imposibilidad actual de reconstruir desde cero toda la cadena histórica de migrations MySQL por la dependencia histórica de `adapter_code`. No modificar migrations históricas como parte de este cierre.

### OBS-02-06 — Índices y planes

Validar índices y planes de ejecución con volumen representativo antes de producción.

### Production Gates pendientes

1. Evidencia ejecutada sobre MariaDB 10.6.27 real.
2. Cuatro pruebas de concurrencia real indicadas en OBS-02-02.
3. Idempotencia end-to-end request → consulta cerrada y probada.
4. `VIN_NOT_AVAILABLE` preservado salvo evidencia contractual aprobada.
5. Deuda de migrations históricas registrada y considerada en el procedimiento de despliegue, sin reescribir historia.
6. Índices y `EXPLAIN` validados con volumen representativo.
7. Autorización explícita y separada del Project Owner para producción.

SPRINT-02 queda cerrado como `APPROVED WITH OBSERVATIONS`. El siguiente Sprint permanece `NO DETERMINADO` y no autorizado.

`APPROVED WITH OBSERVATIONS`
