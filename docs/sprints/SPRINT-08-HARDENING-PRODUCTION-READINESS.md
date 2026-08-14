# VINTrack — SPRINT-08
# Hardening & Production Readiness

**Estado:** PENDING OWNER AUTHORIZATION
**Tipo:** Hardening local + Production Readiness Assessment
**Baseline:** SPRINT-07 APPROVED WITH OBSERVATIONS
**Roadmap canónico:** DEC-036
**Último cierre:** DEC-043
**Producción:** NOT AUTHORIZED
**Deployment:** NOT AUTHORIZED
**Cron Neubox:** NOT AUTHORIZED
**SMTP productivo:** NOT AUTHORIZED

**Resultado obligatorio:**
docs/sprints/SPRINT-08-RESULT.md

**Entregables operativos obligatorios:**
docs/production/PRODUCTION-READINESS-REPORT.md
docs/production/DEPLOYMENT-RUNBOOK.md
docs/production/ROLLBACK-RUNBOOK.md

---

# 1. OBJETIVO

SPRINT-08 es el Sprint de hardening y evaluación de preparación productiva
del proceso de expedientes/notificaciones VINTrack construido durante
SPRINT-01 a SPRINT-07.

NO es un Sprint de deployment.

NO autoriza modificar producción.

NO autoriza configurar cPanel Cron.

NO autoriza configurar SMTP productivo.

NO autoriza ejecutar migrations en producción.

El objetivo es:

1. cerrar todos los Production Gates que puedan demostrarse de forma segura
   fuera de producción;

2. fortalecer riesgos técnicos identificados durante SPRINT-02 a SPRINT-07;

3. verificar regresión integral;

4. validar idempotencia end-to-end request → consultation;

5. evaluar performance con volumen representativo;

6. evaluar y, cuando sea seguro, resolver deuda de migrations;

7. realizar hardening de seguridad;

8. validar portabilidad MySQL/MariaDB cuando exista entorno verificable;

9. preparar procedimientos exactos de deployment, backup y rollback;

10. separar explícitamente:
    - VERIFIED;
    - VERIFIED LOCALLY;
    - NOT VERIFIED;
    - BLOCKED BY PRODUCTION ENVIRONMENT;

11. emitir un Production Readiness Verdict.

---

# 2. PRINCIPIO RECTOR

SPRINT-08 NO debe convertir supuestos en evidencia.

Un requisito sólo puede marcarse VERIFIED cuando exista evidencia reproducible.

Ejemplos:

MySQL local ejecutado:
VERIFIED LOCALLY

MariaDB 10.6.27 no disponible:
NOT VERIFIED

PHP CLI documentado por hosting pero no probado:
NOT VERIFIED

Cron diseñado pero no ejecutado en Neubox:
NOT VERIFIED

No utilizar frases ambiguas como:

"should work"
"probably compatible"
"expected to work"

para cerrar Production Gates.

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

y todos los rectores/resultados:

- SPRINT-00
- SPRINT-01
- SPRINT-02
- SPRINT-03
- SPRINT-04
- SPRINT-05
- SPRINT-06
- SPRINT-07

Construir una tabla consolidada de:

OBSERVATION
ORIGIN SPRINT
STATUS
PRODUCTION GATE?
SPRINT-08 ACTION
FINAL EVIDENCE

No perder observaciones históricas.

---

# 4. GOVERNANCE PREFLIGHT

Antes de modificar código confirmar:

- DEC-036 = roadmap canónico;
- DEC-043 = cierre SPRINT-07;
- Current Sprint = None;
- SPRINT-08 = PENDING OWNER AUTHORIZATION;
- Production = NOT AUTHORIZED.

Después de autorización:

SPRINT-08 puede pasar a IN PROGRESS.

Producción debe continuar:

NOT AUTHORIZED.

---

# 5. BASELINE TÉCNICO

Ejecutar antes de cambios:

- suite completa;
- regresiones críticas;
- Pint según baseline;
- git diff --check;
- estado de migrations;
- versión PHP;
- versión Laravel;
- versión DB;
- timezone;
- extensiones relevantes;
- filesystem/storage;
- configuración de Mail sin revelar secretos.

Baseline esperado por resultado de SPRINT-07:

104 tests
449 assertions
0 fallos

Registrar el resultado real.

No asumirlo.

---

# 6. INVENTARIO DE PRODUCTION GATES

Como mínimo consolidar:

PG-01 MariaDB 10.6.27
PG-02 idempotencia end-to-end request → consultation
PG-03 EXPLAIN con volumen representativo
PG-04 deuda histórica de migrations
PG-05 malware scanning
PG-06 storage/fileinfo/GD/permisos Neubox
PG-07 backup/rollback
PG-08 PHP CLI cPanel
PG-09 Artisan vía cPanel Cron
PG-10 working directory
PG-11 Cron frequency/timeout/overlap
PG-12 logs/cache
PG-13 URL productiva
PG-14 SMTP/TLS/remitente
PG-15 SPF/DKIM/DMARC
PG-16 límites/rate de correo
PG-17 observabilidad de rebotes
PG-18 DataTables CDN/CSP, si aplica
PG-19 autorización explícita de producción

Si existen otros gates en PROJECT_STATE/DECISION_LOG/RESULTS, agregarlos.

No eliminar gates por conveniencia.

---

# 7. CLASIFICACIÓN DE GATES

Cada Production Gate debe terminar exactamente en una categoría:

CLOSED
OPEN
BLOCKED_EXTERNAL
DEFERRED_BY_OWNER

CLOSED:
existe evidencia suficiente.

OPEN:
puede resolverse pero sigue pendiente.

BLOCKED_EXTERNAL:
requiere Neubox, DNS, SMTP real u otro entorno externo no autorizado/no
disponible.

DEFERRED_BY_OWNER:
sólo si existe decisión explícita del Owner.

No usar "DONE" sin evidencia.

---

# 8. PRIORIDAD CRÍTICA — IDEMPOTENCIA REQUEST → CONSULTATION

Resolver PG-02 durante SPRINT-08 salvo bloqueo técnico demostrado.

El problema a resolver:

un mismo request lógico de consulta no debe producir accidentalmente:

- dos llamadas facturables al provider;
- dos débitos;
- dos consultations;
- dos actualizaciones derivadas;
- dos expedientes;
- dos cadenas de eventos.

La solución debe cubrir el entry point real.

---

# 9. IDEMPOTENCY KEY

Evaluar mecanismo server-side explícito.

Preferencia:

idempotency/request key persistida y protegida por constraint DB.

No depender exclusivamente de:

- JavaScript;
- botón deshabilitado;
- sesión;
- debounce;
- cache local.

La DB debe participar en la garantía.

---

# 10. SEMÁNTICA DE IDEMPOTENCIA

Definir qué constituye "mismo request lógico".

Como mínimo considerar:

- authenticated user;
- provider service;
- criterio;
- valor;
- idempotency key.

No deduplicar consultas legítimas realizadas en momentos distintos únicamente
porque tengan el mismo VIN/placa.

Una nueva consulta intencional posterior sigue siendo válida.

---

# 11. PROVIDER CALL

Analizar cuidadosamente el punto:

provider/API
→ debit
→ consultation

documentado como baseline actual.

No cambiar silenciosamente el orden contractual.

La idempotencia debe evitar que dos requests concurrentes con la misma key
lleguen ambos al provider.

---

# 12. IDEMPOTENCY RESPONSE

Si un request ya fue completado:

el retry con la misma key debe obtener una respuesta coherente/reutilizable
sin repetir el efecto facturable.

No fabricar una nueva consultation.

Definir comportamiento para:

- IN_PROGRESS;
- COMPLETED;
- FAILED;
- stale/incomplete request.

No almacenar secretos innecesarios.

---

# 13. IDEMPOTENCY CONCURRENCY TEST

Prueba obligatoria con procesos separados contra MySQL local:

dos requests simultáneos
misma idempotency key
mismo usuario
mismo servicio

Resultado esperado:

- una sola operación lógica;
- máximo una llamada efectiva al provider fake/instrumentado;
- máximo un débito;
- una consultation;
- efectos derivados únicos.

Documentar contadores exactos.

---

# 14. DIFFERENT KEY TEST

Dos requests legítimos con:

mismo usuario
mismo VIN/placa
diferentes idempotency keys

NO deben colapsarse artificialmente.

La lógica de 90 días/expedientes seguirá actuando según reglas existentes.

---

# 15. FAILURE INJECTION — IDEMPOTENCIA

Probar fallos controlados en puntos relevantes:

A. antes de provider;
B. después de claim/idempotency reservation;
C. provider failure;
D. después de provider success;
E. debit failure;
F. consultation persistence failure;
G. retry posterior.

No realizar estas pruebas contra producción.

Usar fakes/doubles/DB desechable cuando corresponda.

---

# 16. EXACTLY-ONCE PROVIDER

No afirmar exactly-once contra API externa si el provider no soporta
idempotency key propia.

Distinguir:

VINTrack duplicate suppression
vs.
provider external exactly-once.

Documentar crash window si existe:

provider respondió
→ proceso muere
→ estado local incompleto.

Mitigar, no falsificar garantía.

---

# 17. PERFORMANCE DATASET

Resolver PG-03 con dataset representativo local/desechable.

No contaminar BD principal.

Crear fixture/generator reproducible.

Debe representar al menos:

- muchos users;
- múltiples services;
- múltiples consultations por usuario;
- consultas con robo/no robo;
- cases;
- múltiples cases por identidad;
- 90-day windows;
- statuses diversos;
- datos suficientes para DataTables.

---

# 18. TAMAÑO DE DATASET

Elegir volumen suficiente para que el optimizer tenga decisiones relevantes.

Preferencia mínima orientativa:

100,000 consultations

y volumen proporcional de cases/events.

Si recursos locales no lo permiten, usar el mayor volumen razonable y
documentar limitación.

No convertir "100,000" en requisito contractual si la máquina no lo soporta.

---

# 19. PERFORMANCE QUERIES

Medir como mínimo:

- Historial Cliente;
- Historial Global Admin;
- filtro por Cliente;
- búsqueda;
- Status Robo;
- Status Notificado;
- Status Validado;
- Estado General;
- Consultation → Case;
- ventana 90 días;
- ordering;
- pagination.

Registrar:

EXPLAIN
índices usados
rows estimadas
temporary
filesort
latencia aproximada local

cuando sea reproducible.

---

# 20. ÍNDICES

Crear índices nuevos sólo con evidencia.

Antes:
EXPLAIN A

Después:
EXPLAIN B

Demostrar mejora.

No crear índices "por si acaso".

Evaluar costo de escritura.

Toda migration de índice debe ser reversible y portable.

---

# 21. N+1

Revalidar ausencia de N+1 en:

- Cliente history;
- Admin history;
- Notification Center;
- Case listing;
- evidence listing;
- timelines relevantes.

Mantener límites de query count donde ya existan.

---

# 22. MIGRATION DEBT

Resolver PG-04 de forma controlada.

Existe deuda histórica conocida en rollback relacionada con:

2026_08_05_000002_drop_label_icon_from_menu_permissions_tables

Primero inspeccionar exactamente:

- up();
- down();
- schema previo;
- dependencias posteriores;
- riesgo.

No editar migration histórica aplicada sin evaluar consecuencias.

---

# 23. ESTRATEGIA MIGRATION DEBT

Preferencia:

crear migration correctiva/aditiva posterior si puede resolver el problema
sin reescribir historia.

Sólo modificar migration histórica si:

- no existe alternativa segura;
- no afecta entornos donde ya fue aplicada;
- existe justificación formal;
- rector/documentación lo permiten.

Si no puede resolverse de forma segura:
mantener OPEN con runbook explícito.

---

# 24. FULL MIGRATION TEST

En DB desechable:

migrate fresh
→ suite crítica
→ rollback completo cuando sea técnicamente válido
→ migrate nuevamente

Registrar exactamente dónde falla.

No borrar BD principal.

---

# 25. MYSQL / MARIADB

MySQL objetivo local:

8.4.3

Producción objetivo:

MariaDB 10.6.27

Si MariaDB 10.6.27 puede instalarse de forma segura localmente sin alterar el
entorno principal, se permite un entorno desechable separado.

NO reemplazar MySQL local principal.

NO tocar producción.

---

# 26. MARIADB VALIDATION

Si existe entorno MariaDB 10.6.27:

ejecutar:

- migrations fresh;
- schema;
- constraints;
- indexes;
- critical suite;
- lifecycle;
- evidence;
- histories;
- outbox;
- commands;
- concurrency cuando sea viable.

Si NO existe:

PG-01 permanece BLOCKED_EXTERNAL u OPEN según evidencia.

No declarar compatibilidad "VERIFIED" sólo por inspección SQL.

---

# 27. SECURITY HARDENING

Realizar revisión enfocada, no pentest destructivo externo.

Cubrir:

- authentication;
- authorization;
- IDOR;
- CSRF;
- XSS;
- mass assignment;
- file upload;
- file download;
- MIME validation;
- path traversal;
- filename handling;
- SQL injection;
- DataTables parameters;
- email/template injection;
- logs;
- secrets;
- exception leakage.

---

# 28. AUTHORIZATION MATRIX

Construir matriz:

RESOURCE
CUSTOMER
ADMIN
OTHER CUSTOMER
UNAUTHENTICATED

Como mínimo para:

- consultation history;
- notification case;
- evidence;
- portal notification;
- timeline;
- admin history;
- admin case review.

Ejecutar tests negativos.

---

# 29. MASS ASSIGNMENT

Revisar específicamente campos del expediente.

El Cliente NO debe poder modificar:

- VIN;
- owner;
- consultation_id;
- folio;
- status administrativo;
- audit fields;
- deadlines;
- validation metadata.

El Admin sólo puede modificar campos autorizados por lifecycle/contract.

---

# 30. XSS

Probar payloads en campos textuales:

<script>
img/onerror
HTML entities
quotes

La regla MAYÚSCULAS/SIN ACENTOS no sustituye escaping.

Verificar:

- Blade;
- DataTables;
- emails;
- Notification Center.

---

# 31. SQL / DATATABLES

Revalidar:

- allowlist ordering;
- parameter binding;
- filters;
- search;
- length cap;
- no `length=-1` ilimitado;
- no columnas arbitrarias.

---

# 32. FILE SECURITY

Preservar SPRINT-03:

- private storage;
- PDF/JPG/JPEG/PNG;
- 3 MiB;
- máximo 8 activos;
- SHA-256;
- authorized download;
- soft deletion;
- no public URL.

---

# 33. MALWARE SCANNING — PG-05

No integrar automáticamente un antivirus externo sin decisión arquitectónica.

Durante SPRINT-08:

1. evaluar riesgo;
2. evaluar opciones compatibles con hosting compartido;
3. documentar alternativas;
4. recomendar estrategia.

Posibles categorías:

- scanning antes de producción;
- scanning externo;
- servicio/API;
- proceso administrativo;
- aceptación explícita de riesgo.

Si no existe mecanismo autorizado:
PG-05 permanece OPEN.

No fingir que MIME/hash equivalen a malware scanning.

---

# 34. FILEINFO / GD

Verificar localmente extensiones disponibles.

Registrar comandos/evidencia.

No extrapolar a Neubox.

Neubox:
BLOCKED_EXTERNAL hasta verificación autorizada.

---

# 35. PRIVATE STORAGE READINESS

Documentar requerimientos productivos:

- ubicación fuera de public_html cuando sea posible;
- permisos;
- ownership;
- Laravel storage config;
- download exclusivamente autorizado;
- backups;
- capacidad estimada.

No inventar rutas reales.

---

# 36. CAPACITY

Realizar estimación simple.

Máximo contractual:

8 archivos × 3 MiB = 24 MiB por expediente.

Calcular escenarios:

1,000 cases
10,000 cases
100,000 cases

Esto es capacity planning, no cuota contractual.

Documentar storage bruto máximo teórico sin confundirlo con consumo esperado.

---

# 37. BACKUP — PG-07

Diseñar procedimiento reproducible previo a deployment.

Debe cubrir:

- DB;
- archivos/evidencias;
- código/config relevante;
- timestamps;
- identificación de versión.

No ejecutar backup productivo todavía.

---

# 38. BACKUP VALIDATION

Un backup no está validado sólo porque existe.

Preparar procedimiento futuro para verificar:

- archivo generado;
- tamaño razonable;
- integridad;
- restauración de prueba cuando sea posible.

SPRINT-08 puede probar el procedimiento localmente.

---

# 39. ROLLBACK RUNBOOK

Crear:

docs/production/ROLLBACK-RUNBOOK.md

Debe distinguir:

A. rollback código;
B. rollback migration;
C. rollback config;
D. rollback Cron;
E. rollback SMTP;
F. restauración DB;
G. restauración evidence storage.

No asumir que `migrate:rollback` siempre es suficiente.

---

# 40. FORWARD-FIX

Para migrations con datos irreversibles, documentar cuándo se debe preferir:

forward-fix

en lugar de rollback destructivo.

No prometer rollback imposible.

---

# 41. DEPLOYMENT RUNBOOK

Crear:

docs/production/DEPLOYMENT-RUNBOOK.md

Debe estar adaptado a:

- Neubox;
- cPanel;
- SFTP/File Manager;
- sin SSH;
- sin Git Deployment;
- sin consola PHP interactiva;
- cPanel Cron disponible;
- no Scheduler productivo.

---

# 42. DEPLOYMENT RUNBOOK — FASES

Como mínimo:

1. pre-deployment checklist;
2. maintenance decision;
3. backup;
4. artifact preparation;
5. upload;
6. dependencies strategy;
7. configuration;
8. migrations;
9. storage;
10. cache;
11. permissions;
12. Cron;
13. SMTP;
14. smoke tests;
15. post-deployment verification;
16. rollback trigger;
17. monitoring.

No ejecutar estos pasos en producción.

---

# 43. COMPOSER / VENDOR

Dado que producción no tiene SSH, documentar estrategia realista:

- build local;
- composer install local con flags productivos;
- upload vendor si es necesario;
- verificar plataforma PHP/extensions.

No ejecutar deployment.

No asumir que Composer está disponible en Neubox.

---

# 44. ENV

Preparar checklist de variables necesarias sin incluir valores secretos.

Ejemplos:

APP_ENV
APP_DEBUG
APP_URL
DB_*
MAIL_*
FILESYSTEM_*
timezone/config correspondiente
notification processing config

Nunca copiar secretos al documento.

---

# 45. APP_DEBUG

Production requirement:

APP_DEBUG=false

Documentarlo como gate/check.

No modificar producción.

---

# 46. CACHE

Documentar estrategia compatible sin SSH.

No asumir:

php artisan optimize

puede ejecutarse manualmente en servidor.

Si se requiere Artisan, debe validarse posteriormente vía mecanismo permitido.

---

# 47. CPANEL CRON

Mantener comandos de SPRINT-07 como candidatos.

No configurar Cron real.

Preparar tabla:

COMMAND
PURPOSE
RECOMMENDED FREQUENCY
MAX EXECUTION
OVERLAP SAFETY
LOG DESTINATION
PRODUCTION VERIFIED?

Frecuencias siguen siendo recomendación hasta aprobación.

---

# 48. PHP CLI

PG-08 sólo puede cerrarse con evidencia real del entorno objetivo o entorno
equivalente autorizado.

No inventar:

/usr/local/bin/php
/usr/bin/php

ni otra ruta.

---

# 49. ARTISAN CRON

PG-09 requiere demostrar posteriormente que cPanel puede ejecutar el comando
con:

- PHP correcto;
- working directory correcto;
- permissions;
- env;
- storage/cache.

SPRINT-08 prepara el test exacto.

No lo ejecuta en Neubox.

---

# 50. CRON OVERLAP

Preservar garantías DB de SPRINT-07.

Documentar que incluso si cPanel solapa procesos:

- outbox no duplica lógicamente;
- auto-close es idempotente;
- reminders se deduplican.

No depender sólo de `withoutOverlapping()` del Scheduler.

---

# 51. SMTP READINESS

Preparar checklist:

- host;
- port;
- encryption/TLS;
- username;
- sender;
- reply-to;
- domain;
- SPF;
- DKIM;
- DMARC;
- limits;
- bounce behavior.

No registrar credenciales.

---

# 52. INVALID RECIPIENT HARDENING

Resolver OBS-07-02 si puede hacerse sin cambio contractual.

Evaluar distinguir:

TEMPORARY DELIVERY FAILURE
→ RETRYABLE

NO USER / NO EMAIL / INVALID EMAIL
→ NON_RETRYABLE / SKIPPED

Debe:

- quedar trazable;
- no afectar Portal;
- no consumir 5 retries inútiles.

Agregar tests.

---

# 53. SMTP CRASH WINDOW

OBS-07-01 permanece aceptada.

No intentar eliminarla mediante:

mark delivered before send.

Eso puede perder emails.

Mantener documentación:

at-least-once external delivery.

---

# 54. EMAIL SECURITY

Revalidar:

- recipient server-side;
- no attachments;
- escaping;
- no secrets;
- no stack trace;
- no other-customer data;
- authorized links.

---

# 55. DATATABLES CDN / CSP

Evaluar PG-18 si aplica.

Documentar:

- dependencia CDN;
- disponibilidad;
- integrity/SRI si existe;
- CSP;
- posibilidad futura de asset local.

No cambiar frontend únicamente por preferencia.

Si no existe política aprobada:
clasificar como recomendación, no blocker automático.

---

# 56. OBSERVABILITY

Definir mínimo operativo productivo:

- Laravel logs;
- failed deliveries;
- dead outbox;
- auto-close summaries;
- Cron execution;
- storage errors;
- unauthorized attempts relevantes.

No crear plataforma observability compleja.

---

# 57. HEALTH CHECKS

Evaluar un mecanismo mínimo de smoke/health verification.

No exponer:

- DB credentials;
- versions sensibles innecesarias;
- config;
- stack trace.

Puede ser runbook en lugar de endpoint público.

---

# 58. FAILURE INJECTION

Ejecutar únicamente local/desechable.

Casos relevantes:

- DB failure;
- filesystem failure;
- email failure;
- duplicate request;
- duplicate processor;
- concurrent state transition;
- retry;
- stale lease;
- malformed outbox.

Preservar invariantes.

---

# 59. DATA INTEGRITY

Ejecutar queries de diagnóstico local para detectar:

- duplicate folios;
- duplicate active ownership incompatible;
- orphan evidence;
- orphan events;
- orphan outbox;
- orphan deliveries;
- impossible statuses;
- cases >30 days open según clock controlado;
- invalid deadline relationships.

Documentar resultados.

---

# 60. FOREIGN KEYS

Revisar:

- consultation links;
- case links;
- evidence;
- events;
- outbox;
- deliveries;
- notifications.

No modificar FK sin evidencia.

---

# 61. CHARACTER NORMALIZATION

Preservar regla:

textual capturable
→ MAYÚSCULAS
→ SIN ACENTOS
→ SIN DIÉRESIS

Revalidar server-side.

No aplicar a:

- filenames;
- MIME;
- hashes;
- email;
- system identifiers;
- VIN si ya tiene reglas propias.

---

# 62. VIN

Preservar:

- immutable;
- no heuristic inference;
- VIN_NOT_AVAILABLE cuando corresponda.

No resolver excepcional VIN mediante heurística.

---

# 63. BUSINESS RULE REGRESSION

Preservar:

- 3 pending maximum;
- block before provider/credit;
- 3 calendar days;
- 23:59:59;
- 30-day auto-close;
- 90-day window;
- first responsible Police;
- same case on rejection;
- no retroactive cases;
- analyst validation/rejection;
- evidence constraints;
- histories;
- notification semantics.

---

# 64. FULL REGRESSION

Ejecutar suite completa después del hardening.

Además ejecutar grupos críticos cuando existan:

SPRINT-02
SPRINT-03
SPRINT-04
SPRINT-05
SPRINT-06
SPRINT-07
SPRINT-08

Registrar tests/assertions exactos.

---

# 65. CONCURRENCY REGRESSION

Revalidar al menos:

- 2 pending + 2 consultations;
- same consultation case creation;
- folio assignment;
- double submit;
- double VIN;
- validate/reject;
- validate/auto-close;
- reject/auto-close;
- outbox processors;
- auto-close/auto-close;
- idempotent request/request.

Usar procesos reales cuando sea viable.

---

# 66. DEADLOCK / RETRY

Production Gate histórico incluye carreras/deadlock/retry.

Ejecutar stress controlado local para observar deadlocks.

No asumir que "no ocurrió" demuestra imposibilidad.

Si existe retry DB implementado, probarlo.

Si no existe y el riesgo es relevante:
evaluar hardening mínimo.

No introducir retries indiscriminados alrededor de llamadas externas.

---

# 67. CONFIGURATION HARDENING

Revisar:

- env defaults;
- production-safe defaults;
- batch sizes;
- retry counts;
- lease;
- timezone;
- max upload;
- max active evidence;
- deadline values;
- 90-day config.

No cambiar reglas aprobadas.

---

# 68. SECRETS

Buscar accidentalmente:

- passwords;
- API keys;
- SMTP credentials;
- production DB credentials;
- tokens.

No imprimirlos en RESULT.

Si se detecta secreto real versionado:
STOP esa parte y reportar riesgo crítico sin reproducir el secreto.

---

# 69. LOG SANITIZATION

Revisar que logs no incluyan innecesariamente:

- provider credentials;
- email credentials;
- full sensitive payload;
- evidence contents;
- stack traces al usuario.

---

# 70. PRODUCTION READINESS REPORT

Crear:

docs/production/PRODUCTION-READINESS-REPORT.md

Debe ser ejecutivo y técnico.

Incluir:

1. System Baseline
2. Sprint Coverage
3. Production Gate Matrix
4. Closed Gates
5. Open Gates
6. External Blockers
7. Security Assessment
8. Database Assessment
9. Performance Assessment
10. Storage Assessment
11. Cron Assessment
12. Email Assessment
13. Backup Assessment
14. Rollback Assessment
15. Known Risks
16. Required Production Verification
17. Recommended Deployment Sequence
18. Production Readiness Verdict

---

# 71. VERDICT

El verdict sólo puede ser:

READY
READY WITH CONDITIONS
NOT READY

READY:
todos los blockers críticos cerrados.

READY WITH CONDITIONS:
software técnicamente sólido, pero quedan verificaciones externas concretas
antes de deployment.

NOT READY:
existe defecto/gate crítico técnico sin resolver.

No confundir:

READY WITH CONDITIONS

con:

AUTHORIZED FOR PRODUCTION.

Son conceptos distintos.

---

# 72. PRODUCTION AUTHORIZATION

Incluso si verdict = READY:

Production continúa:

NOT AUTHORIZED

hasta instrucción explícita posterior del Project Owner.

Codex NO debe:

- iniciar deployment;
- pedir credenciales para continuar automáticamente;
- configurar Cron;
- ejecutar migrations productivas.

Debe STOP.

---

# 73. NO CREAR SPRINT-09 AUTOMÁTICAMENTE

SPRINT-08 debe terminar con recomendación.

No asumir que el siguiente paso es deployment.

El Owner decidirá entre:

- resolver gates;
- realizar staging;
- preparar deployment;
- autorizar producción;
- crear nuevo Sprint;
- detener proyecto.

---

# 74. MODIFICACIONES PERMITIDAS

SPRINT-08 puede modificar:

- código necesario para hardening;
- tests;
- migrations correctivas nuevas;
- config;
- documentación;
- fixtures/generators de performance.

Sólo cuando exista justificación dentro de este rector.

---

# 75. MODIFICACIONES PROHIBIDAS

NO:

- producción;
- Neubox;
- cPanel real;
- Cron real;
- SMTP real;
- DNS;
- backfill productivo;
- datos productivos;
- credenciales;
- cambiar reglas de negocio;
- nuevas features no relacionadas;
- SMS;
- WhatsApp;
- realtime;
- rediseño UI.

---

# 76. PERFORMANCE DATA SAFETY

Los datos masivos de prueba deben:

- ser sintéticos;
- vivir en DB desechable;
- poder eliminarse;
- no quedar en BD principal;
- no generar emails reales;
- no llamar providers reales.

---

# 77. PROVIDER SAFETY

Todas las pruebas de idempotencia/concurrencia deben usar:

fake/stub/test adapter

cuando puedan provocar consumo externo.

NO llamar API facturable real para stress tests.

---

# 78. FORMAT / QUALITY

Al finalizar:

- Pint archivos afectados;
- git diff --check;
- suite completa;
- migration tests;
- security tests;
- concurrency tests;
- performance evidence.

No exigir cambios de estilo fuera del scope.

---

# 79. SPRINT-08-RESULT.md

Crear:

docs/sprints/SPRINT-08-RESULT.md

Estructura mínima:

1. Executive Summary
2. Scope
3. Explicit Exclusions
4. Governance
5. Baseline
6. Production Gate Inventory
7. Production Gate Final Matrix
8. Idempotency Design
9. Idempotency Concurrency
10. Idempotency Failure Injection
11. Provider Crash Window
12. Performance Dataset
13. EXPLAIN Before/After
14. Index Decisions
15. N+1 Assessment
16. Migration Debt
17. Full Migration Validation
18. MySQL Validation
19. MariaDB Validation
20. Security Hardening
21. Authorization Matrix
22. IDOR
23. XSS
24. SQL/DataTables
25. Evidence Security
26. Malware Scanning Assessment
27. Storage Capacity
28. Private Storage Assessment
29. Invalid Recipient Hardening
30. SMTP Assessment
31. cPanel/PHP CLI Assessment
32. Cron Assessment
33. Backup Assessment
34. Rollback Assessment
35. Failure Injection
36. Data Integrity
37. Concurrency Regression
38. Deadlock/Retry Assessment
39. Configuration Hardening
40. Secrets/Logging Assessment
41. Tests Added
42. Tests Executed
43. Performance Results
44. Migrations Added/Changed
45. Files Modified
46. Remaining Risks
47. External Blockers
48. Production Readiness Verdict
49. Requirement Traceability
50. Recommended Next Step
51. Sprint Conclusion

---

# 80. FINAL ATTESTATION

SPRINT-08 debe finalizar declarando explícitamente:

Local hardening:
PASS / FAIL

Full regression:
PASS / FAIL

Request idempotency:
VERIFIED / NOT VERIFIED

Performance:
VERIFIED / PARTIALLY VERIFIED / NOT VERIFIED

Migration chain:
VERIFIED / PARTIALLY VERIFIED / NOT VERIFIED

MySQL 8.4.3:
VERIFIED / NOT VERIFIED

MariaDB 10.6.27:
VERIFIED / NOT VERIFIED

Evidence security:
VERIFIED / NOT VERIFIED

Malware scanning:
VERIFIED / NOT VERIFIED

Neubox:
VERIFIED / NOT VERIFIED

cPanel Cron:
VERIFIED / NOT VERIFIED

SMTP production:
VERIFIED / NOT VERIFIED

Backup:
VERIFIED LOCALLY / NOT VERIFIED

Rollback:
VERIFIED LOCALLY / NOT VERIFIED

Production Readiness:
READY / READY WITH CONDITIONS / NOT READY

Production Authorization:
NOT AUTHORIZED

Deployment:
NOT EXECUTED

Finalizar exactamente:

READY FOR OWNER REVIEW

STOP.