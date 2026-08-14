# VINTrack — SPRINT-07
# Notifications, Outbox Delivery & Automation

**Estado:** PENDING OWNER AUTHORIZATION
**Tipo:** Implementación local + pruebas
**Baseline:** SPRINT-06 APPROVED WITH OBSERVATIONS
**Roadmap canónico:** DEC-036
**Último cierre:** DEC-042
**Producción:** NOT AUTHORIZED
**Cron Neubox:** NOT AUTHORIZED
**Resultado obligatorio:** docs/sprints/SPRINT-07-RESULT.md

---

# 1. OBJETIVO

Implementar localmente el mecanismo operativo de notificaciones y
automatización del proceso de expedientes VINTrack, reutilizando los eventos,
outbox, estados, auditoría y reglas construidos en SPRINT-02 a SPRINT-06.

SPRINT-07 debe proporcionar:

1. procesamiento seguro del outbox;
2. entrega de notificaciones por email;
3. notificaciones persistentes dentro del Portal Cliente;
4. reintentos controlados;
5. idempotencia/deduplicación de delivery;
6. tolerancia a fallos parciales;
7. comandos Artisan compatibles con ejecución mediante cPanel Cron;
8. procesamiento automático de deadlines;
9. auto-close de expedientes vencidos conforme a la regla contractual;
10. pruebas de concurrencia y ejecución repetida;
11. observabilidad/auditoría suficiente;
12. documentación exacta para futura configuración productiva.

NO configurar Cron real en Neubox.

NO modificar producción.

---

# 2. PRINCIPIO ARQUITECTÓNICO

El dominio y el delivery deben permanecer desacoplados.

Flujo conceptual:

DOMAIN TRANSACTION
       |
       +-- cambia estado
       +-- audit/event
       +-- outbox
              |
          COMMIT DB
              |
              v
       OUTBOX PROCESSOR
          /       \
         v         v
      EMAIL     PORTAL
         \         /
          v       v
       DELIVERY RECORD

Una transición de dominio confirmada NO debe revertirse porque:

- SMTP falle;
- Laravel Mail falle;
- el destinatario sea temporalmente inaccesible;
- el proceso Cron termine inesperadamente;
- una notificación en portal falle.

El outbox permite desacoplar ambos procesos.

---

# 3. FUENTES OBLIGATORIAS

Antes de modificar código leer completamente:

- AGENTS.md
- docs/VINTRACK_MASTER_SPEC.md
- docs/ARCHITECTURE.md
- docs/BUSINESS_RULES.md
- docs/DATA_MODEL.md
- docs/DECISION_LOG.md
- docs/CHANGE_REQUESTS.md
- docs/PROJECT_STATE.md

y los rectores/resultados de:

- SPRINT-01
- SPRINT-02
- SPRINT-03
- SPRINT-04
- SPRINT-05
- SPRINT-06

Prestar especial atención a:

- eventos de dominio;
- notification outbox;
- notification deliveries;
- dedup_key;
- auditoría;
- auto-close;
- deadlines;
- lifecycle;
- mail existente;
- comandos Artisan existentes;
- Laravel Scheduler existente;
- configuración de timezone;
- restricciones Neubox/cPanel.

No asumir nombres de tablas/clases/campos.

La implementación real prevalece como evidencia técnica cuando no contradiga
una decisión contractual aprobada.

---

# 4. PREFLIGHT OBLIGATORIO

Antes de implementar:

1. verificar PROJECT_STATE;
2. verificar DEC-036;
3. verificar DEC-042;
4. ejecutar suite completa;
5. registrar baseline exacto;
6. inspeccionar tablas de outbox;
7. inspeccionar tablas de delivery;
8. inspeccionar event log;
9. inspeccionar dedup_key existente;
10. inspeccionar lifecycle;
11. identificar todos los eventos que actualmente generan outbox;
12. identificar payload real de cada evento;
13. inspeccionar Laravel Mail actual;
14. inspeccionar configuración mail;
15. inspeccionar queues;
16. inspeccionar Jobs;
17. inspeccionar comandos Artisan;
18. inspeccionar Scheduler;
19. inspeccionar timezone;
20. inspeccionar auto-close existente;
21. inspeccionar deadline calculation;
22. inspeccionar mecanismo actual de alertas;
23. inspeccionar templates email existentes;
24. inspeccionar layout Portal Cliente;
25. inspeccionar roles/capabilities;
26. identificar infraestructura reusable;
27. identificar posibles N+1;
28. identificar transacciones/locks;
29. identificar riesgos de delivery duplicado;
30. identificar riesgos de Cron solapado.

Documentar hallazgos antes de implementar.

---

# 5. NO CREAR SEGUNDO OUTBOX

SPRINT-02 ya introdujo infraestructura de eventos/outbox.

SPRINT-07 debe reutilizarla.

No crear:

notification_outbox_v2
new_notification_queue
case_email_queue

o equivalentes paralelos salvo incompatibilidad material demostrada.

Si la infraestructura actual necesita extensión aditiva, documentar la
necesidad.

No sustituir silenciosamente el diseño existente.

---

# 6. EVENTOS COMO FUENTE

El delivery debe partir de eventos/outbox confirmados.

No inferir periódicamente todos los emails exclusivamente observando el
estado actual de notification_cases.

Ejemplo incorrecto:

SELECT * FROM notification_cases
WHERE status = 'REJECTED'
→ enviar email

Eso podría reenviar indefinidamente.

Preferencia:

evento efectivo
→ outbox único
→ delivery idempotente.

---

# 7. EVENTOS A INVENTARIAR

Codex debe identificar los eventos reales existentes.

Como mínimo evaluar eventos equivalentes a:

- CASE_CREATED
- CASE_SUBMITTED
- CASE_RESUBMITTED
- CASE_REVIEW_STARTED
- CASE_REJECTED
- CASE_VALIDATED
- CASE_CLOSED_NO_FOLLOW_UP
- deadline/reminder events existentes
- VIN assignment si genera comunicación
- cualquier evento contractual existente

No inventar notificación para todos automáticamente.

Primero definir cuáles son user-facing.

---

# 8. MATRIZ DE NOTIFICACIONES

Antes de codificar, construir una matriz explícita:

EVENT
RECIPIENT
EMAIL?
PORTAL?
DEDUP KEY
TEMPLATE
RETRYABLE?

Debe quedar documentada en SPRINT-07-RESULT.

Baseline funcional esperado:

CASE_CREATED
→ Cliente responsable
→ Portal
→ email sólo si ya está contractualmente previsto

CASE_SUBMITTED
→ Cliente
→ confirmación portal/email cuando corresponda

CASE_REJECTED
→ Cliente
→ email + portal
→ incluir instrucción de corregir
→ incluir motivo permitido

CASE_VALIDATED
→ Cliente
→ email + portal

CASE_CLOSED_NO_FOLLOW_UP
→ Cliente
→ email + portal

Recordatorios de deadline
→ Cliente
→ email + portal

No enviar evidencia adjunta por email.

No incluir información sensible innecesaria.

---

# 9. NOTIFICACIONES OBLIGATORIAS DEL PROCESO

Preservar el requisito aprobado de notificar automáticamente al Cliente en
los momentos contractuales definidos previamente.

Como mínimo revisar y cubrir:

- creación/asignación de expediente;
- proximidad del plazo de 3 días;
- llegada al límite cuando corresponda;
- rechazo;
- validación;
- cierre automático por falta de seguimiento;
- cambios relevantes previamente aprobados.

La matriz final debe derivarse de BUSINESS_RULES / Master Spec / decisiones.

Si existe contradicción, detener sólo esa notificación y reportarla.

---

# 10. PORTAL NOTIFICATIONS

Implementar notificación persistente dentro del Portal Cliente.

Debe existir una fuente de datos server-side.

No implementar simplemente:

session flash

porque la notificación debe sobrevivir a:

- logout/login;
- cambio de dispositivo;
- navegación;
- tiempo transcurrido.

---

# 11. MODELO DE NOTIFICACIÓN EN PORTAL

Antes de crear schema nuevo, inspeccionar si Laravel/database notifications o
infraestructura equivalente ya existe.

Reutilizar infraestructura existente si satisface:

- recipient user;
- type/event;
- title;
- message;
- reference;
- created_at;
- read/unread;
- deduplication.

Si no existe mecanismo persistente adecuado, se permite diseño aditivo mínimo.

Cualquier migration debe:

- ser portable MySQL 8.4.3 / MariaDB 10.6.27;
- tener down();
- no depender de features exclusivas de MySQL 8;
- probar up/down/up localmente.

---

# 12. PRIVACIDAD DE PORTAL NOTIFICATION

Una notificación del Cliente no debe revelar:

- datos de otro Policía;
- evidencia ajena;
- datos internos del Analista;
- stack traces;
- payload técnico;
- información de otro expediente no autorizado.

Las URLs deben pasar por autorización normal.

Una URL dentro de una notificación NO concede acceso.

---

# 13. READ / UNREAD

Si se implementa Notification Center:

permitir:

- listar propias;
- contador no leídas;
- marcar propia como leída.

No permitir:

- marcar notificación ajena;
- consultar notificación ajena.

No es necesario implementar un centro de mensajería complejo.

---

# 14. EMAIL

Reutilizar Laravel Mail/configuración existente.

No introducir proveedor de email externo nuevo en este Sprint.

No hardcodear credenciales.

No modificar secretos productivos.

Local/testing debe utilizar:

- Mail fake;
- log/test transport;
- configuración segura equivalente.

Nunca enviar correo real accidentalmente durante tests.

---

# 15. EMAIL CONTENT

Los emails deben ser transaccionales y claros.

Como mínimo pueden contener:

- folio;
- estado;
- fecha relevante;
- deadline cuando aplique;
- instrucción de acción;
- motivo de rechazo cuando corresponda;
- URL segura al Portal.

No adjuntar:

- PDFs;
- JPG;
- PNG;
- evidencia.

No enviar datos sensibles innecesarios.

---

# 16. EMAIL NORMALIZATION

Los templates deben usar las etiquetas humanas del dominio.

Evitar mostrar:

CASE_REJECTED
CLOSED_NO_FOLLOW_UP

directamente al usuario si existen etiquetas amigables aprobadas.

Ejemplo:

REJECTED
→ RECHAZADO

VALIDATED
→ VALIDADO

CLOSED_NO_FOLLOW_UP
→ CERRADO POR FALTA DE SEGUIMIENTO

---

# 17. DELIVERY RECORD

Cada intento de delivery debe ser trazable.

Inspeccionar `notification_deliveries` existente.

Preferencia:

outbox
   |
   +-- channel EMAIL
   +-- channel PORTAL

Registrar según infraestructura real:

- event/outbox reference;
- recipient;
- channel;
- dedup key;
- status;
- attempts;
- timestamps;
- error resumido seguro.

No almacenar secretos.

---

# 18. IDEMPOTENCIA

Requisito crítico.

Procesar dos veces el mismo outbox NO puede producir dos entregas efectivas
equivalentes.

La idempotencia debe estar respaldada por DB cuando sea posible.

No depender únicamente de:

if (...) {
   send();
}

sin constraint/lock/deduplication.

---

# 19. DEDUP KEY

Reutilizar el `dedup_key` existente cuando sea correcto.

La clave debe distinguir suficientemente:

evento
+ destinatario
+ canal

o contrato equivalente existente.

EMAIL y PORTAL pueden requerir deliveries independientes.

Documentar formato real.

---

# 20. CONCURRENCIA OUTBOX

Probar dos procesos reales intentando consumir el mismo outbox.

Resultado esperado:

- una entrega lógica por canal;
- no doble email lógico;
- no doble portal notification;
- estado consistente;
- attempts coherentes.

Usar procesos separados cuando sea técnicamente viable, como en Sprints
anteriores.

---

# 21. CLAIM / LOCK

El processor debe tener mecanismo seguro para reclamar trabajo.

Evaluar patrón compatible MySQL/MariaDB:

- transaction;
- SELECT ... FOR UPDATE;
- status transition;
- claim token;
- lease;
- otro patrón demostrado.

No asumir SKIP LOCKED si introduce incompatibilidad o comportamiento diferente
no probado en MariaDB 10.6.

---

# 22. NO MANTENER LOCK DURANTE SMTP

No mantener una transacción DB/row lock abierta durante una llamada externa de
email si puede evitarse.

Diseñar:

claim
→ commit
→ external delivery
→ persist result

con estrategia segura ante crash.

---

# 23. CRASH WINDOW

Analizar explícitamente:

1. claim realizado;
2. email enviado;
3. proceso muere antes de marcar delivered.

Éste es un problema clásico de exactly-once externo.

No afirmar exactly-once absoluto si SMTP no proporciona idempotency key.

Objetivo real:

- at-least-once processing;
- deduplicación interna;
- minimizar duplicados externos;
- trazabilidad;
- retries seguros.

Documentar esta limitación.

---

# 24. DELIVERY STATUS

Usar estados existentes si ya están definidos.

Si requieren extensión, mantener conjunto pequeño equivalente a:

PENDING
PROCESSING
DELIVERED
FAILED/RETRYABLE
DEAD

No crear máquina compleja sin necesidad.

---

# 25. RETRIES

Los errores temporales deben poder reintentarse.

Usar:

attempt count
next_attempt_at

o infraestructura equivalente existente.

No hacer loop inmediato ilimitado.

Aplicar backoff determinista.

Ejemplo conceptual:

attempt 1 → +5 min
attempt 2 → +15 min
attempt 3 → +1 h
attempt 4 → +6 h

No adoptar necesariamente estos valores si ya existe política aprobada.

Documentar política final.

---

# 26. MAX ATTEMPTS

Definir máximo configurable razonable.

Después del máximo:

- no borrar outbox;
- conservar trazabilidad;
- marcar fallo terminal;
- permitir diagnóstico/manual retry futuro si se aprueba.

No implementar UI administrativa compleja de retry salvo que ya exista
requisito.

---

# 27. ERROR HANDLING

Guardar mensajes sanitizados.

No persistir:

- password SMTP;
- tokens;
- credenciales;
- stack trace completo;
- payload sensible innecesario.

Los logs técnicos pueden conservar detalle apropiado según configuración.

---

# 28. PORTAL DELIVERY VS EMAIL DELIVERY

Un canal puede funcionar aunque el otro falle.

Ejemplo:

PORTAL = DELIVERED
EMAIL = RETRYABLE

No revertir portal notification porque email falló.

No considerar el evento completo perdido.

---

# 29. DEADLINE AUTOMATION

SPRINT-07 debe inspeccionar cómo se generan actualmente:

- notification_deadline_at;
- auto-close deadline;
- reminder events.

No recalcular reglas contractuales de forma divergente.

Timezone empresarial:

America/Mexico_City

---

# 30. REGLA DE 3 DÍAS

La fecha límite contractual permanece:

3 días calendario
hasta 23:59:59
America/Mexico_City

No cambiarla.

SPRINT-07 automatiza notificaciones relacionadas.

No redefine la regla.

---

# 31. RECORDATORIOS

Antes de crear recordatorios, inspeccionar Master Spec/Business Rules y
decisiones.

La implementación debe evitar enviar el mismo recordatorio repetidamente cada
vez que Cron corre.

Cada recordatorio lógico debe producir una identidad/dedup key única.

Ejemplo conceptual:

CASE 123
DEADLINE_REMINDER
T_MINUS_1_DAY

No fijar este evento si no coincide con reglas aprobadas.

---

# 32. BLOQUEO DE CONSULTAS

SPRINT-07 NO debe reimplementar el bloqueo de consultas.

SPRINT-02 ya contiene admission/control correspondiente.

Los procesos automáticos pueden cambiar estados que afecten indirectamente el
conteo de pendientes.

El próximo request de consulta aplicará las reglas existentes.

---

# 33. AUTO-CLOSE 30 DÍAS

Preservar:

ningún expediente abierto debe permanecer abierto más allá del plazo
contractual aplicable.

Al llegar al límite:

→ CLOSED_NO_FOLLOW_UP

según lifecycle existente.

SPRINT-07 debe automatizar la ejecución del mecanismo existente, no crear una
segunda transición alternativa.

---

# 34. AUTO-CLOSE IDEMPOTENTE

Ejecutar auto-close dos veces debe producir:

- una sola transición efectiva;
- un solo evento;
- un solo outbox lógico;
- ningún duplicado.

Las carreras ya probadas en SPRINT-05 deben preservarse.

---

# 35. AUTO-CLOSE CONCURRENCY

Mantener verdes las carreras:

validate vs auto-close
reject vs auto-close

Agregar, si es necesario:

auto-close vs auto-close

con procesos reales.

Resultado:

una única transición legal.

---

# 36. COMMANDS ARTISAN

Crear/reutilizar comandos pequeños y explícitos.

Preferencia conceptual:

php artisan vintrack:process-notification-outbox

php artisan vintrack:process-case-deadlines

php artisan vintrack:auto-close-cases

Los nombres reales deben seguir convenciones existentes.

No es obligatorio crear tres comandos si una separación distinta es mejor.

Evitar un comando monolítico opaco.

---

# 37. CPANEL CRON COMPATIBILITY

Los comandos deben poder invocarse directamente desde Cron sin requerir:

- SSH interactivo;
- queue worker residente;
- supervisor;
- systemd;
- daemon;
- Laravel Scheduler.

La arquitectura debe funcionar mediante ejecuciones discretas.

---

# 38. NO DEPENDER DE QUEUE WORKER PERMANENTE

Neubox compartido no garantiza workers residentes.

SPRINT-07 no puede requerir:

php artisan queue:work

ejecutándose permanentemente.

Si Laravel Queue se reutiliza, debe existir mecanismo explícito compatible con
ejecución discreta por Cron.

Preferencia:

commands bounded/batch.

---

# 39. NO DEPENDER DE schedule:run

Producción no puede asumir Laravel Scheduler.

Aunque exista código:

Schedule::...

SPRINT-07 debe proporcionar comandos directamente invocables.

No eliminar Scheduler existente si sirve localmente.

Simplemente no depender de él para producción.

---

# 40. BATCH SIZE

Cada ejecución debe procesar un número limitado/configurable de elementos.

Ejemplo conceptual:

--limit=50

o configuración equivalente.

Evitar que un Cron permanezca indefinidamente activo.

---

# 41. TIME BUDGET

Considerar hosting compartido.

El comando debe poder terminar limpiamente.

No crear:

while (true)

ni daemon.

Opcionalmente permitir:

--limit
--max-seconds

si aporta seguridad.

---

# 42. OVERLAPPING CRON

Diseñar suponiendo que dos ejecuciones pueden solaparse.

No depender exclusivamente de que cPanel nunca lance dos instancias.

DB locking/idempotency debe preservar consistencia.

---

# 43. LOCK GLOBAL OPCIONAL

Evaluar si hace falta un lock global de comando.

No usar filesystem lock como única garantía contractual si múltiples procesos
pueden ejecutarse en contextos distintos.

La DB sigue siendo la autoridad.

---

# 44. SALIDA DEL COMMAND

Los comandos deben producir salida útil para Cron/log:

processed
delivered
retried
failed
skipped

Sin imprimir secretos ni payloads sensibles.

Exit codes razonables.

---

# 45. DRY-RUN

Para procesos de deadlines/auto-close puede implementarse `--dry-run` si es
seguro y útil.

Dry-run:

- no cambia estado;
- no genera outbox;
- no crea deliveries.

No es obligatorio si complica la autoridad del dominio.

---

# 46. CONFIGURACIÓN

Valores operativos deben estar en config/env cuando corresponda:

- batch size;
- retry max;
- backoff;
- sender;
- portal base URL;
- command limits.

No hardcodear rutas productivas Neubox.

No almacenar secretos en repositorio.

---

# 47. TIMEZONE

Todas las comparaciones contractuales de fecha/hora del expediente:

America/Mexico_City

No depender del timezone del servidor.

No cambiar timestamps históricos arbitrariamente.

---

# 48. NOTIFICATION CENTER — PORTAL CLIENTE

Implementar una UI mínima coherente con el portal.

Como mínimo:

- contador no leídas;
- listado;
- fecha;
- título;
- mensaje;
- estado leído/no leído;
- enlace cuando corresponda.

No convertir SPRINT-07 en rediseño completo del dashboard.

---

# 49. NOTIFICATION CENTER — ACCIONES

Se permite:

VER
MARCAR COMO LEÍDA

No implementar:

delete
reply
forward
edit

salvo infraestructura previa obligatoria.

---

# 50. ADMINISTRATIVE VISIBILITY

No es requisito crear Notification Center para Admin.

El Analista ya trabaja mediante Proceso de Notificaciones.

Si eventos requieren email/admin notification según reglas existentes,
evaluarlos en la matriz.

No ampliar UI administrativa sin contrato.

---

# 51. EMAIL DESTINATION

Usar el email real asociado al usuario conforme al modelo actual.

No aceptar destinatario desde request/browser.

No permitir que payload outbox arbitrario sustituya el recipient sin
validación.

---

# 52. USER WITHOUT EMAIL

Definir comportamiento seguro.

Portal notification puede seguir entregándose.

Email debe:

- marcarse skipped/no-recipient o equivalente;
- no bloquear otros canales;
- quedar trazable.

No inventar email.

---

# 53. DISABLED/DELETED USER

Inspeccionar comportamiento real de users.

Definir cómo tratar recipient no disponible.

No reasignar automáticamente a otro usuario.

---

# 54. LINKS

Los links de email/portal deben apuntar a rutas autorizadas.

No incluir signed URL que eluda autorización normal salvo necesidad aprobada.

No exponer storage privado.

---

# 55. HTML ESCAPING

Motivo de rechazo, folio, notas u otros valores capturados deben escaparse
correctamente en email/portal.

No renderizar HTML arbitrario capturado por usuario.

Probar XSS.

---

# 56. AUDITORÍA

No duplicar domain event log como delivery audit.

Separar:

Domain:
qué ocurrió.

Delivery:
qué se intentó comunicar.

Ambos deben poder correlacionarse.

---

# 57. LOGGING

Registrar:

- command start/end;
- batch summary;
- failures sanitizados;
- retries;
- dead deliveries.

Evitar ruido por cada polling vacío si no aporta valor.

---

# 58. TESTS — OUTBOX

Probar:

- pending item;
- delivered item;
- retryable item;
- terminal failure;
- repeated processor;
- duplicate dedup key;
- malformed payload;
- missing recipient;
- unknown event type.

---

# 59. TESTS — EMAIL

Usar Mail::fake o mecanismo equivalente.

Probar:

- recipient correcto;
- template correcto;
- folio;
- deadline;
- rechazo/motivo;
- validación;
- cierre;
- escaping;
- no evidence attachments.

---

# 60. TESTS — PORTAL

Probar:

- persistencia;
- unread;
- read;
- count;
- ownership;
- IDOR;
- link autorizado;
- duplicate processor;
- XSS escaping.

---

# 61. TESTS — CHANNEL FAILURE

Probar:

EMAIL fails
PORTAL succeeds

y:

PORTAL fails
EMAIL succeeds

cuando sea técnicamente representable.

El evento no debe revertir su domain transition.

---

# 62. TESTS — RETRY

Probar:

attempt 1 fails
→ next attempt programado

antes de next_attempt
→ no procesa

después
→ retry

success
→ delivered

No generar segunda portal notification.

---

# 63. TESTS — CONCURRENCY OUTBOX

Ejecutar dos procesos separados contra MySQL local cuando sea viable.

Ambos intentan procesar el mismo trabajo.

Verificar:

- no doble delivery lógico;
- dedup DB;
- estado final consistente.

Documentar resultado exacto.

---

# 64. TESTS — AUTO-CLOSE

Probar:

- case vencido;
- case no vencido;
- VALIDATED no cierra;
- REJECTED según lifecycle aprobado;
- repeated command;
- auto-close vs auto-close;
- validate vs auto-close regresión;
- reject vs auto-close regresión.

---

# 65. TESTS — DEADLINES

Probar alrededor de:

23:59:58
23:59:59
00:00:00

America/Mexico_City

No depender de timezone del equipo de desarrollo.

Usar clock controlado.

---

# 66. TESTS — CRON STYLE EXECUTION

Ejecutar los comandos como procesos discretos.

Verificar:

run
→ exit

run nuevamente
→ idempotente

No dejar worker residente.

---

# 67. TESTS — BATCH

Con más items que el límite:

primera ejecución procesa batch limitado.

segunda ejecución continúa.

No pierde ni duplica trabajo.

---

# 68. TESTS — REGRESIÓN

Baseline esperado al inicio:

99 tests
417 assertions
0 fallos

Registrar baseline real.

Preservar especialmente:

- admission rule;
- 3 pendientes;
- creación case;
- folios;
- double submit;
- Evidence;
- Cliente;
- Admin;
- rechazo/resubmit;
- double VIN;
- validate/reject;
- validate/auto-close;
- reject/auto-close;
- histories;
- 90-day projection;
- IDOR;
- outbox existente;
- wallet/provider.

---

# 69. MYSQL / MARIADB PORTABILITY

Todo SQL/migration debe ser compatible con:

MySQL 8.4.3
MariaDB 10.6.27

No utilizar sin justificación:

- features exclusivas MySQL 8;
- índices incompatibles;
- tipos incompatibles;
- sintaxis no validable en MariaDB.

La validación real MariaDB sigue siendo Production Gate mientras no exista
entorno disponible.

---

# 70. MIGRATIONS

Se permiten únicamente migrations necesarias para SPRINT-07.

Ejemplos posibles:

- persistencia Portal Notifications;
- campos operativos mínimos de delivery;
- índices demostrados necesarios;
- menú Notification Center.

Toda migration:

- aditiva;
- reversible;
- portable;
- probada up/down/up.

No alterar datos históricos innecesariamente.

---

# 71. MENU

Si se requiere nueva opción:

`Notificaciones`

en Portal Cliente.

Usar migration reversible siguiendo patrón SPRINT-04/05/06.

No habilitar para roles no autorizados.

---

# 72. NO PRODUCCIÓN

Prohibido:

- configurar cPanel Cron;
- modificar cron jobs Neubox;
- subir archivos;
- cambiar .env productivo;
- configurar SMTP productivo;
- ejecutar migrations productivas;
- probar correo real productivo;
- tocar BD productiva.

SPRINT-07 termina localmente.

---

# 73. DOCUMENTACIÓN PARA FUTURO CPANEL

Aunque NO se configure producción, documentar:

- comandos exactos Artisan;
- parámetros;
- frecuencia recomendada;
- exclusión de Scheduler;
- requisitos PHP CLI;
- working directory;
- logging;
- batch size;
- prevención de overlap;
- variables requeridas;
- rollback conceptual.

NO inventar ruta PHP real de Neubox.

Debe quedar marcada:

TO BE VERIFIED IN PRODUCTION GATE.

---

# 74. FRECUENCIAS

No asumir frecuencia definitiva sin analizar SLA.

Proponer frecuencias para revisión posterior.

Ejemplo:

outbox
→ cada 5 minutos

deadlines
→ cada 5/10/15 minutos

auto-close
→ cada hora

Pero NO registrar estas frecuencias como contrato productivo hasta Production
Gate.

---

# 75. PRODUCTION GATES EXISTENTES

Permanecen:

- MariaDB 10.6.27;
- idempotencia request → consulta;
- EXPLAIN con volumen;
- deuda histórica migrations;
- malware scanning;
- storage/fileinfo/GD/permisos Neubox;
- backup/rollback;
- autorización productiva.

SPRINT-07 no debe declararlos cerrados sin evidencia.

---

# 76. NUEVOS PRODUCTION GATES POTENCIALES

SPRINT-07 debe evaluar y, si aplica, registrar:

- PHP CLI real disponible en cPanel Cron;
- ruta PHP CLI;
- ejecución Artisan desde Cron;
- working directory;
- SMTP real;
- From/Reply-To;
- DNS SPF/DKIM/DMARC cuando aplique;
- límites de correo del hosting;
- timeout Cron;
- overlap real;
- permisos de logs/cache;
- URL productiva usada en emails.

No intentar verificarlos en producción sin autorización.

---

# 77. OUT OF SCOPE

No implementar:

- producción;
- Cron real;
- deployment;
- SMS;
- WhatsApp;
- push notifications;
- websockets;
- realtime;
- queue worker permanente;
- Supervisor;
- Horizon;
- external message broker;
- nuevo proveedor email;
- evidence attachments;
- analytics;
- dashboard de delivery complejo;
- retry UI administrativo;
- modificación de reglas 3/30/90;
- retroactivos;
- backfill;
- reapertura VALIDATED;
- cambios wallet/provider.

---

# 78. ORDEN DE IMPLEMENTACIÓN

1. Governance.
2. Baseline.
3. Preflight.
4. Inventario de eventos/outbox.
5. Matriz de notificaciones.
6. Diseño delivery.
7. Tests delivery.
8. Portal notification persistence.
9. Portal Notification Center.
10. Email templates.
11. Email delivery.
12. Deduplication.
13. Retry/backoff.
14. Processor command.
15. Deadline automation.
16. Auto-close command/integration.
17. Cron-compatible bounded commands.
18. Concurrency.
19. Security.
20. Regression.
21. Migration rollback tests.
22. Pint.
23. git diff --check.
24. Documentation.
25. SPRINT-07-RESULT.
26. STOP.

---

# 79. CONDICIONES DE PARADA

Detener la parte afectada si:

- el outbox existente no puede representar delivery sin romper contrato;
- aparece contradicción sobre destinatarios;
- una notificación contractual no puede determinarse;
- se requiere cambiar lifecycle;
- se requiere cambiar regla 3/30/90;
- se requiere producción;
- se requiere Scheduler obligatorio;
- se requiere daemon permanente;
- una migration no es portable;
- se requiere secreto productivo;
- una solución puede provocar pérdida silenciosa de delivery.

No detener el Sprint por errores ordinarios corregibles.

---

# 80. CRITERIOS DE ACEPTACIÓN

## Governance
- [ ] DEC-036 canónico.
- [ ] DEC-042 cierre SPRINT-06.
- [ ] producción no autorizada.

## Architecture
- [ ] domain separado de delivery.
- [ ] outbox existente reutilizado.
- [ ] no segundo outbox.
- [ ] delivery auditable.

## Portal
- [ ] notificaciones persistentes.
- [ ] sólo propias.
- [ ] unread/read.
- [ ] contador.
- [ ] IDOR protegido.
- [ ] deduplicación.

## Email
- [ ] eventos contractuales.
- [ ] recipient server-side.
- [ ] Mail fake en tests.
- [ ] sin evidence attachments.
- [ ] failure no revierte dominio.

## Outbox
- [ ] processor bounded.
- [ ] idempotente.
- [ ] concurrent-safe.
- [ ] retries.
- [ ] dedup.
- [ ] errores sanitizados.

## Automation
- [ ] comandos directos Artisan.
- [ ] no dependencia de Scheduler.
- [ ] no daemon.
- [ ] deadline automation.
- [ ] auto-close.
- [ ] repeated execution safe.
- [ ] overlap safe.

## Dates
- [ ] America/Mexico_City.
- [ ] límites probados.
- [ ] clocks controlados.

## Compatibility
- [ ] MySQL local.
- [ ] diseño MariaDB-compatible.
- [ ] cPanel-compatible conceptualmente.

## Quality
- [ ] suite completa verde.
- [ ] concurrencia documentada.
- [ ] migration rollback.
- [ ] Pint afectados.
- [ ] git diff --check.
- [ ] producción intacta.

---

# 81. SPRINT-07-RESULT.md

Generar:

docs/sprints/SPRINT-07-RESULT.md

Estructura mínima:

1. Executive Summary
2. Scope Completed
3. Explicit Exclusions
4. Governance
5. Baseline
6. Preflight
7. Existing Notification Infrastructure
8. Event Inventory
9. Notification Matrix
10. Outbox Architecture
11. Delivery Architecture
12. Portal Notification Model
13. Portal Notification Center
14. Email Architecture
15. Email Templates
16. Recipient Resolution
17. Deduplication
18. Delivery Idempotency
19. Retry / Backoff
20. Failure Handling
21. Crash Window Analysis
22. Concurrency Model
23. Outbox Processor Command
24. Deadline Automation
25. 3-Day Rule Automation
26. Auto-Close Automation
27. America/Mexico_City
28. cPanel Cron Compatibility
29. Bounded Execution
30. Overlap Protection
31. Security / IDOR / XSS
32. Audit / Logging
33. Database Changes
34. Migrations
35. Tests Added
36. Tests Executed
37. Concurrency Tests
38. Regression
39. Files Modified
40. Limitations
41. Production Gates
42. cPanel Deployment Requirements
43. Requirement Traceability
44. Acceptance Criteria
45. Recommended Follow-up
46. Sprint Conclusion

Incluir cifras exactas.

---

# 82. ATTESTATION FINAL

Outbox processor:
IMPLEMENTADO LOCALMENTE

Portal Notifications:
IMPLEMENTADO LOCALMENTE

Email Delivery:
IMPLEMENTADO LOCALMENTE / TEST TRANSPORT ONLY

Retry:
IMPLEMENTADO LOCALMENTE

Deduplication:
IMPLEMENTADO LOCALMENTE

Deadline Automation:
IMPLEMENTADO LOCALMENTE

Auto-close Automation:
IMPLEMENTADO/PRESERVADO LOCALMENTE

cPanel-compatible commands:
IMPLEMENTADOS LOCALMENTE

cPanel Cron real:
NO CONFIGURADO

SMTP producción:
NO CONFIGURADO

Producción:
NO MODIFICADA

SPRINT-08:
NO INICIADO

Finalizar:

READY FOR OWNER REVIEW

STOP.