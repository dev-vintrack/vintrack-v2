# VINTrack — SPRINT-07: Notifications, Outbox Delivery & Automation — RESULT

**Fecha:** 2026-08-14
**Entorno:** local/testing
**Estado:** READY FOR OWNER REVIEW
**Producción:** NOT AUTHORIZED / NO MODIFICADA

## 1. Executive Summary

Se activó localmente el outbox transaccional existente mediante un processor
acotado, concurrent-safe y reintentable. Se implementaron entregas independientes
por Portal y Email, bandeja persistente propia, recordatorios del deadline y
comandos Artisan discretos para outbox, deadlines y auto-close. No se creó un
segundo outbox ni se alteró una transición de dominio por fallo de delivery.

## 2. Scope Completed

- Processor con claim transaccional, lease, batch limit, max seconds y máximo de intentos.
- Delivery Portal/Email independiente y deduplicado.
- Bandeja propia con listado paginado, unread/read, contador y enlace autorizado.
- Mail de expediente sin attachments mediante Laravel Mail.
- Recordatorios `T_MINUS_1_DAY` y `DEADLINE_REACHED` sobre deadline persistido.
- Ejecución discreta del lifecycle existente para auto-close.
- Tres comandos directamente invocables desde una futura tarea cPanel Cron.
- Tests funcionales, seguridad, retry, repetición y concurrencia real MySQL.

## 3. Explicit Exclusions

No producción, deployment, Cron real, cPanel, SMTP productivo, secretos, Scheduler
productivo, daemon, worker residente, Supervisor, Horizon, broker, SMS, WhatsApp,
push, websockets, attachments, backfill, retroactivos, SPRINT-08 ni cambios
3/30/90, VIN, wallet o providers.

## 4. Governance

Se leyeron AGENTS, documentos maestros, decisiones, change requests, PROJECT_STATE,
rectores/resultados SPRINT-01 a SPRINT-06 y el rector SPRINT-07. DEC-036 es el
roadmap canónico; DEC-042 cerró SPRINT-06; producción permanece `NOT AUTHORIZED`.
No se detectó conflicto contractual.

## 5. Baseline

Antes de modificar código: **99 tests, 417 assertions, 0 failed**.

## 6. Preflight

MySQL local 8.4.3, 42 tablas y 1.80 MiB. `notification_outbox`,
`portal_notifications` y `notification_deliveries` existían y estaban vacías.
Se inspeccionaron schema, índices, modelos, productores de eventos, Mail,
lifecycle, deadline, auto-close, comandos y Scheduler.

## 7. Existing Notification Infrastructure

Se reutilizaron `notification_outbox`, `portal_notifications`,
`notification_deliveries`, sus `dedup_key`, Laravel Mail, la auditoría de casos
y `NotificationCaseLifecycleService`. El único Scheduler existente era una tarea
de inventario; no era delivery de expedientes.

## 8. Event Inventory

Eventos reales: `CASE_CREATED`, `CASE_SUBMITTED`, `CASE_RESUBMITTED`,
`CASE_REVIEW_STARTED`, `CASE_REJECTED`, `CASE_VALIDATED` y `CASE_AUTO_CLOSED`.
SPRINT-07 agrega las intenciones automáticas `DEADLINE_REMINDER_T_MINUS_1_DAY` y
`DEADLINE_REACHED` sin cambiar el estado del expediente.

## 9. Notification Matrix

| Evento | Recipient | Email | Portal | Dedup | Retry |
|---|---|---:|---:|---|---:|
| CASE_CREATED | Owner | No | Sí | event key + channel | Sí |
| CASE_SUBMITTED / RESUBMITTED | Owner | Sí | Sí | event key + channel | Sí |
| CASE_REVIEW_STARTED | Owner | No | Sí | event key + channel | Sí |
| CASE_REJECTED | Owner | Sí | Sí | event key + channel | Sí |
| CASE_VALIDATED | Owner | Sí | Sí | event key + channel | Sí |
| CASE_AUTO_CLOSED | Owner | Sí | Sí | case/deadline + channel | Sí |
| DEADLINE_REMINDER_T_MINUS_1_DAY | Owner | Sí | Sí | case/type/deadline + channel | Sí |
| DEADLINE_REACHED | Owner | Sí | Sí | case/type/deadline + channel | Sí |

Templates: un template HTML escapado y contenido server-side por tipo. Ninguno
incluye evidencia, datos de otro Policía o attachment.

## 10. Outbox Architecture

El outbox existente sigue siendo único. Los productores lo escriben dentro de
la transacción de dominio. Cada canal es una fila independiente, lo que permite
confirmar Portal aunque Email falle.

## 11. Delivery Architecture

El processor reclama una fila en una transacción corta, confirma `PROCESSING` y
entrega fuera del lock. Después persiste `DELIVERED` o reprograma/finaliza el
fallo. No existe transacción DB abierta durante Mail.

EXPLAIN MySQL 8.4.3 local:

- Claim outbox: `notification_outbox_delivery_idx`, `type=range`, 2 rows
  estimadas, `Using index condition; Using where; Using filesort` por el branch
  de lease vencido.
- Inbox unread: `portal_notifications_inbox_idx`, `type=ref`, 1 row estimada,
  `Backward index scan`.
- Auto-close: `notification_cases_auto_close_idx`, scan covering, 1 row
  estimada, `Using where; Using index; Using filesort`.

El volumen local es mínimo y no aporta evidencia para un índice nuevo. Las tres
operaciones están acotadas por `LIMIT`; selectividad y latencia con volumen
representativo permanecen Production Gate.

## 12. Portal Notification Model

Se reutilizó `portal_notifications`: `outbox_id` único, recipient, case, tipo,
título, body, action path, read_at y created_at.

## 13. Portal Notification Center

`/mi-cuenta/notificaciones` lista sólo filas del usuario autenticado, 20 por
página. Incluye contador unread, badge, mark-read idempotente y redirect
server-side al expediente propio.

## 14. Email Architecture

Laravel Mail resuelve el correo desde `users` por `recipient_user_id` del outbox.
`notification_deliveries` registra destinatario, asunto, estado, intentos,
timestamps, error sanitizado y dedup. Tests usan `Mail::fake`/mailer de pruebas.

## 15. Email Templates

`NotificationCaseMail` usa `emails.notification-case`, texto mínimo por evento,
folio y URL generada por servidor. Blade escapa título, texto, folio y URL.
No existen attachments.

## 16. Recipient Resolution

El browser y el payload no eligen destinatario. El processor carga
`users.id = notification_outbox.recipient_user_id`; si no existe o no tiene email
válido, el canal falla de forma reintentable/acotada.

## 17. Deduplication

Outbox conserva `dedup_key UNIQUE`; Portal usa `outbox_id UNIQUE`; Email usa
`notification_deliveries.dedup_key = notification-case-outbox:{id}`. Los
recordatorios incorporan case, tipo lógico y deadline persistido.

## 18. Delivery Idempotency

Reejecutar comandos no duplica la proyección Portal ni un delivery Email ya
marcado `sent`. El dominio nunca se repite ni revierte por delivery.

## 19. Retry / Backoff

Máximo 5 intentos. Backoff: 30, 60, 120, 240 y 480 segundos (tope técnico 3600).
Tras el máximo queda `FAILED`. Claims `PROCESSING` con lease de más de 10 minutos
son recuperables.

## 20. Failure Handling

Los errores se normalizan a una línea y 1000 caracteres en outbox; delivery
Email guarda la misma versión sanitizada. Un canal fallido no modifica el otro
ni la transición confirmada.

## 21. Crash Window Analysis

Ventana residual: SMTP acepta el correo, el proceso muere y `sent/DELIVERED` no
se persiste. Un retry puede duplicar externamente el email. El sistema ofrece
at-least-once con deduplicación local; **no se afirma exactly-once SMTP**. Resolver
exactly-once requeriría garantía/idempotency key del proveedor externo, hoy no
disponible ni autorizada.

## 22. Concurrency Model

`SELECT ... FOR UPDATE` serializa claims; el status confirmado evita que otro
processor tome la misma fila. Auto-close conserva lock de case, estado canónico,
expected version y event key idempotente.

## 23. Outbox Processor Command

`php artisan notifications:process-outbox --limit=100 --max-seconds=50`.
Límites duros: batch 1..500, tiempo 1..300 segundos.

## 24. Deadline Automation

`php artisan notifications:queue-deadline-reminders --limit=100` examina sólo
`PENDING/REJECTED`, donde el owner aún puede actuar. Encola T-1 día o llegada al
límite con identidades estables.

## 25. 3-Day Rule Automation

No se recalcula ni modifica la regla. Se consume exclusivamente
`notification_deadline_at`, ya persistido como consulta + 3 días a 23:59:59.

## 26. Auto-Close Automation

`php artisan notifications:auto-close --limit=100` llama al lifecycle existente.
No existe transición alternativa. Ejecuciones repetidas producen 1 y luego 0.

## 27. America/Mexico_City

Deadline/lifecycle usan `NotificationCaseSettings::timezone()` y semántica local
contractual. Los recordatorios comparan `CarbonImmutable` en esa timezone.

## 28. cPanel Cron Compatibility

Los comandos son procesos finitos y directamente invocables. No requieren
`schedule:run`, daemon, queue worker ni servicios residentes.

## 29. Bounded Execution

Outbox y deadlines limitan lote a 500; outbox limita además duración a 300 s.
Auto-close limita lote a 500 y opera caso por caso.

## 30. Overlap Protection

Claims con lock/lease protegen outbox; lock/version/estado/event key protegen
auto-close. Dos ejecuciones solapadas fueron probadas con procesos separados.

## 31. Security / IDOR / XSS

Portal aplica recipient autenticado en query y mark-read; IDs ajenos responden
404. El destino se reconstruye en servidor. Contenido no confiable se escapa con
Blade. No se exponen folio/evidencia/case privado de otro usuario.

## 32. Audit / Logging

Eventos de dominio y outbox permanecen separados. Outbox registra attempts,
claim, sent/failed y error; Email reutiliza delivery auditable. No se guardan
secretos ni attachments.

## 33. Database Changes

No hubo cambio de schema, datos productivos ni backfill. Sólo fixtures locales
de concurrencia, eliminados por cleanup verificado.

## 34. Migrations

No se creó migration: las estructuras e índices necesarios ya existían y crear
otra tabla/outbox estaba prohibido. No existe migration SPRINT-07 que someter a
up/down/up. En SQLite desechable el `migrate` completo pasó; el rollback completo
detectó deuda histórica en el `down()` de
`2026_08_05_000002_drop_label_icon_from_menu_permissions_tables` (`NOT NULL` sin
default), sin relación con SPRINT-07. El ciclo acotado de las tres migrations
reversibles recientes pasó `migrate → rollback --step=3 → migrate` y la base
temporal fue eliminada.

## 35. Tests Added

`NotificationDeliveryAutomationTest`: delivery por canal, dedup, batch, retry
terminal, recordatorios repetibles, comandos, scope, unread/read, IDOR y XSS.
Tres scripts support prueban dos processors MySQL sobre el mismo mensaje.

## 36. Tests Executed

- Baseline: **99 tests / 417 assertions / 0 failed**.
- Enfocada final: **5 tests / 32 assertions / 0 failed**.
- Suite completa final: **104 tests / 449 assertions / 0 failed**.
- Regresión SPRINT-04/05/06: **29 tests / 191 assertions / 0 failed**.
- Pint afectados: PASS.
- `git diff --check`: PASS.

## 37. Concurrency Tests

MySQL 8.4.3, procesos PHP separados:

- Outbox: processor A `claimed=1/delivered=1`; B `claimed=0`; una proyección Portal.
- Auto-close: procesos `closed=1` y `closed=0`; estado final cerrado, lock_version 1,
  un evento y dos mensajes de canal.

Un intento inicial con `Start-Process` no arrancó por duplicidad `Path/PATH` de
PowerShell; el fixture quedó sin procesar y fue limpiado. El harness se repitió
exitosamente con jobs/procesos separados.

## 38. Regression

Se preservan double submit, double VIN, validate/reject, validate/auto-close,
reject/auto-close y los portales/historias SPRINT-04/05/06. Regresión enfocada:
**29 tests / 191 assertions / 0 failed**; suite final: **104 / 449 / 0**.

## 39. Files Modified

Archivos SPRINT-07, ordenados por ruta:

- `app/Application/NotificationCases/Services/NotificationCaseCreationService.php`
- `app/Application/NotificationCases/Services/NotificationCaseLifecycleService.php`
- `app/Application/NotificationCases/Services/NotificationCaseMessageFactory.php`
- `app/Application/NotificationCases/Services/NotificationCaseOutboxService.php`
- `app/Application/NotificationCases/Services/NotificationDeadlineReminderService.php`
- `app/Application/NotificationCases/Services/NotificationOutboxProcessor.php`
- `app/Console/Commands/AutoCloseNotificationCases.php`
- `app/Console/Commands/ProcessNotificationOutbox.php`
- `app/Console/Commands/QueueNotificationDeadlineReminders.php`
- `app/Infrastructure/Persistence/Models/NotificationOutbox.php`
- `app/Infrastructure/Persistence/Models/PortalNotification.php`
- `app/Mail/NotificationCaseMail.php`
- `app/Presentation/Http/Controllers/Web/PortalNotificationController.php`
- `docs/ARCHITECTURE.md`
- `docs/DATA_MODEL.md`
- `docs/PROJECT_STATE.md`
- `docs/sprints/SPRINT-07-NOTIFICATIONS-OUTBOX-AUTOMATION.md`
- `docs/sprints/SPRINT-07-RESULT.md`
- `resources/views/customer/notifications/index.blade.php`
- `resources/views/emails/notification-case.blade.php`
- `resources/views/layouts/app.blade.php`
- `routes/web.php`
- `tests/Feature/NotificationCases/NotificationDeliveryAutomationTest.php`
- `tests/Support/cleanup_notification_outbox_concurrency.php`
- `tests/Support/notification_outbox_worker.php`
- `tests/Support/prepare_notification_outbox_concurrency.php`

También permanecen pendientes, sin mezclar su significado con la implementación,
los tres archivos documentales autorizados del cierre Owner de SPRINT-06:
`docs/DECISION_LOG.md`, `docs/PROJECT_STATE.md` y
`docs/sprints/SPRINT-06-RESULT.md`. `PROJECT_STATE.md` fue además actualizado al
estado actual de SPRINT-07.

## 40. Limitations

- No exactly-once externo en SMTP.
- No se verificó MariaDB 10.6.27 ni hosting real.
- No SMTP/cPanel/URL productiva.
- EXPLAIN local usa volumen mínimo; no justifica índices nuevos.
- `notification_deliveries` es infraestructura legacy compartida; el outbox
  `DATETIME(6)` conserva la autoridad operacional del módulo.

## 41. Production Gates

Acumulados: MariaDB 10.6.27; idempotencia request→consulta; EXPLAIN/selectividad
con volumen; deuda histórica migrations; malware scanning; storage/fileinfo/GD/
permisos Neubox; backup/rollback; autorización productiva; DataTables CDN/CSP si
la política aplica.

Nuevos/no cerrados: PHP CLI cPanel, ruta PHP, acceso Artisan, working directory,
frecuencia, timeout/overlap, logs/cache, URL productiva, SMTP, credenciales,
TLS, SPF/DKIM/DMARC, remitente, límites/rate/size y observabilidad de rebotes.

## 42. cPanel Deployment Requirements

TO BE VERIFIED IN PRODUCTION GATE: binario PHP 8.3 (incluido
`/usr/local/bin/php`), ruta absoluta del proyecto, permisos, working directory,
variables/config cache, frecuencia, overlap/timeout, salida/log rotation y tres
entradas Cron discretas. No se inventa ninguna ruta productiva.

## 43. Requirement Traceability

BR-024/DEC-022: email+portal; BR-027: idempotencia; BR-029: timezone; BR-030:
scope; BR-034/DEC-023/030: cPanel commands; DEC-013: auto-close; DEC-021: audit;
DEC-033: permisos; rector §§5-47: outbox/delivery/retry/automation/seguridad.

## 44. Acceptance Criteria

Arquitectura separada, outbox único, canales independientes, recipient servidor,
Portal propio, Email sin attachments, retry/batch/dedup, commands discretos,
repetición, concurrencia, IDOR/XSS y alcance negativo: PASS local. Producción y
compatibilidad real MariaDB/cPanel/SMTP: NOT RUN / Production Gate.

## 45. Recommended Follow-up

Project Owner debe revisar SPRINT-07. No iniciar SPRINT-08 sin autorización
explícita. Antes de producción, ejecutar todos los gates del §41 con backup y
rollback aprobados.

## 46. Sprint Conclusion

Outbox processor: IMPLEMENTADO LOCALMENTE
Portal Notifications: IMPLEMENTADO LOCALMENTE
Email Delivery: IMPLEMENTADO LOCALMENTE / TEST TRANSPORT ONLY
Retry: IMPLEMENTADO LOCALMENTE
Deduplication: IMPLEMENTADO LOCALMENTE
Deadline Automation: IMPLEMENTADO LOCALMENTE
Auto-close Automation: IMPLEMENTADO/PRESERVADO LOCALMENTE
cPanel-compatible commands: IMPLEMENTADOS LOCALMENTE
cPanel Cron real: NO CONFIGURADO
SMTP producción: NO CONFIGURADO
Producción: NO MODIFICADA
SPRINT-08: NO INICIADO

READY FOR OWNER REVIEW

STOP.
