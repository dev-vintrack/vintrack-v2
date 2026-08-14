# VINTrack — Local Production Hardening Gate
# PG-05 Malware Scanning + PG-18 Frontend Critical Assets

**Estado:** PENDING OWNER AUTHORIZATION
**Tipo:** Local hardening implementation + verification
**Roadmap:** DEC-036
**SPRINT-08 closure:** DEC-044
**Malware architecture:** DEC-045
**Frontend assets/CSP architecture:** DEC-046

**Production Readiness:** READY WITH CONDITIONS
**Production Authorization:** NOT AUTHORIZED
**Deployment:** NOT EXECUTED
**Neubox access:** NOT AUTHORIZED
**Scanner provider selection:** NOT AUTHORIZED

**Resultado obligatorio:**

`docs/production/LOCAL-PRODUCTION-HARDENING-GATE-RESULT.md`

---

# 1. OBJETIVO

Implementar y verificar localmente las decisiones aprobadas en:

- DEC-045 — Malware Scanning Architecture for Evidence
- DEC-046 — Frontend Critical Assets Self-Hosting and CSP Strategy

El Gate debe resolver todo lo que pueda demostrarse localmente antes de entrar
al Production Environment Verification Gate.

Debe:

1. implementar el workflow de cuarentena de evidencia;
2. implementar una abstracción de malware scanner;
3. probar scanning mediante implementación Fake/Test;
4. implementar fail-closed;
5. impedir SUBMIT/RESUBMIT con evidencia activa no CLEAN;
6. implementar estados, retry e historial/auditoría de scanning;
7. preservar Evidence de SPRINT-03;
8. inventariar dependencias frontend externas;
9. migrar los assets frontend críticos aprobados a self-hosted;
10. conservar DataTables 1.13.6;
11. verificar funcionamiento sin CDN;
12. producir un assessment CSP actualizado;
13. mantener producción totalmente fuera de alcance.

NO seleccionar ni contratar todavía un malware provider real.

---

# 2. FUENTES OBLIGATORIAS

Leer completamente antes de modificar código:

- AGENTS.md
- docs/VINTRACK_MASTER_SPEC.md
- docs/ARCHITECTURE.md
- docs/BUSINESS_RULES.md
- docs/DATA_MODEL.md
- docs/DECISION_LOG.md
- docs/CHANGE_REQUESTS.md
- docs/PROJECT_STATE.md
- docs/production/PRODUCTION-READINESS-REPORT.md
- docs/production/DEPLOYMENT-RUNBOOK.md
- docs/production/ROLLBACK-RUNBOOK.md

Revisar además los rectores/resultados relevantes:

- SPRINT-02
- SPRINT-03
- SPRINT-04
- SPRINT-05
- SPRINT-06
- SPRINT-07
- SPRINT-08

Confirmar antes de implementar:

DEC-044
DEC-045
DEC-046

No reinterpretar las decisiones.

---

# 3. GOVERNANCE PREFLIGHT

Confirmar:

- SPRINT-08 = APPROVED WITH OBSERVATIONS;
- Production Readiness = READY WITH CONDITIONS;
- Production Authorization = NOT AUTHORIZED;
- Deployment = NOT EXECUTED;
- PG-05 = DECIDED — IMPLEMENTATION REQUIRED BEFORE PRODUCTION;
- PG-18 = DECIDED — LOCAL ASSET MIGRATION REQUIRED BEFORE PRODUCTION.

Este Gate no crea SPRINT-09.

---

# 4. BASELINE

Antes de cualquier cambio ejecutar:

- suite completa;
- tests Evidence;
- tests Portal Cliente;
- tests Admin;
- histories/DataTables;
- outbox/delivery;
- idempotency;
- Pint según baseline;
- git diff --check.

Baseline esperado por SPRINT-08:

112 tests
496 assertions
0 fallos

Registrar baseline REAL observado.

---

# 5. PARTE A — PG-05 MALWARE SCANNING

La implementación debe respetar literalmente DEC-045:

QUARANTINE-FIRST
+
MALWARE SCANNING
+
FAIL-CLOSED

No sustituirla por otro modelo.

---

# 6. NO SELECCIONAR PROVIDER

NO integrar:

- Cloudmersive;
- VirusTotal;
- ClamAV productivo;
- cualquier API externa;
- cualquier servicio SaaS real.

Implementar exclusivamente una abstracción desacoplada y una implementación:

Fake/Test

para validar el workflow.

---

# 7. SCANNER CONTRACT

Crear/reutilizar un contrato equivalente conceptualmente a:

MalwareScanner

con una operación equivalente a:

scan(document): MalwareScanResult

El contrato debe vivir en la capa arquitectónica apropiada.

Application/Domain NO debe importar SDK ni clases concretas del proveedor.

Infrastructure contendrá adaptadores concretos futuros.

---

# 8. MALWARE SCAN RESULT

El resultado normalizado debe poder representar como mínimo:

- CLEAN
- INFECTED
- TEMPORARY_ERROR
- PERMANENT_ERROR

o semántica equivalente.

No devolver únicamente boolean.

Debe poder transportar, cuando corresponda:

- provider reference;
- detection/threat;
- engine/policy;
- error code sanitizado.

No incluir secretos.

---

# 9. DOCUMENT SCAN STATUS

La evidencia debe poseer un estado actual equivalente a:

- PENDING
- SCANNING
- CLEAN
- INFECTED
- ERROR

Los nombres físicos pueden adaptarse a la arquitectura real.

No utilizar exclusivamente:

is_clean boolean.

---

# 10. PERSISTENCE DESIGN

Antes de crear schema:

inspeccionar:

- notification_case_documents;
- audit/events;
- outbox;
- idempotency;
- otras tablas de attempts.

Preferencia de diseño:

document:
current malware status

+
scan attempts/history:
append-only

Conceptualmente:

notification_case_document_scans

con:

- id;
- document_id;
- provider;
- status/result;
- attempt;
- started_at;
- finished_at;
- provider_reference nullable;
- threat_name nullable;
- policy/version nullable;
- error_code nullable;
- timestamps.

No utilizar exactamente esta tabla si la arquitectura real sugiere algo mejor.

Debe preservar historial.

---

# 11. MIGRATIONS PG-05

Se permiten migrations aditivas necesarias.

Deben ser:

- reversibles;
- MySQL 8.4.3 / MariaDB 10.6.27 compatibles;
- probadas up → down → up;
- sin reescribir migrations históricas.

No producción.

---

# 12. UPLOAD FLOW

Después del upload seguro SPRINT-03:

archivo
→ private storage
→ metadata
→ SHA-256
→ malware status PENDING

Upload exitoso NO equivale a CLEAN.

No permitir bypass para documentos nuevos.

---

# 13. QUARANTINE

No es obligatorio mover físicamente el archivo a otro filesystem si el
storage privado existente puede representar cuarentena de forma segura.

La cuarentena es principalmente una condición de acceso.

PENDING / SCANNING / ERROR / INFECTED:

NO available for normal evidence use.

No crear public URL.

---

# 14. DOWNLOAD PROTECTION

Los endpoints ordinarios de Evidence deben exigir:

malware status == CLEAN

además de las autorizaciones existentes.

Para documentos no CLEAN:

denegar descarga normal.

No filtrar detalles técnicos de malware al Cliente.

---

# 15. ADMIN ACCESS TO INFECTED CONTENT

No introducir una descarga ordinaria especial para el Analista.

INFECTED debe permanecer aislado.

Si en el futuro se requiere forensic retrieval, será una capability/proceso
separado aprobado explícitamente.

---

# 16. SUBMIT / RESUBMIT BLOCKING

Modificar el servicio de dominio/Application correspondiente.

Antes de:

SUBMIT
RESUBMIT

verificar todos los documentos ACTIVOS.

Regla:

todos los documentos activos deben estar CLEAN.

Si existe cualquiera:

PENDING
SCANNING
ERROR
INFECTED

la operación se rechaza server-side.

No confiar en Blade/JavaScript.

---

# 17. SIN DOCUMENTOS

Si el contrato vigente permite presentar un expediente sin archivos adjuntos,
no inventar una nueva obligación documental.

La regla malware aplica a documentos activos existentes.

No interpretar:

“todos los documentos deben estar CLEAN”

como:

“debe existir por lo menos un documento”.

---

# 18. INFECTED

Cuando scanner devuelve INFECTED:

- persistir status;
- registrar intento;
- auditar;
- impedir download ordinario;
- impedir submit;
- NO eliminar el archivo;
- NO marcarlo como removed automáticamente.

La política de eliminación/retención sigue pendiente.

---

# 19. ERROR

TEMPORARY_ERROR:

- estado no CLEAN;
- retry permitido;
- backoff acotado.

PERMANENT_ERROR:

- no CLEAN;
- requiere intervención/retry manual futuro;
- no convertir automáticamente a CLEAN.

---

# 20. RETRY

Diseñar mecanismo bounded y compatible conceptualmente con cPanel.

No requerir daemon.

Puede reutilizar patrón de SPRINT-07 sin mezclarlo conceptualmente con
notification delivery.

No crear un retry infinito.

---

# 21. COMMAND LOCAL

Implementar si arquitectónicamente conviene un comando discreto equivalente:

php artisan evidence:process-malware-scans

Nombre final según convenciones reales.

Debe soportar:

- batch limitado;
- ejecución repetida;
- overlap seguro;
- no daemon;
- salida sanitizada.

No configurar Cron.

---

# 22. CONCURRENCY

Probar:

dos processors
→ mismo PENDING document

Resultado:

un scan lógico efectivo
o comportamiento idempotente equivalente.

No deben aparecer dos resultados finales incompatibles.

---

# 23. IDEMPOTENCY / SHA-256

No implementar cache global agresivo sólo por SHA-256.

Si se introduce reutilización local para tests:

debe incluir como mínimo:

- provider;
- policy/version;
- scan timestamp/TTL.

Preferencia para este Gate:

NO reutilizar scans entre documentos salvo que la infraestructura sea
claramente necesaria.

Mantener el diseño simple.

---

# 24. AUDIT

Auditar:

- scan queued;
- scan started cuando sea útil;
- CLEAN;
- INFECTED;
- ERROR;
- retry;
- terminal failure.

No registrar secretos.

---

# 25. PORTAL CLIENTE UX

Mostrar estados amigables como:

PENDIENTE DE ANALISIS
ANALIZANDO
ARCHIVO SEGURO
ARCHIVO BLOQUEADO
ERROR DE ANALISIS

No mostrar:

virus engine internals
provider raw payload
stack trace
API key

Cuando submit esté bloqueado, mensaje claro:

“Existen archivos pendientes de validación de seguridad.”

o equivalente.

---

# 26. ADMIN UX

El Analista debe poder conocer si una evidencia:

- está limpia;
- está pendiente;
- fue bloqueada;
- tiene error.

No habilitar descarga de no CLEAN.

No mostrar información sensible del scanner salvo que sea necesaria.

---

# 27. TEST SCANNER

Implementar FakeMalwareScanner configurable para pruebas:

CLEAN
INFECTED
TEMPORARY_ERROR
PERMANENT_ERROR

Debe permitir instrumentar invocation count.

---

# 28. TESTS PG-05 — UPLOAD

Probar:

upload válido
→ PENDING

upload inválido SPRINT-03
→ no document / no scan

max documents
→ preservado

IDOR
→ preservado.

---

# 29. TESTS PG-05 — CLEAN

PENDING
→ scan
→ CLEAN

Resultado:

- scan history;
- status CLEAN;
- normal download permitida si authorization;
- submit puede continuar si demás invariantes pasan.

---

# 30. TESTS PG-05 — INFECTED

PENDING
→ scan
→ INFECTED

Verificar:

- archivo físico preservado;
- download ordinaria bloqueada;
- submit bloqueado;
- audit/history;
- no auto-delete.

---

# 31. TESTS PG-05 — ERROR

TEMPORARY_ERROR:
→ retryable.

PERMANENT_ERROR:
→ bloqueado / no retry automático infinito.

Nunca CLEAN por error.

---

# 32. TESTS PG-05 — SUBMIT

Casos:

sin documentos
→ comportamiento contractual existente.

todos CLEAN
→ permitido.

uno PENDING
→ bloqueado.

uno SCANNING
→ bloqueado.

uno ERROR
→ bloqueado.

uno INFECTED
→ bloqueado.

removed no CLEAN
→ no debe bloquear si ya no es documento activo,
salvo contrato contrario.

---

# 33. TESTS PG-05 — DOWNLOAD

Cliente owner:

CLEAN → permitido.

PENDING → denied.
SCANNING → denied.
ERROR → denied.
INFECTED → denied.

Analista:

mismo principio para endpoint ordinario.

Otro Cliente:

denied independientemente de status.

---

# 34. TESTS PG-05 — CONCURRENCY

Procesos separados cuando sea viable:

same document
+ two malware processors

Verificar:

- invocation lógica única o idempotente;
- status final legal;
- history consistente;
- no duplicate terminal outcomes.

---

# 35. PARTE B — PG-18 FRONTEND ASSETS

Implementar DEC-046.

Objetivo:

SELF-HOST CRITICAL FRONTEND ASSETS.

No actualizar DataTables.

---

# 36. INVENTARIO EXTERNO OBLIGATORIO

Buscar en todo el frontend:

- http://
- https://
- cdn.datatables.net
- cdnjs
- jsdelivr
- unpkg
- jquery
- bootstrap
- fontawesome
- googleapis
- gstatic
- fonts
- icon CDNs
- otros script/link externos

Producir matriz:

RESOURCE
CURRENT SOURCE
VERSION
WHERE USED
CRITICAL?
SELF-HOST?
REMAIN EXTERNAL?
REASON

---

# 37. DATATABLES

Mantener exactamente:

DataTables 1.13.6

salvo evidencia documental de que otra versión ya sea realmente el baseline.

No migrar a DataTables 2/3.

No cambiar API por actualización.

---

# 38. EXTENSIONS

Inventariar extensiones DataTables realmente usadas.

Self-host sólo las necesarias.

No descargar Buttons/Responsive/etc. por comodidad si no se usan.

---

# 39. JQUERY

Si DataTables 1.13.6 depende del jQuery actual:

preservar versión compatible.

Si jQuery está actualmente en CDN y es dependencia crítica:

migrarlo también a local assets.

No actualizarlo salvo necesidad demostrada.

---

# 40. BOOTSTRAP

Si Bootstrap JS/CSS se consume desde CDN y es crítico para operar el Portal:

evaluar migración local dentro de PG-18.

No actualizar versión.

---

# 41. ICONS / FONTS

Clasificar:

critical UI functionality
vs.
cosmetic.

Preferencia:

assets críticos locales.

Para Google Fonts u otros externos:
evaluar self-host o fallback del sistema.

No ampliar innecesariamente scope si son puramente cosméticos.

---

# 42. STRATEGY

Usar el pipeline existente.

Si ya existe Vite/NPM funcional:
se permite build local.

Si no existe:
preferir assets estáticos versionados.

NO introducir una cadena Node/NPM nueva sólo para cerrar PG-18.

En cualquier caso:

producción no requiere Node/NPM.

---

# 43. VERSIONING

Assets deben tener versión explícita o lockfile equivalente.

No:

latest

No URL flotante.

---

# 44. NO CDN FALLBACK

No implementar:

local → fail → load external CDN.

El artifact debe contener lo necesario.

Si falta un asset crítico:
smoke test debe fallar.

---

# 45. VIEW CHANGES

Sustituir únicamente referencias externas aprobadas por rutas locales.

No rediseñar vistas.

No alterar comportamiento DataTables.

No alterar server-side protocol de SPRINT-06.

---

# 46. OFFLINE/CDN-BLOCK TEST

Prueba obligatoria:

simular ausencia/bloqueo de los CDN críticos.

Las vistas relevantes deben seguir operando con assets locales.

Como mínimo:

- Client History;
- Admin Global History;
- Client Notification Process;
- Admin Notification Process;
- Notification Center si usa dependencias comunes.

---

# 47. CSP INVENTORY

No imponer todavía una CSP productiva completa.

Generar assessment:

scripts origins
style origins
font origins
img origins
connect origins

y detectar uso de:

- inline scripts;
- inline styles;
- eval;
- data:;
- blob:;
- external domains.

---

# 48. CSP RECOMMENDATION

Proponer política objetivo, no necesariamente activarla todavía.

Ideal conceptual:

default-src 'self'

script-src 'self'

style-src 'self'

más excepciones justificadas.

Si actualmente requiere:

'unsafe-inline'
'unsafe-eval'

documentar exactamente por qué.

No romper la aplicación sólo para eliminarlo dentro de este Gate salvo cambio
pequeño y seguro.

---

# 49. SRI

Para cualquier recurso externo deliberadamente mantenido:

evaluar SRI cuando aplique.

Documentar si no aplica.

Local assets no necesitan SRI como control principal.

---

# 50. ASSET INTEGRITY

Los assets locales deben quedar cubiertos por:

- source control;
- commit;
- artifact;
- manifest/hash de deployment.

No crear checksum runtime innecesario.

---

# 51. TESTS PG-18

Probar:

- DataTables inicia correctamente;
- Cliente server-side;
- Admin server-side;
- filtros;
- búsqueda;
- ordering;
- pagination;
- status rendering;
- actions;
- forms/layouts comunes;
- cero fetch HTTP necesario a CDN crítico.

No basta con que la página HTML responda 200.

---

# 52. SECURITY

No introducir:

- JS descargado dinámicamente;
- eval innecesario;
- rutas públicas inseguras;
- directory listings.

Preservar XSS protections.

---

# 53. MIGRATIONS PG-18

No deben requerirse migrations de BD salvo menú/config persistente absolutamente
necesarios.

Preferencia:
cero migrations.

---

# 54. PERFORMANCE

Los assets locales no deben degradar significativamente el first load.

No hace falta micro-optimización.

Verificar tamaños razonables.

No incluir paquetes completos gigantes si sólo se necesitan módulos concretos.

---

# 55. PG-18 CLOSURE CRITERIA

PG-18 puede marcarse:

CLOSED LOCALLY

si:

- inventario completado;
- DataTables crítico self-hosted;
- demás dependencias críticas aprobadas self-hosted;
- ninguna dependencia crítica CDN no aprobada;
- regresión verde;
- smoke test sin CDN;
- CSP assessment generado.

CSP productiva real seguirá formando parte del Production Environment
Verification Gate cuando dependa de headers/config del hosting.

---

# 56. PG-05 FINAL STATUS

Después de este Gate:

PG-05 NO se considera CLOSED productivamente.

Estado esperado:

IMPLEMENTED LOCALLY — PROVIDER SELECTION AND PRODUCTION VERIFICATION REQUIRED

Sólo puede considerarse CLOSED definitivamente cuando:

- proveedor real aprobado;
- privacidad aprobada;
- configuración productiva;
- scan real verificado;
- failure behavior verificado.

---

# 57. NO PRODUCCIÓN

Prohibido:

- Neubox;
- SFTP;
- File Manager;
- cPanel;
- Cron real;
- SMTP;
- DNS;
- provider malware real;
- API externa de scanner;
- production DB;
- production storage.

---

# 58. REGRESIÓN COMPLETA

Al finalizar ejecutar:

- suite completa;
- SPRINT-03 Evidence;
- SPRINT-04 Cliente;
- SPRINT-05 Admin;
- SPRINT-06 histories;
- SPRINT-07 notifications;
- SPRINT-08 idempotency/performance;
- PG-05 tests;
- PG-18 tests.

Registrar tests/assertions exactos.

---

# 59. CONCURRENCY REGRESSION

Preservar:

- request/request idempotency;
- double submit;
- double VIN;
- validate/reject;
- auto-close races;
- outbox concurrency.

Agregar malware processor concurrency.

---

# 60. PERFORMANCE REGRESSION

Reejecutar los tests de History relevantes.

No es obligatorio repetir todos los benchmarks 100k salvo que PG-18 o PG-05
modifiquen queries relacionadas.

Si cualquier cambio toca ConsultationHistoryQuery:
repetir PG-03 benchmark.

---

# 61. MIGRATION VALIDATION

Si PG-05 agrega schema:

up → down → up

en DB desechable.

Actualizar bootstrap sólo si la política vigente de schema baseline lo exige y
es seguro.

No producción.

---

# 62. QUALITY

Ejecutar:

- Pint archivos afectados;
- git diff --check;
- suite completa;
- security regression;
- migration validation.

No crear commit automáticamente salvo autorización existente en governance.

---

# 63. DOCUMENTACIÓN

Actualizar cuando corresponda:

- docs/DECISION_LOG.md
- docs/PROJECT_STATE.md
- docs/production/PRODUCTION-READINESS-REPORT.md

Crear:

docs/production/LOCAL-PRODUCTION-HARDENING-GATE-RESULT.md

Si se modifica schema/bootstrap:
documentarlo explícitamente.

No modificar SPRINT-08-RESULT como si este Gate fuera parte retroactiva del
Sprint.

---

# 64. RESULT STRUCTURE

`LOCAL-PRODUCTION-HARDENING-GATE-RESULT.md` debe incluir:

1. Executive Summary
2. Governance
3. Baseline
4. PG-05 Architecture Implemented
5. Scanner Contract
6. Scan State Model
7. Persistence
8. Quarantine Behavior
9. Download Enforcement
10. Submit/Resubmit Enforcement
11. Retry / Command
12. Malware Audit
13. PG-05 Tests
14. Malware Concurrency
15. PG-05 Remaining External Requirement
16. Frontend External Dependency Inventory
17. DataTables Baseline
18. Assets Migrated
19. Assets Remaining External
20. Offline/CDN-block Verification
21. CSP Assessment
22. SRI Assessment
23. PG-18 Tests
24. Security Regression
25. Full Regression
26. Migrations
27. Files Modified
28. Production Gate Matrix Delta
29. Remaining Risks
30. Production Readiness
31. Production Authorization
32. Recommended Next Step
33. Conclusion

---

# 65. GATE VERDICT

Al finalizar clasificar:

PG-05:
IMPLEMENTED LOCALLY
o
NOT IMPLEMENTED

y adicionalmente:

PROVIDER VERIFICATION REQUIRED
cuando corresponda.

PG-18:
CLOSED LOCALLY
o
OPEN

Production Readiness debe recalcularse.

No cambiar Production Authorization.

---

# 66. FINAL ATTESTATION

Debe declarar:

PG-05 architecture:
IMPLEMENTED LOCALLY / NOT IMPLEMENTED

Real malware provider:
NOT SELECTED / NOT CONFIGURED

Real malware scanning:
NOT VERIFIED

PG-18 local asset migration:
PASS / FAIL

DataTables version:
1.13.6 / actual verified baseline

Critical CDN runtime dependency:
REMOVED / REMAINS

CSP production:
NOT CONFIGURED

Full regression:
PASS / FAIL

Production Readiness:
READY WITH CONDITIONS / NOT READY

Production Authorization:
NOT AUTHORIZED

Deployment:
NOT EXECUTED

Production Environment Verification:
NOT STARTED

Finalizar:

READY FOR OWNER REVIEW

STOP.