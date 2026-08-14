# VINTrack — SPRINT-05
# Administrative Portal — Review & Validation

**Estado:** PENDING OWNER AUTHORIZATION
**Tipo:** Implementación local + pruebas
**Baseline:** SPRINT-04 APPROVED WITH OBSERVATIONS
**Roadmap canónico:** DEC-036
**Último cierre:** DEC-039
**Producción:** NOT AUTHORIZED
**Resultado obligatorio:** docs/sprints/SPRINT-05-RESULT.md

---

# 1. OBJETIVO

Implementar localmente el Portal Administrativo para que los usuarios
administrativos autorizados, particularmente el Analista, puedan revisar,
cotejar, corregir y resolver los expedientes de notificación generados por
los Clientes/Policías.

SPRINT-05 debe reutilizar obligatoriamente:

- dominio y persistencia de SPRINT-02;
- Evidence & Secure File Management de SPRINT-03;
- reglas y experiencia Cliente de SPRINT-04 cuando sean aplicables;
- máquina de estados;
- authorization/capabilities;
- optimistic locking/concurrencia;
- auditoría append-only;
- outbox transaccional.

Al finalizar, un Analista autorizado debe poder:

1. listar expedientes sujetos a revisión;
2. consultar expedientes de diferentes clientes conforme a capability;
3. abrir el detalle;
4. identificar al Cliente/Policía responsable;
5. revisar todos los datos capturados;
6. visualizar/listar evidencias;
7. descargar evidencias de forma privada y autorizada;
8. corregir campos permitidos;
9. iniciar/tomar revisión cuando corresponda;
10. validar un expediente;
11. rechazar un expediente indicando obligatoriamente el motivo;
12. consultar trazabilidad relevante;
13. operar siempre dentro de la máquina de estados aprobada.

NO implementar todavía el Historial Global de Vehículos Consultados.

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

Inspeccionar además la implementación real antes de asumir:

- clases;
- commands;
- repositories;
- tablas;
- columnas;
- capabilities;
- estados;
- rutas;
- middleware;
- layouts;
- eventos;
- outbox;
- lock_version;
- documentos.

La precedencia documental continúa siendo la definida en AGENTS.md.

No corregir silenciosamente contradicciones contractuales.

---

# 3. NUEVA DECISIÓN DEL PROJECT OWNER — MOTIVO DE RECHAZO

Registrar en DECISION_LOG.md utilizando el siguiente DEC disponible.

No asumir número.

Regla aprobada:

Toda transición administrativa a:

REJECTED

requiere obligatoriamente un motivo textual de rechazo.

El motivo:

- no puede estar vacío;
- debe validarse server-side;
- debe normalizarse conforme a las reglas textuales contractuales aplicables;
- debe quedar asociado inequívocamente a la transición;
- debe persistirse de forma recuperable;
- debe quedar auditado;
- debe identificar actor y timestamp mediante la infraestructura existente;
- debe ser visible posteriormente para el Cliente;
- debe permanecer históricamente disponible aunque el expediente sea
  corregido y reenviado.

La UI puede exigirlo client-side, pero la autoridad es server-side.

No crear automáticamente una columna nueva.

Primero inspeccionar si:

- events;
- audit metadata;
- transition metadata;
- case history;

ya proporcionan persistencia adecuada y consultable.

Si el modelo actual NO permite recuperar de manera segura y eficiente el
último motivo y su historia, diseñar la modificación física mínima necesaria.

Si dicha modificación cambia un contrato aprobado o introduce una nueva
entidad conceptual no prevista, detener esa parte y solicitar decisión.

---

# 4. PRINCIPIO DE AUTORIDAD ADMINISTRATIVA

"Administrativo" NO significa acceso irrestricto.

Toda acción debe exigir:

1. autenticación;
2. tipo de rol/capability apropiado;
3. autorización server-side sobre la operación;
4. estado compatible;
5. invariantes del dominio;
6. concurrencia/locking cuando corresponda.

No autorizar acciones solamente porque:

- la URL pertenece a /admin;
- el menú está oculto;
- el usuario tiene cualquier rol administrativo;
- existe un botón;
- el frontend envía un status permitido.

Usar las capabilities contractuales reales.

---

# 5. PREFLIGHT OBLIGATORIO

Antes de implementar:

1. verificar repository status;
2. verificar PROJECT_STATE;
3. verificar DEC-036 y DEC-039;
4. ejecutar suite baseline completa;
5. registrar tests/assertions/fallos;
6. inspeccionar Portal Administrativo actual;
7. inspeccionar navegación/menu administrativo;
8. inspeccionar roles y RoleType administrativo;
9. inspeccionar capabilities de expedientes;
10. inspeccionar máquina de estados real;
11. inspeccionar transition services;
12. inspeccionar audit/event/outbox;
13. inspeccionar NotificationCaseDocument;
14. inspeccionar descarga administrativa existente o ausente;
15. inspeccionar lock_version;
16. inspeccionar soft removal;
17. inspeccionar consultas/paginación existentes;
18. inspeccionar esquema real para motivo de rechazo;
19. inspeccionar implementación de auto-close;
20. inspeccionar comportamiento concurrente validate/reject/auto-close;
21. inspeccionar tratamiento existente de VIN_NOT_AVAILABLE;
22. inspeccionar mecanismo excepcional de asignación de VIN;
23. inspeccionar patrones de FormRequest/DTO/controller;
24. inspeccionar CSRF/routing;
25. identificar migrations potencialmente necesarias;
26. presentar hallazgos y riesgos antes de implementar.

No modificar producción.

---

# 6. NUEVA OPCIÓN / MÓDULO ADMINISTRATIVO

Crear o integrar una opción administrativa claramente identificable como:

`Proceso de Notificaciones`

o utilizar el nombre contractual existente si ya fue aprobado.

Este módulo representa expedientes.

NO representa el historial global de consultas.

No mezclar:

Notification Cases

con:

Consultations History

El Historial Global de Vehículos Consultados permanece en SPRINT-06.

---

# 7. LISTADO ADMINISTRATIVO DE EXPEDIENTES

Mostrar expedientes accesibles al usuario administrativo según capability.

Como mínimo:

- folio;
- VIN;
- Cliente/Policía responsable;
- placas;
- marca;
- modelo cuando exista;
- año;
- fecha de apertura;
- fecha límite;
- Estado General;
- fecha de envío;
- última actualización;
- acciones disponibles.

Puede incorporarse información adicional aprobada si mejora la revisión sin
convertir este listado en Historial Global de Consultas.

---

# 8. FILTROS ADMINISTRATIVOS

Implementar filtros razonables para la operación del Analista, como mínimo
cuando el modelo real lo permita:

- folio;
- VIN;
- Cliente/usuario responsable;
- estado;
- rango de fecha.

No implementar filtros mediante carga completa de la tabla en memoria si el
volumen puede crecer.

Preferir queries server-side/paginadas.

No implementar todavía la arquitectura DataTables contractual de SPRINT-06
salvo que ya exista y pueda reutilizarse sin ampliar alcance.

---

# 9. PRIORIZACIÓN

La UI debe permitir identificar fácilmente expedientes que requieren acción.

Particularmente:

SUBMITTED
→ requiere revisión administrativa.

UNDER_REVIEW
→ revisión en curso.

REJECTED
→ devuelto al Cliente.

VALIDATED
→ concluido satisfactoriamente.

CLOSED_NO_FOLLOW_UP
→ cerrado automáticamente.

No usar color como única representación.

No convertir prioridad visual en una segunda regla de negocio.

---

# 10. DETALLE DEL EXPEDIENTE

La vista administrativa debe mostrar:

- folio;
- VIN;
- Cliente/Policía responsable;
- consulta origen cuando corresponda;
- datos snapshot;
- campos capturados/corregidos;
- Estado General;
- fechas relevantes;
- fecha límite;
- evidencias;
- motivo(s) de rechazo relevantes;
- información de trazabilidad funcional necesaria.

No exponer:

- provider raw payload innecesario;
- wallet internals;
- storage paths;
- storage keys;
- secretos;
- hashes salvo necesidad administrativa aprobada;
- información técnica de auditoría innecesaria.

---

# 11. FOLIO Y VIN

Folio:

- server-owned;
- read-only;
- inmutable.

VIN normal:

- server-owned;
- read-only;
- inmutable;
- no puede ser corregido por Analista.

No crear input administrativo que permita modificar libremente VIN.

No confiar en hidden fields.

---

# 12. CAMPOS EDITABLES POR EL ANALISTA

El Analista autorizado puede corregir, conforme a capability, los campos
capturables del expediente excepto VIN y folio.

Campos:

- lugar de recuperación;
- país;
- estado;
- alcaldía/municipio;
- colonia;
- código postal;
- calle;
- número;
- fecha/hora de recuperación;
- placas;
- marca;
- modelo;
- año;
- número de motor;
- color;
- procedencia;
- autoridad;
- IPH;
- NUC;
- carpeta de investigación;
- resguardo;
- inventario;
- notas.

No permitir modificación directa de:

- id;
- folio;
- VIN;
- owner;
- consultation_id;
- status mediante formulario genérico;
- opened_at;
- submission_due_at;
- validated_at;
- closed_at;
- lock_version arbitrario;
- audit fields;
- storage metadata.

---

# 13. NORMALIZACIÓN ADMINISTRATIVA

Las correcciones textuales del Analista están sujetas a la misma regla
canónica:

- MAYÚSCULAS;
- SIN ACENTOS;
- SIN DIÉRESIS.

La normalización debe realizarse server-side.

No crear una implementación distinta de la utilizada en Portal Cliente.

Reutilizar el normalizador existente.

---

# 14. RECOVERED_AT

Las correcciones administrativas de recovered_at también respetan DEC-038:

recovered_at <= current business datetime

Timezone:

America/Mexico_City

El Analista NO puede introducir una fecha futura.

Validación server-side obligatoria.

---

# 15. CAMPOS OBLIGATORIOS

El Analista puede corregir datos, pero no debe poder validar un expediente
que incumpla las invariantes contractuales de submit/validación.

Antes de VALIDATED verificar mediante Domain/Application:

- VIN;
- lugar de recuperación;
- país;
- estado;
- alcaldía/municipio;
- recovered_at;
- placas;
- marca;
- año;
- procedencia;
- autoridad;
- carpeta de investigación;
- resguardo;
- IPH OR NUC;
- demás invariantes contractuales existentes.

No duplicar esta lista como segunda autoridad si ya existe Value Object /
validator / domain service reutilizable.

---

# 16. EVIDENCIAS

Reutilizar exclusivamente SPRINT-03.

El Analista autorizado debe poder:

- listar evidencia;
- descargar evidencia privada.

No crear URLs públicas.

No exponer storage path.

No duplicar download logic.

No permitir acceso por document_id aislado sin case authorization.

---

# 17. MODIFICACIÓN ADMINISTRATIVA DE EVIDENCIAS

Baseline recomendado para SPRINT-05:

El Analista revisa y descarga evidencia, pero NO sustituye silenciosamente
la evidencia presentada por el Cliente.

No habilitar upload/remove administrativo por defecto sólo porque existe
capability técnica.

Si los documentos son insuficientes:

→ REJECTED
→ motivo obligatorio
→ Cliente corrige/agrega/remueve evidencia
→ resubmit.

Esto conserva la procedencia documental.

Si la documentación contractual vigente ya autoriza expresamente al Analista
a agregar/remover evidencia, seguir el contrato y documentar la diferencia.

No inventar esa autorización.

---

# 18. CORRECCIONES ADMINISTRATIVAS Y AUDITORÍA

Toda corrección del Analista debe quedar auditada.

Para cada campo efectivamente modificado registrar de forma recuperable:

- campo;
- valor anterior;
- valor nuevo;
- actor;
- timestamp;
- contexto/correlation cuando corresponda.

No registrar eventos de cambio para campos cuyo valor efectivo no cambió.

No guardar secretos ni payload completo.

Los valores auditados deben respetar la política existente de privacidad.

Si la infraestructura actual no permite registrar before/after de forma
adecuada, implementar la extensión mínima compatible.

No crear una segunda tabla de auditoría si la existente es suficiente.

---

# 19. INICIAR REVISIÓN

Cuando el expediente se encuentre:

SUBMITTED

el Analista autorizado debe poder iniciar/tomar revisión mediante la transición
canónica:

SUBMITTED → UNDER_REVIEW

Usar el command/service existente o extender correctamente Application/Domain.

No hacer:

case.status = UNDER_REVIEW

desde Controller.

La operación debe:

- autorizar;
- bloquear/versionar;
- validar estado;
- transicionar;
- auditar;
- generar outbox/evento cuando corresponda;
- ser consistente ante retry.

---

# 20. VALIDACIÓN

Desde:

UNDER_REVIEW

el Analista autorizado puede ejecutar:

UNDER_REVIEW → VALIDATED

si todas las invariantes contractuales están satisfechas.

No permitir VALIDATED mediante edición directa del campo status.

Registrar:

- actor;
- timestamp;
- transición;
- evento;
- auditoría;
- outbox cuando corresponda.

La transición debe ser atómica.

---

# 21. RECHAZO

Desde el estado permitido por la máquina contractual, el Analista puede
ejecutar:

→ REJECTED

utilizando transition service.

Motivo obligatorio.

El rechazo:

- conserva case id;
- conserva folio;
- conserva VIN;
- conserva owner;
- conserva historial;
- conserva evidencia existente;
- no crea un expediente nuevo;
- habilita nuevamente al Cliente según capabilities existentes;
- permite corrección y resubmit posterior.

No utilizar la antigua estrategia conceptual de cambiar un booleano
"Status Notificado por Cliente = No".

La máquina de estados es la autoridad.

---

# 22. MOTIVO DE RECHAZO

Validar server-side.

Debe rechazarse:

- null;
- empty;
- whitespace-only.

Aplicar normalización textual contractual.

Definir longitud máxima razonable basándose primero en schema/patrón existente.

No truncar silenciosamente.

El motivo debe ser recuperable posteriormente.

Debe mostrarse al Cliente cuando el expediente esté REJECTED.

Si existen múltiples ciclos:

SUBMITTED
→ UNDER_REVIEW
→ REJECTED
→ PENDING/RESUBMIT
→ ...
→ REJECTED

preservar historial de motivos.

No sobrescribir la historia con el último motivo.

La UI puede destacar el último motivo vigente y permitir consultar historia
cuando sea razonable.

---

# 23. VISIBILIDAD DEL MOTIVO EN PORTAL CLIENTE

SPRINT-05 está autorizado a realizar la modificación mínima necesaria al
Portal Cliente de SPRINT-04 para mostrar el motivo de rechazo.

No rediseñar el Portal Cliente.

Cuando el expediente esté REJECTED:

mostrar claramente:

- estado;
- último motivo de rechazo;
- fecha;
- información necesaria para que el Policía corrija.

No mostrar metadata interna innecesaria.

La modificación debe tener tests.

---

# 24. VALIDATED

Una vez VALIDATED:

- Cliente no edita;
- Analista no debe editar datos ordinariamente;
- documentos quedan funcionalmente read-only;
- expediente conserva trazabilidad;
- no se crea otro expediente por esta acción.

No implementar reapertura arbitraria de VALIDATED.

Si se requiere en el futuro, será una decisión contractual independiente.

---

# 25. CLOSED_NO_FOLLOW_UP

Este estado es AUTOMÁTICO.

El Analista:

- puede visualizarlo;
- puede consultar evidencia/historia según capability;

pero NO puede seleccionarlo manualmente.

No incluir:

"Estado = CERRADO POR FALTA DE SEGUIMIENTO"

en un dropdown administrativo editable.

No crear endpoint manual de cierre por falta de seguimiento.

---

# 26. CONTROL DEL ESTADO GENERAL

El Analista NO recibe un dropdown libre con todos los estados.

La UI debe mostrar únicamente acciones/transiciones legales derivadas de la
máquina de estados/capabilities.

Ejemplo:

SUBMITTED
→ INICIAR REVISION

UNDER_REVIEW
→ VALIDAR
→ RECHAZAR

No:

<select name="status">
  PENDING
  SUBMITTED
  UNDER_REVIEW
  REJECTED
  VALIDATED
  CLOSED...
</select>

Las transiciones deben ser commands explícitos.

---

# 27. REJECTED Y RESUBMIT

SPRINT-04 ya consume REJECTED.

Verificar regresión completa:

REJECTED
→ Cliente puede editar
→ Cliente corrige
→ Cliente gestiona evidencia según capability
→ Cliente resubmit
→ vuelve al estado contractual correspondiente.

No cambiar folio.

No crear expediente nuevo.

Preservar motivo anterior en historia.

---

# 28. VIN EXCEPCIONAL — OBS-04-04

Inspeccionar el mecanismo real existente para:

VIN_NOT_AVAILABLE

y cualquier command/capability de asignación excepcional de VIN creado en
SPRINT-02.

NO habilitar edición general del VIN.

Si ya existe un flujo contractual de asignación única:

- exponerlo únicamente a capability administrativa específica;
- sólo cuando el expediente no tenga VIN verificable;
- validar VIN;
- garantizar asignación única;
- auditar;
- mantener idempotencia/concurrencia;
- bloquear cambios posteriores.

Si NO existe contrato suficientemente definido para exponerlo:

- no inventar UI;
- documentar como Production Gate/follow-up;
- conservar OBS-04-04 abierta.

---

# 29. DOBLE ASIGNACIÓN VIN

Si el flujo excepcional anterior se implementa/exhibe en SPRINT-05, ejecutar
prueba concurrente real:

mismo expediente sin VIN
+ dos asignaciones VIN simultáneas

Resultado:

- máximo una asignación efectiva;
- VIN final consistente;
- auditoría/eventos consistentes;
- segundo intento estable.

Si el flujo no aplica:

NOT APPLICABLE TO SPRINT-05

y conservar Production Gate.

---

# 30. CONCURRENCIA VALIDATE VS REJECT

Ejecutar prueba real con conexiones/procesos separados:

mismo expediente UNDER_REVIEW
+
Analista A → VALIDATE
Analista B → REJECT

Resultado requerido:

- exactamente una transición efectiva;
- estado final único y legal;
- sin combinación contradictoria;
- eventos coherentes;
- outbox coherente;
- lock_version consistente;
- segundo proceso obtiene resultado estable.

No aceptar:

VALIDATED + REJECTED simultáneamente.

---

# 31. CONCURRENCIA VALIDATE VS AUTO-CLOSE

OBS-02 mantiene este Production Gate.

Inspeccionar primero la implementación real de auto-close.

Si existe un command/domain operation invocable localmente sin Cron, ejecutar
prueba real concurrente:

expediente elegible
+
VALIDATE
vs
AUTO-CLOSE

Resultado:

- una sola transición final;
- nunca dos estados finales;
- eventos/outbox consistentes;
- locking/versioning correcto.

NO configurar Cron.

Si auto-close todavía no existe como operación ejecutable, documentar:

NOT TESTABLE IN SPRINT-05

y conservar el Production Gate para SPRINT-07/08.

No implementar Scheduler sólo para cerrar esta prueba.

---

# 32. CONCURRENCIA REJECT VS AUTO-CLOSE

Aplicar el mismo criterio cuando técnicamente proceda.

Si ambos commands existen:

probar carrera real.

Si no:

documentar claramente y mantener Gate.

---

# 33. DEADLOCK / RETRY

No introducir un mecanismo global nuevo de retry sin necesidad.

Durante las pruebas concurrentes:

- detectar deadlocks;
- documentar comportamiento;
- comprobar rollback;
- comprobar ausencia de efectos parciales.

Si aparece una necesidad concreta de retry transaccional, implementar la
solución mínima consistente con arquitectura existente y probarla.

No ocultar deadlocks con retries infinitos.

---

# 34. AUTHORIZATION / IDOR

Crear al menos:

- Cliente A;
- Cliente B;
- Analista autorizado;
- usuario administrativo sin capability cuando sea posible.

Probar:

Analista autorizado:
- lista;
- abre;
- descarga;
- corrige;
- inicia revisión;
- valida;
- rechaza.

Cliente:
- no accede rutas administrativas.

Admin sin capability:
- no obtiene acceso por pertenecer genéricamente al portal admin.

Manipular:

- case_id;
- document_id;
- owner_id;
- user_id;
- VIN;
- status;
- route binding.

Nunca devolver datos de expediente no autorizado.

---

# 35. MASS ASSIGNMENT

Usar mapping explícito.

Payload administrativo NO puede modificar arbitrariamente:

- id;
- folio;
- VIN normal;
- owner;
- consultation_id;
- status;
- opened_at;
- submission_due_at;
- validated_at;
- closed_at;
- lock_version;
- audit actor;
- document storage metadata.

Las transiciones se ejecutan mediante commands separados.

---

# 36. CSRF / HTTP

Mantener CSRF.

No usar GET para mutaciones.

Separar endpoints conceptualmente:

UPDATE CASE DATA
START REVIEW
VALIDATE
REJECT

No crear un endpoint genérico:

UPDATE EVERYTHING

que acepte status y datos indistintamente.

---

# 37. AUDITORÍA ADMINISTRATIVA

Como mínimo demostrar auditoría de:

- corrección de datos;
- inicio de revisión;
- validación;
- rechazo;
- motivo;
- asignación excepcional VIN si aplica.

Para correcciones registrar before/after.

Para transiciones registrar transición.

Evitar duplicación de eventos cuando una misma operación sea retry.

---

# 38. OUTBOX

Mantener outbox transaccional.

SPRINT-05 puede producir filas outbox legítimas.

NO entregar mensajes.

NO enviar email.

NO configurar worker.

NO configurar Cron.

SPRINT-07 sigue siendo responsable de delivery/automation.

---

# 39. UI ADMINISTRATIVA

Respetar diseño VINTrack existente.

No rediseñar globalmente.

Organización sugerida:

A. Identificación
B. Cliente responsable
C. Datos de recuperación
D. Ubicación
E. Vehículo
F. Autoridad / investigación
G. Resguardo
H. Evidencias
I. Historial / rechazo
J. Acciones administrativas

Acciones críticas deben ser visualmente claras.

---

# 40. CONFIRMACIONES

Requerir confirmación UX antes de:

VALIDAR
RECHAZAR

Para rechazo, capturar motivo antes de confirmar.

La confirmación del navegador no sustituye autorización/idempotencia.

---

# 41. MENSAJES

Mensajes comprensibles:

- revisión iniciada;
- cambios guardados;
- expediente validado;
- expediente rechazado;
- motivo requerido;
- estado cambió concurrentemente;
- operación ya no disponible;
- acceso denegado;
- evidencia no disponible.

No exponer stack traces, SQL, paths o excepciones internas.

---

# 42. HISTORIAL FUNCIONAL

Mostrar al Analista la trazabilidad necesaria para entender el expediente,
utilizando eventos/auditoría existente.

Como mínimo deben poder identificarse razonablemente:

- creación;
- envío;
- revisión;
- rechazo(s);
- resubmit(s);
- validación;
- cierre automático cuando exista;
- correcciones administrativas relevantes.

No convertir la vista en un visor técnico de logs.

---

# 43. PERFORMANCE

Evitar N+1.

Paginar listado.

Aplicar filtros en SQL/query builder.

No cargar todos los expedientes globales con get() si el volumen crecerá.

No implementar todavía toda la optimización/DataTables de SPRINT-06.

Si una query crítica requiere índice nuevo, demostrarlo antes de migration.

---

# 44. MIGRATIONS

Preferencia: cero migrations de schema.

Migration de datos para menú/permisos administrativos puede ser necesaria y
es aceptable si sigue el patrón reversible aprobado en SPRINT-04.

Si el motivo de rechazo requiere persistencia adicional:

1. inspeccionar modelo existente;
2. justificar necesidad;
3. elegir cambio mínimo;
4. mantener compatibilidad MySQL 8.4.3 / MariaDB 10.6.27;
5. probar up/down en BD desechable;
6. no producción.

Si implica cambio contractual, detener y solicitar aprobación.

---

# 45. TESTS — LISTADO ADMINISTRATIVO

Probar:

- Analista autorizado ve expedientes permitidos;
- puede filtrar;
- Cliente A/B identificables correctamente;
- estado correcto;
- deadline correcto;
- acciones correctas;
- admin sin capability rechazado;
- Cliente rechazado.

---

# 46. TESTS — CORRECCIÓN

Probar:

- todos los campos permitidos;
- folio inmutable;
- VIN inmutable;
- owner inmutable;
- status no mass-assignable;
- normalización;
- recovered_at futuro rechazado;
- IPH/NUC;
- campos obligatorios antes de validate;
- before/after audit;
- no-op update no crea cambios falsos.

---

# 47. TESTS — EVIDENCIA

Probar desde Portal Administrativo:

- listado;
- descarga;
- expediente ajeno/no autorizado;
- documento de otro case;
- documento removido;
- storage key manipulada;
- sin URL pública.

Si upload/remove admin NO está autorizado, probar que los endpoints/UI no lo
permitan.

---

# 48. TESTS — START REVIEW

SUBMITTED → UNDER_REVIEW

Probar:

- autorizado;
- no autorizado;
- estado incorrecto;
- retry;
- lock_version;
- audit;
- event/outbox.

---

# 49. TESTS — VALIDATE

UNDER_REVIEW → VALIDATED

Probar:

- válido;
- invariantes incompletas;
- estado incorrecto;
- usuario sin capability;
- doble validate;
- audit;
- outbox;
- timestamp.

---

# 50. TESTS — REJECT

Probar:

- motivo válido;
- motivo vacío;
- whitespace;
- normalización;
- persistencia recuperable;
- auditoría;
- visibilidad Cliente;
- mismo folio;
- mismo case;
- evidencia preservada;
- REJECTED editable por Cliente;
- resubmit;
- historial de múltiples rechazos.

---

# 51. TESTS — STATE PROTECTION

Probar que no exista manipulación directa para forzar:

PENDING
SUBMITTED
UNDER_REVIEW
REJECTED
VALIDATED
CLOSED_NO_FOLLOW_UP

especialmente:

Analista → CLOSED_NO_FOLLOW_UP manual

debe ser imposible.

---

# 52. TESTS — CONCURRENCIA REAL

Obligatorio:

UNDER_REVIEW
+ VALIDATE
+ REJECT simultáneos

con conexiones/procesos separados.

Cuando sea técnicamente aplicable:

VALIDATE vs AUTO-CLOSE
REJECT vs AUTO-CLOSE
doble asignación VIN

Registrar resultados exactos.

---

# 53. TESTS — REGRESIÓN CLIENTE

Preservar SPRINT-04:

- listado propio;
- edición;
- recovered_at;
- evidence;
- submit;
- double submit;
- REJECTED → editable;
- motivo visible;
- resubmit;
- VALIDATED → read-only.

---

# 54. SUITE COMPLETA

Baseline esperado según cierre anterior:

89 tests
344 assertions
0 fallos

Pero registrar el baseline REAL observado al iniciar.

Al finalizar ejecutar suite completa.

No borrar/deshabilitar tests para obtener verde.

Ejecutar además:

- Pint sobre archivos afectados;
- git diff --check;
- análisis estático existente si forma parte del proyecto.

---

# 55. FUERA DE ALCANCE

NO implementar:

- Historial de Vehículos Consultados Cliente;
- Historial Global de Vehículos Consultados Admin;
- DataTables SPRINT-06;
- delivery;
- emails reales;
- Notification Center persistente;
- outbox worker;
- Cron;
- cPanel;
- Scheduler productivo;
- deployment;
- producción;
- purga física;
- malware scanner;
- backfill;
- expedientes retroactivos;
- cambios generales wallet/provider;
- rediseño global;
- reapertura de VALIDATED;
- cierre manual CLOSED_NO_FOLLOW_UP.

---

# 56. PRODUCTION GATES

No perder ninguno de los acumulados:

- MariaDB 10.6.27;
- idempotencia request → consulta;
- doble asignación VIN si continúa pendiente;
- validate vs auto-close;
- deadlock/retry;
- índices/EXPLAIN con volumen;
- deuda histórica de migrations;
- malware scanning;
- storage/fileinfo/GD/permisos Neubox;
- autorización explícita de producción.

SPRINT-05 puede cerrar alguno únicamente mediante evidencia real.

Documentar cuáles permanecen.

---

# 57. ORDEN DE IMPLEMENTACIÓN

1. Governance/read.
2. Baseline.
3. Preflight.
4. Registrar decisión motivo de rechazo.
5. Tests authorization.
6. Query/listado admin.
7. Detail.
8. Admin correction command.
9. Before/after audit.
10. Evidence read/download.
11. StartReview.
12. Reject + reason persistence.
13. Client reason display.
14. Validate.
15. State protections.
16. Exceptional VIN assessment.
17. Concurrent validate vs reject.
18. Auto-close races si aplican.
19. Regression Cliente.
20. Full suite.
21. Pint/static/diff.
22. Documentation.
23. SPRINT-05-RESULT.
24. STOP.

---

# 58. CONDICIONES DE PARADA

Detener la parte afectada y consultar al Project Owner si:

- motivo de rechazo requiere cambio contractual no previsto;
- máquina de estados contradice el rector;
- Analista no tiene capability contractual para corregir;
- evidencia administrativa exige cambiar ownership;
- se requiere hacer VIN libremente editable;
- se requiere crear estado nuevo;
- se requiere cierre manual;
- se requiere cambiar 3/30/90;
- se requiere modificar wallet/provider;
- se requiere infraestructura externa;
- se requiere producción;
- auto-close necesita Cron para ser probado;
- migration necesaria no es portable;
- conflicto documental no puede resolverse por precedencia.

Errores ordinarios de implementación/test deben corregirse dentro del Sprint.

---

# 59. CRITERIOS DE ACEPTACIÓN

## Governance
- [ ] DEC-036 roadmap canónico.
- [ ] DEC-039 cierre SPRINT-04.
- [ ] nueva decisión de rechazo registrada.
- [ ] producción no autorizada.

## Admin listing
- [ ] listado paginado.
- [ ] filtros.
- [ ] Cliente responsable.
- [ ] estados/deadlines.
- [ ] authorization.

## Detail
- [ ] todos los datos.
- [ ] folio/VIN read-only.
- [ ] evidencias.
- [ ] rechazo/historia.
- [ ] acciones legales.

## Correction
- [ ] campos permitidos.
- [ ] normalización.
- [ ] recovered_at.
- [ ] before/after audit.
- [ ] no mass assignment.

## Review
- [ ] SUBMITTED → UNDER_REVIEW.
- [ ] command explícito.
- [ ] audit/outbox.

## Reject
- [ ] motivo obligatorio.
- [ ] persistente.
- [ ] histórico.
- [ ] visible al Cliente.
- [ ] mismo case/folio.
- [ ] REJECTED editable.
- [ ] resubmit.

## Validate
- [ ] UNDER_REVIEW → VALIDATED.
- [ ] invariantes completas.
- [ ] audit/outbox.
- [ ] read-only posterior.

## Automatic states
- [ ] CLOSED_NO_FOLLOW_UP no seleccionable manualmente.

## Evidence
- [ ] private download.
- [ ] IDOR.
- [ ] no segundo storage subsystem.

## Security
- [ ] capabilities.
- [ ] IDOR.
- [ ] mass assignment.
- [ ] CSRF.
- [ ] state manipulation rejected.

## Concurrency
- [ ] validate vs reject real.
- [ ] una sola transición.
- [ ] eventos/outbox consistentes.
- [ ] otras carreras probadas si técnicamente aplican.

## Quality
- [ ] suite completa verde.
- [ ] regresión SPRINT-04.
- [ ] Pint.
- [ ] diff check.
- [ ] sin producción.

---

# 60. SPRINT-05-RESULT.md

Generar:

docs/sprints/SPRINT-05-RESULT.md

Estructura mínima:

1. Executive Summary
2. Scope Completed
3. Explicit Exclusions
4. Governance / Decisions
5. Baseline
6. Preflight Findings
7. Administrative Portal Architecture
8. Routes / Authorization
9. Administrative Case Listing
10. Filters / Pagination
11. Case Detail
12. Administrative Corrections
13. Before/After Audit
14. Evidence Review
15. Start Review
16. Validation
17. Rejection
18. Rejection Reason Persistence
19. Client Rejection Feedback
20. State / Capability Matrix
21. Automatic State Protection
22. Exceptional VIN Assessment
23. IDOR / Mass Assignment / CSRF
24. Audit / Events / Outbox
25. Concurrency — Validate vs Reject
26. Concurrency — Auto-close Races
27. Concurrency — VIN Assignment
28. Deadlock / Retry Findings
29. Tests Added
30. Tests Executed
31. Regression
32. Files Modified
33. Database Operations
34. Migrations
35. Performance Findings
36. Limitations / Risks
37. Production Gates
38. Requirement Traceability
39. Acceptance Criteria
40. Recommended Follow-up
41. Sprint Conclusion

Incluir comandos y cifras exactas.

No exponer secretos.

---

# 61. ATTESTATION FINAL

El resultado debe declarar explícitamente:

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

Finalizar exactamente:

READY FOR OWNER REVIEW

STOP.