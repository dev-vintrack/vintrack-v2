# VINTrack — SPRINT-02: Core Domain, Persistence & Consultation Admission

**Estado:** PENDIENTE DE AUTORIZACIÓN DE EJECUCIÓN DEL PROJECT OWNER  
**Tipo:** Implementación local del núcleo + pruebas  
**Baseline:** SPRINT-01 `APPROVED — OBSERVATIONS RESOLVED`  
**Entorno autorizado:** repositorio y base de datos local de desarrollo  
**Producción:** FUERA DE ALCANCE  
**Resultado obligatorio:** `docs/sprints/SPRINT-02-RESULT.md`

---

## 1. Objetivo

Implementar y probar localmente el núcleo transaccional del proceso de expedientes de notificación vehicular aprobado en SPRINT-01, sin construir todavía portales, formularios finales, archivos, entrega de notificaciones ni automatización productiva.

Al finalizar, VINTrack debe disponer de:

- esquema persistente portable y migrations nuevas;
- modelo de dominio y máquina de estados;
- ownership y autorización central del núcleo;
- bloqueo concurrente de consultas cuando el usuario tiene tres pendientes;
- reserva de capacidad antes de crédito/API;
- creación/reutilización idempotente de expedientes;
- folio legible concurrente;
- snapshot vehicular;
- extracción de VIN por servicio y excepción controlada para consultas por placa;
- primera asignación inmutable del VIN y detección de conciliación;
- reglas de 3, 30 y 90 días;
- auditoría append-only;
- outbox transaccional, sin entregar mensajes todavía;
- pruebas unitarias, integración, autorización, concurrencia y compatibilidad.

Este Sprint no activa la experiencia completa para usuarios ni despliega a producción.

---

## 2. Fuentes obligatorias y precedencia

Antes de modificar código, leer completamente:

```text
AGENTS.md
docs/VINTRACK_MASTER_SPEC.md
docs/ARCHITECTURE.md
docs/BUSINESS_RULES.md
docs/DATA_MODEL.md
docs/DECISION_LOG.md
docs/CHANGE_REQUESTS.md
docs/PROJECT_STATE.md
docs/sprints/SPRINT-00-RESULT.md
docs/sprints/SPRINT-01-DOMAIN-DATA-MODEL.md
docs/sprints/SPRINT-01-RESULT.md
```

La fuente contractual principal del modelo es el diseño final armonizado de SPRINT-01, DEC-028–DEC-034 y CR-002. No implementar desde borradores anteriores.

Toda afirmación sobre la implementación actual debe citar evidencia de archivo/clase/método/migration/tabla. Si una contradicción material permanece después de la armonización inicial, detener únicamente la parte afectada y solicitar decisión.

---

## 3. Preflight obligatorio: armonizar `AGENTS.md`

`AGENTS.md` conserva reglas anteriores y debe corregirse antes de implementar:

1. Sustituir la regla histórica “solo Placas e IPH” por la matriz vigente.
2. Documentar `IPH OR NUC` al enviar.
3. Incorporar la excepción de consulta por placa sin VIN recuperable: VIN nullable en borrador, asignación única antes de `SUBMITTED`, después inmutable.
4. Mantener folio siempre inmutable.
5. Incorporar `America/Mexico_City` como semántica temporal contractual del módulo.
6. Actualizar estado: SPRINT-01 aprobado con observaciones resueltas; SPRINT-02 es el Sprint actual solo después de autorización explícita.
7. Mantener `consultations.provider_service_id` singular y `vehicles` fuera como master.
8. No rebajar ninguna regla de seguridad, concurrencia, gobernanza o producción.

La corrección de `AGENTS.md` es una armonización documental aprobada por el Project Owner, no un cambio funcional independiente. Registrar el cambio en el resultado.

Después de armonizar, releer `AGENTS.md` y comprobar que no contradice los documentos aprobados.

---

## 4. Alcance autorizado

### 4.1 Persistencia local

Crear migrations Laravel nuevas, aditivas y reversibles cuando sea seguro, para las entidades físicas aprobadas en SPRINT-01, ajustando nombres únicamente a convenciones reales:

- `notification_cases`;
- `notification_case_documents` solo como tabla/metadata si una FK es necesaria para completar el esquema, sin implementar uploads en este Sprint;
- `notification_case_events`;
- `notification_outbox`;
- `portal_notifications` solo como esquema si es dependencia del outbox, sin bandeja/UI;
- `notification_case_sequences`;
- `notification_case_user_guards`;
- `notification_case_vin_guards`;
- guard provisional de identidad por placa, si el diseño final lo requiere como tabla separada;
- `notification_case_consultation_reservations`;
- `notification_case_vin_reconciliations`;
- configuración usando `global_configuration` existente cuando sea compatible, sin duplicar configuración.

Las migrations deben:

- usar InnoDB y `utf8mb4` conforme a convenciones;
- ser compatibles con MySQL 8.4.3 y MariaDB 10.6.27;
- evitar `ENUM`, índices funcionales, generated columns o JSON crítico no portable;
- usar FK `RESTRICT` para historia, sin cascadas destructivas;
- incluir constraints e índices justificados por queries reales;
- no alterar ni recrear `consultations.provider_service_id`;
- no agregar FK a `vehicles`;
- no duplicar `provider_service_id`, `source_criterion` ni `source_value` en `notification_cases`;
- preservar `notification_case_id` nullable solo para eventos/outbox pre-expediente permitidos;
- usar `DATETIME(0)` para `notification_deadline_at` y precisión aprobada para los demás DATETIME;
- documentar semántica `America/Mexico_City` sin depender de conversiones implícitas de `TIMESTAMP`.

### 4.2 Configuración

Implementar acceso central tipado a valores configurables, con defaults aprobados:

```text
deadline_days = 3
max_open_days = 30
reuse_days = 90
max_pending = 3
max_files = 8
max_file_bytes = 3145728
timezone = America/Mexico_City
reservation_ttl_seconds = valor determinado por evidencia de timeouts
```

No dispersar números mágicos. Si `global_configuration` no soporta de forma segura estas claves/tipos, detener esa parte y documentar una alternativa; no crear silenciosamente un segundo sistema de configuración.

No implementar purga. Retención 5/5/5/2 permanece documental y sin eliminación automática.

### 4.3 Dominio y aplicación

Implementar, respetando las capas actuales:

- agregado/entidad `NotificationCase`;
- value objects/enums de estado, folio, VIN, criterio canónico y claves idempotentes;
- política computable de pendiente;
- máquina de estados exacta;
- servicios de fechas 3/30/90 días;
- normalizador textual server-side;
- primera asignación de VIN;
- creación/reutilización del expediente;
- selección y enlace de `previous_case_id`;
- guards, reservas y locks;
- folio anual transaccional;
- auditoría append-only;
- outbox transaccional;
- repositorios/interfaces y adaptadores de persistencia;
- policies/authorization necesarias para los casos de uso del núcleo;
- DTOs/commands/handlers conforme a la arquitectura existente.

No colocar reglas de negocio en controladores, Eloquent models, Blade o JavaScript si pertenecen al dominio/aplicación.

### 4.4 Integración con consultas

Integrar el admission guard en el punto comprobado de `ConsultationService::consult()` o abstracción equivalente, antes de:

- validación/consumo de wallet que produzca side effects;
- débito;
- llamada al adapter/proveedor;
- cualquier operación externa facturable.

Regla:

```text
pendientes + reservas vivas >= 3
→ bloquear antes de crédito/API
```

La respuesta bloqueada debe ser una excepción/resultado de negocio estable, sin registrar consulta ni consumir recursos. Debe crear un evento pre-expediente idempotente `CONSULTATION_BLOCKED` y el outbox correspondiente si el diseño requiere intención futura, pero no entregar email/portal en este Sprint.

La reserva:

- se crea bajo lock del user guard;
- tiene `request_key` único;
- no implica débito;
- no mantiene transacción durante API remota;
- se consume/libera tras resultado;
- expira con TTL seguro;
- se recupera idempotentemente ante reintentos;
- nunca habilita una cuarta creación.

Mantener el orden existente de débito/API salvo lo estrictamente necesario para insertar el admission guard. No rediseñar wallet ni reembolsos.

### 4.5 Creación/reutilización del expediente

Después de una consulta positiva calificante:

1. resolver el VIN desde fuente canónica;
2. si criterio `niv`/`vin`, `consultations.valor` es VIN;
3. si criterio `placa`, usar mapper del servicio si devuelve VIN inequívoco;
4. si no hay VIN, usar identidad provisional derivada de `provider_service_id + PLATE + placa normalizada`;
5. bloquear guard correspondiente;
6. buscar expediente activo/aplicable;
7. aplicar regla de 90 días;
8. reutilizar/proyectar existente o crear uno solo;
9. fijar owner desde `consultations.user_id`;
10. guardar snapshot, fechas, folio, evento y outbox en la misma transacción;
11. consumir la reserva;
12. conservar `previous_case_id` cuando corresponda.

Una consulta origina como máximo un expediente. La creación debe usar `consultation_id UNIQUE`, `creation_key UNIQUE` y guards como defensas complementarias.

### 4.6 Mappers de VIN

Inspeccionar todos los adapters/provider services capaces de producir resultados positivos. Crear un inventario verificable:

```text
provider_service_id
adapter/clase
criterios soportados
path del VIN en respuesta
normalización
fixture anonimizado
resultado cuando falta/ambigua
```

Implementar mappers solo cuando exista evidencia/fixture suficiente. Nunca inferir un path de VIN.

Para un servicio por placa sin VIN, retornar explícitamente `VIN_NOT_AVAILABLE`, no error genérico. Para respuesta inesperada/ambigua, registrar fallo operacional y no inventar VIN.

No leer VIN desde `vehicles`.

### 4.7 Excepción de asignación única de VIN

Implementar command/use case para owner autorizado:

```text
AssignCaseVinOnce
```

Precondiciones:

- expediente propio;
- originado por `placa`;
- VIN y `vin_key` NULL;
- estado editable `PENDING` o `REJECTED` según diseño;
- permiso `case.vin.assign_once`;
- VIN válido;
- idempotency key;
- lock del caso y VIN guard.

Sin conflicto:

- asignar `vin` y `vin_key` una vez;
- emitir `CASE_VIN_ASSIGNED` con actor/IP/UA/procedencia;
- incrementar `lock_version`;
- prohibir cambios posteriores.

Con expediente aplicable del mismo VIN:

- no asignar silenciosamente ni permitir `SUBMITTED`;
- crear/reutilizar incidencia de conciliación idempotente;
- preservar ambos expedientes;
- no fusionar, borrar ni cambiar owner;
- no crear séptimo estado.

La resolución final de conciliaciones puede quedar fuera de este Sprint si no está suficientemente especificada; la detección, persistencia, bloqueo de submit y autorización sí forman parte del núcleo.

### 4.8 Máquina de estados

Implementar exactamente:

```text
PENDING
SUBMITTED
UNDER_REVIEW
REJECTED
VALIDATED
CLOSED_NO_FOLLOW_UP
```

Transiciones del núcleo:

- `PENDING → SUBMITTED`;
- `SUBMITTED → UNDER_REVIEW`;
- `SUBMITTED → REJECTED`;
- `UNDER_REVIEW → REJECTED`;
- `UNDER_REVIEW → VALIDATED`;
- `REJECTED → SUBMITTED`;
- estados pendientes → `CLOSED_NO_FOLLOW_UP` únicamente mediante servicio automático invocable, sin programar Cron todavía.

No permitir `SUBMITTED → VALIDATED`, `CLOSED` genérico, estado de conciliación, ni mutación desde terminales.

Implementar CAS con `lock_version` o lock equivalente. Evento y outbox pertenecen a la misma transacción.

### 4.9 Envío y validación del formulario en capa de aplicación

Aunque no se construya UI, implementar el command/use case de submit para probar invariantes.

Antes de `SUBMITTED` exigir:

- VIN;
- lugar de recuperación;
- país;
- estado;
- alcaldía/municipio;
- fecha/hora de recuperación;
- placas;
- marca;
- año;
- procedencia;
- autoridad;
- carpeta de investigación;
- resguardo;
- al menos IPH o NUC.

Permitir borradores parciales. Normalizar todos los textos capturables a mayúsculas sin acentos ni diéresis. VIN y folio no son editables; VIN solo admite la asignación excepcional una vez.

### 4.10 Auditoría y outbox

Implementar infraestructura mínima productiva del núcleo:

- evento append-only;
- `event_key` único;
- correlation/request keys;
- actor, rol efectivo, IP, UA, timestamps, estados, motivo y metadata limitada;
- diffs de correcciones cuando existan commands administrativos;
- evento con case nullable solo para allowlist pre-expediente;
- outbox insertada en la misma transacción;
- `dedup_key` único;
- payload mínimo y seguro.

No implementar worker, email, bandeja, plantillas ni entrega. Las filas deben quedar listas para un Sprint posterior.

---

## 5. Fuera de alcance

No implementar en SPRINT-02:

- páginas, menús o formularios finales del Portal Cliente;
- páginas o DataTables del Portal Administrativo;
- DataTables server-side;
- upload, download o eliminación de archivos;
- centro/bandeja de notificaciones;
- envío real de email;
- worker de outbox;
- comando Artisan de mantenimiento definitivo;
- Scheduler, cPanel Cron o endpoint HMAC;
- ejecución automática programada del cierre a 30 días;
- purga/retención automática;
- resolución/merge automático de conciliaciones VIN;
- migración/backfill de consultas históricas a expedientes;
- creación retroactiva de expedientes;
- cambios al módulo `vehicles` como master;
- cambios generales de wallet/reembolsos;
- despliegue, SQL o modificaciones en producción;
- modificación de datos productivos.

No exponer rutas temporales inseguras para “probar” el núcleo. Las pruebas deben invocar application services o endpoints existentes autorizados.

---

## 6. Estrategia de migrations y seguridad de datos

1. Inspeccionar esquema/migrations reales antes de crear archivos.
2. Crear migrations aditivas, ordenadas por dependencias.
3. Ejecutar únicamente en BD local de desarrollo.
4. Registrar estado previo de migrations y backup local razonable.
5. Ejecutar `migrate` local y comprobar schema.
6. Probar rollback en una BD de prueba desechable, no sobre datos valiosos.
7. Ejecutar ciclo migrate → rollback → migrate en entorno de test.
8. No usar `migrate:fresh`, `db:wipe`, truncate o reset sobre la BD local principal sin autorización explícita.
9. No generar SQL productivo en este Sprint.
10. No ejecutar migrations en MariaDB productivo.

Si existe una instancia MariaDB 10.6.27 de prueba autorizada, ejecutar la suite allí. Si no existe, marcar `NOT RUN — ENVIRONMENT UNAVAILABLE`; realizar revisión estática de portabilidad y no fingir compatibilidad ejecutada.

---

## 7. Pruebas obligatorias

Identificar primero la infraestructura de tests existente. Agregar tests en el nivel correcto y conservar regresión.

### 7.1 Unitarias de dominio

- seis estados exactos;
- matriz válida e inválida de transiciones;
- definición de pendiente;
- normalización de todos los campos textuales;
- VIN válido/inválido, `niv`/`vin` equivalentes y `placa`;
- folio y formato;
- cálculo 3 días a `23:59:59` en `America/Mexico_City`;
- frontera vigente `<=` y vencido `>`;
- 30 días desde `opened_at` sin reinicio;
- 90 días inclusivo y `>90`;
- IPH OR NUC;
- campos obligatorios al submit;
- inmutabilidad de owner, origin, folio y VIN asignado.

### 7.2 Persistencia/migrations

- migrate/rollback/migrate;
- PK, FK RESTRICT, uniques y nullable correctos;
- no existe FK a `vehicles`;
- no existen columnas duplicadas prohibidas;
- `consultation_id`, `case_number`, `creation_key`, `event_key` y dedup keys únicos;
- índices requeridos presentes;
- DATETIME correctos;
- insert/update válido en ambos motores disponibles;
- eventos post-caso rechazan case NULL en dominio;
- evento `CONSULTATION_BLOCKED` admite case NULL.

### 7.3 Integración de consultas

- con 0/1/2 pendientes la consulta llega al flujo existente;
- con 3 pendientes se bloquea antes de wallet/API;
- bloqueo no debita ni invoca adapter;
- bloqueo idempotente no duplica auditoría/outbox;
- reservas se crean, consumen, liberan y expiran;
- timeout/retry con misma key no duplica side effects;
- consulta negativa no crea expediente;
- positiva crea/reutiliza según reglas;
- no romper consultas existentes sin expediente.

### 7.4 Concurrencia

Pruebas reales con conexiones separadas/procesos cuando la infraestructura lo permita, no mocks que oculten locks:

- usuario con 2 pendientes y dos consultas simultáneas: solo una reserva cruza a capacidad 3;
- mismo `consultation_id` simultáneo: un expediente;
- mismo VIN por dos usuarios: un expediente aplicable/owner ganador conforme a orden real;
- folios simultáneos: únicos;
- doble submit: una transición/evento;
- doble asignación VIN: una asignación;
- asignación VIN contra expediente existente: incidencia única;
- validación versus cierre automático: una transición gana;
- deadlock/retry no duplica eventos/outbox.

Si Windows/Laragon limita el paralelismo del runner, crear un harness de test controlado dentro de la infraestructura de tests y documentar la limitación. No declarar una carrera probada si solo se ejecutó secuencialmente.

### 7.5 Autorización y seguridad

- owner accede a caso propio;
- otro cliente no ve/muta caso;
- manipular ID/folio/VIN no evade policy;
- Cliente no valida/rechaza/inicia revisión;
- Analista usa permisos explícitos;
- nadie modifica folio/owner/origin;
- VIN normal no se modifica;
- asignación única solo cumple origen/estado/permiso;
- `CLOSED_NO_FOLLOW_UP` no es seleccionable manualmente;
- mass assignment no toca campos protegidos;
- metadata de auditoría/outbox no guarda secretos/payload completo.

### 7.6 Mappers de proveedor

- fixture anonimizado por adapter/servicio soportado;
- NIV/VIN desde `consultations.valor`;
- placa con VIN extraíble;
- placa sin VIN retorna ausencia explícita;
- VIN ambiguo/malformado no crea caso con identidad inventada;
- nunca se consulta `vehicles` para VIN del expediente.

### 7.7 Regresión

- suite existente relevante;
- flujo de consulta exitoso;
- flujo de consulta fallido;
- wallet/débito sin cambio fuera del guard;
- provider adapters existentes;
- historial existente no roto por relaciones nuevas;
- roles/permisos existentes;
- compatibilidad de migrations previas.

### 7.8 Matriz de motores

| Suite | MySQL 8.4.3 local | MariaDB 10.6.27 de prueba |
|---|---|---|
| migrations | Obligatoria | Obligatoria si el entorno existe |
| dominio | Obligatoria | Obligatoria si runner disponible |
| integración | Obligatoria | Recomendable/obligatoria antes de producción |
| concurrencia | Obligatoria | Obligatoria antes de producción |
| EXPLAIN/índices | Obligatoria | Obligatoria antes de producción |

La ausencia de MariaDB de prueba no invalida automáticamente el desarrollo local, pero impide declarar `READY FOR PRODUCTION` y debe ser un riesgo bloqueante para despliegue posterior.

---

## 8. Criterios de aceptación

### Gobernanza

- [ ] `AGENTS.md` armonizado con SPRINT-01.
- [ ] `PROJECT_STATE.md` identifica SPRINT-02 como actual durante ejecución.
- [ ] No hay contradicciones documentales nuevas.

### Datos

- [ ] Migrations aditivas crean el esquema aprobado.
- [ ] No se altera `provider_service_id` ni `vehicles`.
- [ ] No se duplican criterio/valor/servicio en el caso.
- [ ] Constraints, FK e índices coinciden con queries/invariantes.
- [ ] Rollback probado en BD de test.

### Dominio

- [ ] Seis estados y transiciones exactas.
- [ ] Reglas 3/30/90 centralizadas.
- [ ] Folio concurrente/idempotente.
- [ ] Snapshot y ownership inmutables.
- [ ] Formulario y normalización validados en application layer.
- [ ] IPH OR NUC implementado.

### Consulta y concurrencia

- [ ] Cuarta consulta bloqueada antes de crédito/API.
- [ ] Carrera 2+2 probada con concurrencia real.
- [ ] Reservas no sostienen transacción remota.
- [ ] Creación/reutilización del caso es idempotente.
- [ ] No hay duplicados por consulta/VIN/placa provisional bajo las carreras cubiertas.

### VIN por placa

- [ ] Mappers evidenciados por servicio.
- [ ] Placa sin VIN crea borrador nullable cuando corresponde.
- [ ] No puede enviarse sin VIN.
- [ ] Asignación única auditada e inmutable.
- [ ] Conflicto genera incidencia, no merge ni séptimo estado.

### Seguridad y trazabilidad

- [ ] Policies/casos de uso impiden IDOR y mass assignment.
- [ ] Auditoría append-only transaccional.
- [ ] Outbox transaccional/deduplicada sin entrega.
- [ ] No se guardan secretos ni payloads innecesarios.

### Calidad

- [ ] Tests unitarios, integración, concurrencia, seguridad y regresión pasan.
- [ ] Resultados por motor se informan honestamente.
- [ ] No hay tests deshabilitados para ocultar fallos.
- [ ] Análisis estático/formato del proyecto pasa si existe.

### Alcance

- [ ] No se implementaron UI, archivos, delivery, Cron ni producción.
- [ ] No se crearon expedientes retroactivos.
- [ ] No se ejecutó nada en producción.

---

## 9. Orden recomendado de trabajo

1. Leer documentación y corregir `AGENTS.md`.
2. Inspeccionar código, migrations, test infrastructure y adapters.
3. Presentar inventario de archivos/clases previstos y riesgos.
4. Agregar tests de dominio que fallen por funcionalidad ausente.
5. Crear migrations y probar ciclo local.
6. Implementar value objects, estados, fechas y normalización.
7. Implementar repositories, aggregate, audit y outbox.
8. Implementar guards/reservas/folio.
9. Implementar mappers y creación/reutilización.
10. Integrar admission guard en ConsultationService.
11. Implementar submit/transiciones y asignación VIN.
12. Ejecutar concurrencia y seguridad.
13. Ejecutar regresión completa relevante.
14. Revisar diff, schema y alcance.
15. Generar `SPRINT-02-RESULT.md` y detenerse.

No posponer pruebas de concurrencia hasta el final si el diseño de locks todavía puede cambiar.

---

## 10. Condiciones de parada

Detener la parte afectada y solicitar decisión si:

- los adapters no permiten identificar de forma verificable qué resultados son positivos;
- no puede calcularse el VIN para criterios NIV/VIN desde datos canónicos;
- el esquema real contradice FK/PK aprobadas;
- `global_configuration` no permite configuración segura y se necesita nueva tabla;
- la integración del guard exige cambiar el orden económico API/débito más allá del bloqueo previo;
- se requiere un séptimo estado;
- se propone permitir submit sin VIN;
- se requiere modificar/eliminar `consultations.provider_service_id`;
- se requiere usar `vehicles` como master;
- una migration portable resulta imposible sin cambiar el diseño;
- una corrección exigiría producción.

No detener todo el Sprint por un fallo de test ordinario: diagnosticar y corregir dentro del alcance.

---

## 11. Documentación durante el Sprint

Actualizar solo cuando corresponda por implementación real:

- `AGENTS.md` (armonización obligatoria);
- documentos maestros si el nombre final de clases/tablas difiere justificadamente sin cambiar reglas;
- `DECISION_LOG.md` solo para decisiones técnicas nuevas relevantes;
- `CHANGE_REQUESTS.md` solo si surge un cambio contractual que requiere aprobación;
- `PROJECT_STATE.md`;
- `docs/sprints/SPRINT-02-RESULT.md`.

No marcar CR como aprobado sin Owner. No reescribir la historia de SPRINT-01.

---

## 12. Formato obligatorio de `SPRINT-02-RESULT.md`

```text
1. Executive Summary
2. Scope Completed
3. Scope Not Completed / Explicit Exclusions
4. Documentation and Evidence Reviewed
5. AGENTS.md Harmonization
6. Architecture Implemented
7. Database Migrations and Final Schema
8. Domain Model and State Machine
9. Consultation Admission Guard
10. Reservations, Locks and Idempotency
11. Case Creation / Reuse / Previous Case
12. VIN Mapper Inventory
13. Plate-Origin VIN Assignment and Reconciliation
14. Temporal Rules and Configuration
15. Audit and Outbox
16. Authorization and Security
17. Files Modified
18. Database Operations Executed
19. Tests Added
20. Tests Executed and Exact Results
21. Concurrency Evidence
22. MySQL/MariaDB Compatibility Evidence
23. Regression Results
24. Failures, Limitations and Known Issues
25. Production Impact
26. Rollback and Recovery
27. Requirement Traceability
28. Acceptance Criteria Verification
29. Recommended Follow-up Sprint Boundaries
30. Sprint Conclusion
```

Incluir comandos ejecutados, conteos exactos y evidencia suficiente, sin exponer secretos.

### Attestation final

```text
Producción: NO MODIFICADA
Cron/cPanel: NO CONFIGURADO
Emails/notificaciones: NO ENTREGADOS
Archivos/evidencias: UI Y STORAGE NO IMPLEMENTADOS
Portales/DataTables: NO IMPLEMENTADOS
Expedientes retroactivos: NO CREADOS
```

Finalizar con:

```text
READY FOR OWNER REVIEW
```

No iniciar el siguiente Sprint.

---

## 13. Definición de terminado

SPRINT-02 termina cuando el núcleo puede demostrarse mediante pruebas locales, sin UI, de esta secuencia:

```text
consulta admitida
→ resultado calificante
→ creación/reutilización única
→ expediente PENDING
→ borrador normalizado
→ asignación VIN excepcional si aplica
→ submit válido
→ revisión/rechazo/reenvío/validación
→ eventos y outbox atómicos
```

y simultáneamente puede demostrarse que:

```text
3 pendientes
→ siguiente consulta bloqueada
→ sin crédito
→ sin proveedor/API
```

con protección real ante concurrencia y sin haber implementado o desplegado componentes fuera de alcance.
