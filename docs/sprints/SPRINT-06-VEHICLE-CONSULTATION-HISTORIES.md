# VINTrack — SPRINT-06
# Vehicle Consultation Histories + Server-side DataTables

**Estado:** PENDING OWNER AUTHORIZATION  
**Tipo:** Implementación local + pruebas  
**Baseline:** SPRINT-05 APPROVED WITH OBSERVATIONS  
**Roadmap canónico:** DEC-036  
**Último cierre:** DEC-041  
**Producción:** NOT AUTHORIZED  
**Resultado obligatorio:** docs/sprints/SPRINT-06-RESULT.md

---

# 1. OBJETIVO

Implementar localmente dos vistas históricas de consultas vehiculares:

## Portal Cliente

`Historial de Vehículos Consultados`

Debe mostrar exclusivamente las consultas realizadas por el usuario autenticado.

## Portal Administrativo

`Historial Global de Vehículos Consultados`

Debe mostrar consultas de todos los Clientes/Policías autorizados y permitir
filtrar por un Cliente específico.

Ambas vistas deben presentar información de la consulta y, cuando aplique,
información derivada del expediente de notificación relacionado.

Este Sprint NO debe convertir `consultations` y `notification_cases` en una
sola entidad.

`consultations` continúa siendo el historial de eventos de consulta.

`notification_cases` continúa siendo el proceso documental independiente.

---

# 2. FUENTES OBLIGATORIAS

Antes de modificar código, leer completamente:

- AGENTS.md
- docs/VINTRACK_MASTER_SPEC.md
- docs/ARCHITECTURE.md
- docs/BUSINESS_RULES.md
- docs/DATA_MODEL.md
- docs/DECISION_LOG.md
- docs/CHANGE_REQUESTS.md
- docs/PROJECT_STATE.md
- docs/sprints/SPRINT-01-DOMAIN-DATA-MODEL.md
- docs/sprints/SPRINT-01-RESULT.md
- docs/sprints/SPRINT-02-CORE-DOMAIN-PERSISTENCE.md
- docs/sprints/SPRINT-02-RESULT.md
- docs/sprints/SPRINT-03-EVIDENCE-SECURE-FILE-MANAGEMENT.md
- docs/sprints/SPRINT-03-RESULT.md
- docs/sprints/SPRINT-04-CLIENT-PORTAL-NOTIFICATION-PROCESS.md
- docs/sprints/SPRINT-04-RESULT.md
- docs/sprints/SPRINT-05-ADMINISTRATIVE-PORTAL-REVIEW-VALIDATION.md
- docs/sprints/SPRINT-05-RESULT.md

Inspeccionar además implementación real de:

- Consultation;
- NotificationCase;
- relaciones;
- lifecycle;
- authorization;
- roles;
- menús;
- DataTables actuales;
- queries de historiales;
- modelos `vehicles`;
- provider_service_id;
- adapters/provider responses;
- estado de robo/fraude;
- snapshots;
- deadlines;
- capabilities;
- paginación actual.

No asumir nombres o relaciones no verificadas.

---

# 3. PRINCIPIO ARQUITECTÓNICO

La vista histórica debe construirse conceptualmente así:

CONSULTATION
     |
     +-- datos de consulta
     |
     +-- resultado robo/fraude
     |
     +-- proyección de expediente aplicable
             |
             +-- propio
             +-- otro usuario
             +-- histórico
             +-- ninguno

No hacer:

consultations.vehicle_id
→ notification case

si la arquitectura contractual no utiliza `vehicles` como master.

No utilizar `vehicles` como propietario del expediente.

---

# 4. PREFLIGHT OBLIGATORIO

Antes de implementar:

1. verificar PROJECT_STATE;
2. verificar DEC-036 y DEC-041;
3. ejecutar suite baseline;
4. registrar tests/assertions/fallos;
5. inspeccionar tablas y relaciones reales;
6. inspeccionar historiales actuales Cliente/Admin;
7. inspeccionar DataTables actuales;
8. inspeccionar queries `get()` existentes;
9. inspeccionar tamaños/volumen local aproximado;
10. inspeccionar indexes relevantes;
11. inspeccionar campos reales de `consultations`;
12. inspeccionar cómo se expresa resultado positivo robo/fraude;
13. inspeccionar `notification_cases`;
14. inspeccionar `source_criterion` / `source_value` o equivalentes;
15. inspeccionar VIN/snapshot;
16. inspeccionar ventana de 90 días;
17. inspeccionar `previous_case_id`;
18. inspeccionar roles Cliente/Admin;
19. inspeccionar permisos de menú;
20. inspeccionar layouts y componentes visuales;
21. inspeccionar soporte AJAX/JSON actual;
22. inspeccionar versión/configuración DataTables;
23. identificar N+1 potenciales;
24. identificar filtros actuales;
25. identificar si ya existe server-side adapter reusable;
26. presentar riesgos antes de implementar.

---

# 5. SEMÁNTICA FUNDAMENTAL:
# CONSULTATION → NOTIFICATION CASE

SPRINT-06 NO puede resolver la relación simplemente mediante:

JOIN by VIN
ORDER BY notification_cases.created_at DESC
LIMIT 1

porque una consulta histórica podría quedar asociada a un expediente creado
posteriormente.

La proyección debe respetar el momento histórico de la consulta.

Para cada consultation, determinar el expediente aplicable conforme a:

- identidad vehicular aprobada;
- fecha/hora de consulta;
- ventana contractual de 90 días;
- consulta origen del expediente;
- relaciones persistidas;
- previous case cuando corresponda;
- user responsable;
- reglas de creación/reutilización implementadas en SPRINT-02.

La query debe reflejar la realidad histórica, no simplemente el estado actual
más reciente del VIN.

---

# 6. RELACIÓN DIRECTA CON EXPEDIENTE ORIGINADO

Cuando una consultation sea la originating consultation de un expediente:

relationship:
consultation.id
→ notification_case.originating_consultation_id

usar esta relación directa.

No reconstruirla heurísticamente.

---

# 7. CONSULTA POSTERIOR DENTRO DE 90 DÍAS

Cuando un Policía B realiza una consulta posterior del mismo vehículo dentro
de la ventana aplicable y existe un expediente originado por Policía A:

la consulta B:

- permanece propiedad de Policía B;
- no crea nuevo expediente;
- debe poder proyectar que existe un expediente aplicable;
- no transfiere responsabilidad;
- no concede ownership del expediente;
- no concede acceso a evidencia de Policía A;
- puede mostrar un estado informativo limitado.

---

# 8. EXPEDIENTE DE OTRO USUARIO

En Portal Cliente, cuando la consulta corresponde a un expediente aplicable
responsabilidad de otro Policía:

mostrar un estado equivalente a:

`EXPEDIENTE DE NOTIFICACION EN PROCESO POR OTRO USUARIO`

o texto contractual vigente.

Debe tener menor prioridad visual que los pendientes propios.

Baseline visual aprobado:

- pendiente propio → rojo claro;
- expediente aplicable de otro usuario → azul claro.

El color NO debe ser el único indicador.

No mostrar:

- nombre del otro Policía si no está autorizado;
- evidencia;
- datos internos;
- motivo de rechazo;
- timeline;
- información privada del owner.

---

# 9. CONSULTA POSTERIOR DESPUÉS DE 90 DÍAS

Cuando exista una consulta calificante posterior fuera de la ventana y ésta
origine un nuevo expediente:

la nueva consulta debe proyectar su nuevo expediente.

El nuevo expediente puede referenciar al anterior mediante el mecanismo real
implementado.

No asociar la consulta nueva al expediente anterior como expediente vigente.

---

# 10. CONSULTAS SIN EXPEDIENTE

Una consulta puede no tener expediente porque:

- resultado no calificó;
- VIN no estaba disponible;
- no se generó case conforme al dominio;
- otra condición contractual lo impidió.

La vista debe manejar correctamente:

`NO APLICA`

o equivalente derivado.

No crear expedientes retroactivos desde el historial.

---

# 11. COLUMNAS — PORTAL CLIENTE

Como mínimo mostrar:

- Fecha de consulta
- VIN
- Placas / criterio consultado cuando corresponda
- Marca
- Modelo
- Año
- Servicio
- Resultado / Status Robo
- Status Notificado por Cliente
- Status Validado por Analista
- Fecha límite para notificar
- Estado General del Proceso
- Acciones

Puede agregarse información existente útil si no cambia el contrato.

---

# 12. COLUMNAS — PORTAL ADMINISTRATIVO

Debe incluir lo anterior y además:

- Cliente/Policía responsable de la consulta
- identificador/usuario visible conforme a UI actual

Debe permitir filtrar por Cliente.

No utilizar esta vista como reemplazo del listado administrativo de expedientes
de SPRINT-05.

---

# 13. STATUS ROBO

`Status Robo` debe derivarse exclusivamente del resultado persistido real de
la consulta.

No recalcular consultando providers.

No consumir créditos.

No llamar APIs.

No depender del estado actual de `vehicles` si eso altera la verdad histórica.

La semántica debe preservar el resultado observado en esa consulta.

---

# 14. STATUS NOTIFICADO POR CLIENTE

NO crear nuevo booleano persistente sólo para esta columna.

Debe ser una proyección derivada del estado canónico del expediente aplicable.

Definir explícitamente el mapping usando estados reales.

Baseline esperado:

PENDING
→ NO

SUBMITTED
→ SI

UNDER_REVIEW
→ SI

REJECTED
→ depender de semántica contractual:
   el expediente ya fue presentado alguna vez, pero actualmente requiere
   corrección.

VALIDATED
→ SI

CLOSED_NO_FOLLOW_UP
→ derivar conforme a historia real, no inventar booleano.

Antes de implementar el mapping final:

inspeccionar SPRINT-01/02 y event history.

Si REJECTED o CLOSED presentan ambigüedad de presentación, usar una proyección
que preserve verdad histórica y documentarla.

No modificar la máquina de estados.

---

# 15. STATUS VALIDADO POR ANALISTA

Derivar exclusivamente del Estado General.

Baseline:

VALIDATED
→ SI

todos los demás
→ NO

No crear booleano duplicado.

---

# 16. FECHA LÍMITE PARA NOTIFICAR

Mostrar el deadline persistido del expediente aplicable:

`notification_deadline_at`

o nombre real equivalente.

No recalcular en JavaScript.

Cuando no exista expediente aplicable:

mostrar vacío / NO APLICA según convención UI.

Usar timezone:

America/Mexico_City

---

# 17. ESTADO GENERAL DEL PROCESO

Mostrar el estado canónico real.

Estados:

- PENDING
- SUBMITTED
- UNDER_REVIEW
- REJECTED
- VALIDATED
- CLOSED_NO_FOLLOW_UP

Usar etiquetas amigables en español sin crear valores persistentes nuevos.

Para consulta con expediente de otro usuario, puede utilizarse una proyección
informativa especial en la UI, pero NO crear un nuevo estado de dominio.

---

# 18. ACCIONES — PORTAL CLIENTE

Las acciones dependen de ownership y capability.

Ejemplos:

Expediente propio PENDING
→ CAPTURAR / CONTINUAR

Expediente propio REJECTED
→ CORREGIR

Expediente propio SUBMITTED/UNDER_REVIEW
→ VER

VALIDATED
→ VER

CLOSED
→ VER

Expediente de otro usuario
→ VER ESTADO LIMITADO / SIN ACCESO AL CASE

Sin expediente
→ sin acción de expediente salvo comportamiento aprobado.

No permitir navegación a case ajeno.

---

# 19. ACCIONES — PORTAL ADMINISTRATIVO

Cuando el usuario tenga capability:

→ VER EXPEDIENTE

y navegar al módulo administrativo de SPRINT-05.

No duplicar Review/Validate/Reject dentro de la tabla histórica.

La tabla histórica enlaza al flujo administrativo existente.

---

# 20. PORTAL CLIENTE — SCOPE

La query debe partir siempre de:

authenticated user

Nunca aceptar:

user_id

del browser como scope de datos.

Manipular filtros no puede revelar consultas de otros Clientes.

---

# 21. PORTAL ADMIN — SCOPE

El Portal Administrativo puede consultar el universo permitido según role/
capability.

Debe soportar filtro por Cliente.

El filtro debe aplicarse server-side.

No confiar en dropdown visual.

---

# 22. SERVER-SIDE DATATABLES

La implementación debe ser server-side.

No cargar todas las consultations mediante:

->get()

para posteriormente paginar en JavaScript.

Debe soportar:

- draw
- start
- length
- search
- order
- filters

o el protocolo equivalente de la librería DataTables utilizada realmente.

No incorporar paquete externo nuevo si la implementación puede resolverse con
el stack existente.

Si ya existe una integración server-side compatible, reutilizarla.

---

# 23. PAGINACIÓN

El backend debe devolver únicamente la página solicitada.

Definir máximo razonable de:

`length`

para evitar requests abusivos.

No permitir:

length = -1

para descargar toda la base salvo requisito explícito.

---

# 24. BÚSQUEDA GLOBAL

Portal Cliente puede buscar dentro de sus datos permitidos.

Portal Administrativo dentro de su universo autorizado.

Campos razonables:

- VIN
- placas
- folio
- marca
- modelo
- usuario/Cliente en Admin

Aplicar búsqueda server-side.

Escapar/parametrizar correctamente.

No concatenar SQL crudo proveniente del browser.

---

# 25. FILTROS

Como mínimo evaluar:

Portal Cliente:
- fecha
- status robo
- estado del proceso

Portal Admin:
- Cliente
- fecha
- VIN
- placas
- status robo
- estado del proceso

No agregar decenas de filtros irrelevantes.

Aplicar SQL/query builder.

---

# 26. ORDENAMIENTO

Allowlist de columnas ordenables.

No utilizar directamente el nombre de columna enviado por DataTables en
`ORDER BY`.

Mapear índices UI → columnas permitidas.

Probar payload adversarial.

---

# 27. PERFORMANCE

Evitar:

- N+1
- subqueries correlacionadas costosas por fila
- carga completa de histories
- resolver case mediante queries individuales por consultation

Diseñar query/projection eficiente.

Evaluar:

- joins
- subqueries agregadas
- CTE sólo si compatible y justificado
- derived tables
- eager loading controlado

Debe ser compatible con:

MySQL 8.4.3
MariaDB 10.6.27

---

# 28. INDEXES

Primero inspeccionar índices existentes.

No crear índices por intuición.

Identificar queries principales:

- user_id + created_at
- provider_service_id
- criterio/valor/VIN
- notification_case originating consultation
- case owner/status/dates
- filtros admin

Ejecutar EXPLAIN local con dataset disponible.

Si el dataset local es insuficiente para demostrar beneficio:

documentar.

Un índice nuevo sólo se crea cuando existe una necesidad demostrable.

EXPLAIN con volumen representativo sigue siendo Production Gate aunque se
puedan realizar verificaciones locales parciales.

---

# 29. VOLUMEN REPRESENTATIVO

Si el entorno local carece de suficiente volumen:

crear exclusivamente dataset de prueba aislado cuando sea seguro.

No contaminar datos reales.

No crear casos retroactivos reales.

Los datos de performance deben limpiarse.

Documentar tamaño usado.

---

# 30. CLIENT / CUSTOMER

Recordar:

no existe tabla `clients`.

El Cliente se representa actualmente mediante User + Role/RoleType.is_customer.

No inventar `client_id`.

En Admin, filtro por Cliente debe utilizar la relación real del sistema.

---

# 31. NO USAR vehicles COMO MASTER

`vehicles` puede contribuir únicamente con información no histórica si está
contractualmente permitido y no altera la verdad de la consulta.

Preferencia:

usar snapshot/persistencia de consultation y case.

No utilizar `vehicles` para resolver ownership o expediente.

No utilizar su estado actual de robo para reemplazar Status Robo histórico.

---

# 32. SNAPSHOT

Para filas con expediente:

puede utilizarse el snapshot del case cuando sea apropiado para mostrar
información documental.

Para la verdad histórica de consulta:

usar consultation.

Documentar qué campo proviene de qué fuente.

Evitar inconsistencias silenciosas.

---

# 33. PROYECCIÓN HISTÓRICA

Crear una capa clara de Application/Query, equivalente conceptual a:

ConsultationHistoryQuery
ConsultationNotificationProjection

Los nombres finales deben seguir arquitectura real.

No introducir lógica de proyección compleja directamente en Blade o
JavaScript.

---

# 34. CONTRATO DE FILA

Definir un DTO/read model explícito.

Ejemplo conceptual:

ConsultationHistoryRow {
  consultation_id
  consulted_at
  user
  vin
  plates
  make
  model
  year
  theft_status
  notification_case_relation
  notification_case_owner_type
  notification_status
  analyst_validation_status
  notification_deadline
  general_process_status
  available_actions
}

No usar exactamente este contrato si el modelo real requiere otro.

La intención es evitar arrays ad-hoc sin semántica clara.

---

# 35. EXPEDIENTE PROPIO VS OTRO USUARIO

La proyección debe distinguir explícitamente:

OWN_CASE
OTHER_USER_CASE
NO_CASE

No inferir esta condición exclusivamente desde colores.

No exponer `owner_user_id` innecesariamente al Cliente.

---

# 36. SEGURIDAD / IDOR

Portal Cliente:

- otra consultation ID;
- otro case ID;
- filtros alterados;
- AJAX request manual;
- pagination tampering;
- ordering tampering

no pueden revelar datos ajenos.

Portal Admin:

usuarios sin capability no pueden acceder al endpoint JSON.

Probar 403/404 conforme a convención.

---

# 37. RATE LIMIT / ABUSO

No convertir Sprint-06 en rediseño de rate limiting.

Pero limitar parámetros DataTables:

- length
- search length
- date ranges razonables cuando aplique

para evitar queries excesivas triviales.

No introducir rate limiter global nuevo salvo necesidad demostrada.

---

# 38. CSRF / HTTP

GET server-side DataTable puede utilizarse si es sólo lectura y patrón actual.

Si filtros sensibles usan POST, mantener CSRF.

No mutar ningún expediente desde el endpoint del historial.

---

# 39. MENÚ PORTAL CLIENTE

Crear/activar:

`Historial de Vehículos Consultados`

como opción separada de:

`Proceso de Notificaciones`

No fusionarlas.

Migration de datos reversible puede utilizarse siguiendo patrón aceptado de
SPRINT-04/05.

---

# 40. MENÚ PORTAL ADMIN

Crear/activar:

`Historial Global de Vehículos Consultados`

como opción separada de:

`Proceso de Notificaciones`

Migration reversible de menú permitida si necesaria.

---

# 41. DATATABLE CLIENTE

Debe ser visualmente coherente con portal actual.

No copiar lógica global Admin y filtrar en frontend.

Backend Cliente ya debe venir scoped.

Mostrar estados con badges/texto.

Prioridad:

pendiente propio
> otro usuario
> normal/no aplica

sin depender sólo de color.

---

# 42. DATATABLE ADMIN

Debe soportar:

- todos los Clientes;
- filtro por Cliente;
- información de consulta;
- estados derivados;
- acceso al expediente cuando corresponda.

No incluir acciones de edición directa en celdas que salten SPRINT-05.

---

# 43. EXPORTACIÓN

No implementar exportación CSV/Excel/PDF salvo que ya exista como requisito
aprobado.

Si DataTables actual trae botones por defecto, no habilitarlos para datos
globales sin evaluar authorization/performance.

Fuera de alcance por defecto.

---

# 44. STATUS NOTIFICADO — DECISIÓN DE PROYECCIÓN

Antes de codificar, documentar el mapping final.

Recomendación contractual:

NO
- NO_CASE
- PENDING

SI
- SUBMITTED
- UNDER_REVIEW
- REJECTED
- VALIDATED

Para CLOSED_NO_FOLLOW_UP:

determinar históricamente si el expediente llegó a submit.

Si nunca fue submit:
→ NO

Si alguna vez fue submit:
→ SI

Usar event history si es necesario.

No asumir que CLOSED implica automáticamente NO o SI.

Esta regla conserva la semántica:

"el Cliente alguna vez notificó/capturó y presentó el proceso"

más que:

"el estado actual es submitted".

Si el contrato aprobado previo define otra semántica explícita, seguirlo y
documentar.

---

# 45. STATUS VALIDADO — PROYECCIÓN

Mapping:

VALIDATED
→ SI

cualquier otro estado
→ NO

No requiere nuevo campo.

---

# 46. FECHA DE NOTIFICACIÓN

Si la UI requiere fecha en que el Cliente notificó:

obtener el primer evento efectivo de SUBMIT o timestamp persistido real.

No usar `updated_at`.

No usar fecha de validación.

No inventar si no existe.

---

# 47. CONSULTA DE OTRO POLICÍA

Para Cliente B:

si existe case de Cliente A dentro de ventana aplicable:

mostrar:

- Status Robo de la consulta B;
- estado limitado;
- mensaje de otro usuario;
- sin acceso al detalle privado.

No mostrar:

- documentos;
- motivo;
- correcciones;
- timeline;
- datos personales innecesarios del owner.

---

# 48. CONSULTA HISTÓRICA Y CASE POSTERIOR

Prueba obligatoria:

Consulta X ocurre en fecha T1.

Case para mismo VIN se crea en T2 > T1 sin que X fuera su consulta origen y
sin que la regla histórica lo haga aplicable.

La fila X NO debe mostrar ese case simplemente porque ahora exista.

Esta prueba es crítica.

---

# 49. MÚLTIPLES CASES MISMO VIN

Probar:

Case A
→ ventana 90 días

después:

Case B

Las consultas correspondientes deben proyectar el case correcto según
cronología y regla.

No tomar siempre el case más reciente.

---

# 50. VIN_NOT_AVAILABLE

Consultas por placa sin VIN:

no inferir VIN.

Mostrar:

`VIN NO DISPONIBLE`

o etiqueta existente.

La proyección de case debe utilizar identidad persistida real/criterio aprobado.

No resolver mediante heurística.

---

# 51. EXPEDIENTE VALIDADO DE OTRO USUARIO

Según regla contractual previa:

una consulta posterior dentro de la ventana puede encontrar expediente
VALIDATED de otro usuario.

No crear nuevo expediente.

Mostrar información limitada de que existe expediente aplicable/validado.

No transferir ownership.

---

# 52. ACTIONS READ MODEL

Las acciones deben calcularse server-side.

No enviar al frontend:

status
→ JS decide botón sin autorización.

El backend debe producir capabilities/action flags.

JavaScript sólo renderiza.

---

# 53. TESTS — PORTAL CLIENTE SCOPE

Probar:

- Cliente A sólo ve consultas A;
- Cliente B sólo ve B;
- Admin no se filtra accidentalmente como Cliente;
- query parameters no cambian owner;
- AJAX manual no revela otros users.

---

# 54. TESTS — PORTAL ADMIN SCOPE

Probar:

- Admin/Analista autorizado ve universo permitido;
- filtro por Cliente;
- soporte/no autorizado recibe 403;
- Cliente no accede endpoint Admin.

---

# 55. TESTS — STATUS ROBO

Probar:

- consulta positiva;
- consulta negativa;
- dato histórico no cambia aunque `vehicles` cambie después.

---

# 56. TESTS — CASE PROJECTION

Probar:

1. consultation origin → own case;
2. same user within window;
3. other user within window;
4. other user + VALIDATED;
5. consultation >90 days → new case;
6. previous case reference;
7. no case;
8. VIN_NOT_AVAILABLE;
9. multiple cases same vehicle;
10. case created later no retroactive false association.

---

# 57. TESTS — STATUS DERIVATION

Probar mapping:

- no case
- PENDING
- SUBMITTED
- UNDER_REVIEW
- REJECTED
- VALIDATED
- CLOSED before submit
- CLOSED after prior submit cuando sea representable

para:

- Status Notificado
- Status Validado
- Estado General
- Fecha límite
- Fecha de notificación cuando aplique.

---

# 58. TESTS — ACTIONS

Probar:

OWN PENDING
OWN REJECTED
OWN SUBMITTED
OWN UNDER_REVIEW
OWN VALIDATED
OWN CLOSED
OTHER USER CASE
NO CASE

Las acciones server-side deben corresponder exactamente.

---

# 59. TESTS — SERVER-SIDE DATATABLES

Probar:

- draw;
- pagination;
- length;
- max length;
- search;
- filters;
- ordering;
- invalid order column;
- SQL injection-like input;
- empty dataset;
- page beyond end;
- stable ordering;
- recordsTotal;
- recordsFiltered.

---

# 60. TESTS — PERFORMANCE

Verificar:

- query count razonable;
- sin N+1;
- no `get()` de universo completo;
- limit/offset presentes;
- filtros server-side;
- EXPLAIN documentado.

Si se agrega índice:

probar migration up/down.

---

# 61. TESTS — SECURITY

Manipular:

- user_id;
- client filter;
- case_id;
- consultation_id;
- order column;
- search;
- page size;
- status filter;
- date range.

No debe existir:

- SQL injection;
- IDOR;
- data leak;
- información de otro user en Cliente.

---

# 62. REGRESIÓN

Baseline esperado:

94 tests
387 assertions
0 fallos

Registrar baseline real al iniciar.

Preservar:

- SPRINT-02 core;
- SPRINT-03 Evidence;
- SPRINT-04 Cliente;
- SPRINT-05 Admin;
- double submit;
- double VIN;
- validation races;
- auto-close races;
- authorization;
- outbox;
- audit.

---

# 63. MIGRATIONS

Preferencia:

sólo migrations de datos reversibles para menú/permisos.

No crear schema nuevo salvo necesidad demostrada.

Si un índice nuevo está claramente justificado:

- migration aditiva;
- portable MySQL/MariaDB;
- up/down probado;
- documentar EXPLAIN antes/después;
- no producción.

No añadir columnas booleanas:

status_notified
status_validated

sólo para UI.

---

# 64. PRODUCTION GATES

SPRINT-06 puede ayudar a cerrar parcialmente:

- índices / EXPLAIN

pero sólo si existe dataset representativo suficiente.

Permanecen acumulados:

- MariaDB 10.6.27;
- idempotencia request→consulta;
- deuda histórica migrations;
- malware scanning;
- storage/fileinfo/GD/permisos Neubox;
- backup/rollback;
- autorización productiva.

No modificar producción.

---

# 65. FUERA DE ALCANCE

NO implementar:

- email delivery;
- Notification Center persistente;
- outbox worker;
- Cron/cPanel;
- Scheduler productivo;
- producción;
- deployment;
- retroactivos;
- backfill;
- analytics;
- exportaciones;
- dashboards;
- reapertura VALIDATED;
- modificación VIN;
- merge VIN;
- wallet/provider refactor;
- cambios a regla 3/30/90.

---

# 66. ORDEN RECOMENDADO

1. Governance.
2. Baseline.
3. Preflight.
4. Modelar projection/read model.
5. Definir mapping status derivado.
6. Tests projection.
7. Query Cliente.
8. Endpoint server-side Cliente.
9. View/DataTable Cliente.
10. Query Admin.
11. Filtro Cliente.
12. Endpoint server-side Admin.
13. View/DataTable Admin.
14. Actions server-side.
15. Security/IDOR.
16. Multiple case chronology tests.
17. Performance/EXPLAIN.
18. Menús.
19. Regression.
20. Pint.
21. git diff --check.
22. Documentation.
23. SPRINT-06-RESULT.
24. STOP.

---

# 67. CONDICIONES DE PARADA

Detener la parte afectada si:

- no puede determinarse históricamente qué case corresponde a una consultation;
- la implementación real contradice la regla 90 días;
- se requiere cambiar ownership;
- se requiere usar `vehicles` como master;
- se requiere nuevo booleano persistente;
- se requiere cambiar estados;
- se requiere crear entidad Client nueva;
- se requiere producción;
- una migration de schema no es portable;
- mapping de Status Notificado contradice decisión contractual previa no
  resoluble.

No detener el Sprint por errores ordinarios.

---

# 68. CRITERIOS DE ACEPTACIÓN

## Governance
- [ ] DEC-036 canónico.
- [ ] DEC-041 cierre SPRINT-05.
- [ ] producción no autorizada.

## Separation
- [ ] consultations sigue siendo historial.
- [ ] cases siguen separados.
- [ ] vehicles no es master.

## Cliente
- [ ] nuevo Historial de Vehículos Consultados.
- [ ] sólo consultas propias.
- [ ] server-side.
- [ ] estados derivados.
- [ ] otro usuario mostrado de forma limitada.
- [ ] acciones seguras.

## Admin
- [ ] Historial Global.
- [ ] todos los Clientes permitidos.
- [ ] filtro por Cliente.
- [ ] server-side.
- [ ] enlace a SPRINT-05.

## Projection
- [ ] originating consultation.
- [ ] within 90 days.
- [ ] >90 days.
- [ ] other user.
- [ ] validated other user.
- [ ] multiple cases.
- [ ] no retroactive false association.
- [ ] VIN_NOT_AVAILABLE.

## Status
- [ ] Status Robo histórico.
- [ ] Status Notificado derivado.
- [ ] Status Validado derivado.
- [ ] deadline.
- [ ] Estado General.
- [ ] no booleans duplicados.

## Performance
- [ ] server-side pagination.
- [ ] no universe get().
- [ ] no N+1.
- [ ] filters SQL.
- [ ] ordering allowlist.
- [ ] EXPLAIN documentado.

## Security
- [ ] Cliente isolation.
- [ ] Admin authorization.
- [ ] no IDOR.
- [ ] no SQL injection.
- [ ] page/length limitado.

## Quality
- [ ] suite completa verde.
- [ ] Pint.
- [ ] diff check.
- [ ] sin producción.

---

# 69. SPRINT-06-RESULT.md

Generar:

docs/sprints/SPRINT-06-RESULT.md

Estructura mínima:

1. Executive Summary
2. Scope Completed
3. Explicit Exclusions
4. Governance
5. Baseline
6. Preflight
7. Historical Projection Design
8. Consultation → Case Resolution
9. 90-Day Historical Semantics
10. Own vs Other User Case
11. Client History Architecture
12. Admin Global History Architecture
13. Server-side DataTables Protocol
14. Client Query
15. Admin Query
16. Client Filter
17. Status Robo
18. Status Notificado Projection
19. Status Validado Projection
20. General Process Status
21. Deadline / Notification Date
22. Actions Read Model
23. VIN_NOT_AVAILABLE
24. Multiple Cases Same Vehicle
25. No Retroactive False Association
26. Authorization / IDOR
27. Search / Filters / Ordering
28. Performance / Query Count
29. Indexes / EXPLAIN
30. Menu / Permissions
31. Tests Added
32. Tests Executed
33. Regression
34. Files Modified
35. Database Operations
36. Migrations
37. Limitations
38. Production Gates
39. Requirement Traceability
40. Acceptance Criteria
41. Recommended Follow-up
42. Sprint Conclusion

Incluir cifras exactas.

---

# 70. ATTESTATION FINAL

Historial Cliente:
IMPLEMENTADO LOCALMENTE

Historial Global Administrativo:
IMPLEMENTADO LOCALMENTE

Filtro por Cliente:
IMPLEMENTADO LOCALMENTE

Server-side pagination/filtering:
IMPLEMENTADO LOCALMENTE

Proceso de Notificaciones Cliente:
PRESERVADO

Proceso Administrativo:
PRESERVADO

Evidence:
PRESERVADO

Notification Delivery:
NO IMPLEMENTADO

Cron/cPanel:
NO CONFIGURADO

Producción:
NO MODIFICADA

SPRINT-07:
NO INICIADO

Finalizar:

READY FOR OWNER REVIEW

STOP.