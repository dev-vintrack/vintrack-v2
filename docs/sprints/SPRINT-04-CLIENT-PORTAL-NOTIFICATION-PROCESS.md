# VINTrack — SPRINT-04: Client Portal — Notification Process

**Estado:** PENDING OWNER AUTHORIZATION
**Tipo:** Implementación local + pruebas
**Baseline:** SPRINT-03 APPROVED WITH OBSERVATIONS
**Roadmap canónico:** DEC-036
**Producción:** NOT AUTHORIZED
**Resultado obligatorio:** docs/sprints/SPRINT-04-RESULT.md

---

# 1. OBJETIVO

Implementar localmente la experiencia completa del Portal Cliente para que
el usuario/Policía responsable pueda visualizar y gestionar sus propios
expedientes de notificación vehicular.

SPRINT-04 debe conectar mediante UI las capacidades de dominio ya construidas
en SPRINT-02 y el subsistema seguro de evidencias construido en SPRINT-03.

El Sprint debe permitir al Cliente/Policía:

1. visualizar sus expedientes de notificación;
2. identificar claramente su estado;
3. abrir un expediente propio;
4. visualizar el folio;
5. visualizar el VIN inmutable;
6. capturar/corregir los datos permitidos;
7. utilizar los datos snapshot/default disponibles;
8. cargar evidencia mediante SPRINT-03;
9. listar evidencia;
10. descargar evidencia autorizada;
11. remover lógicamente evidencia cuando esté permitido;
12. enviar definitivamente el expediente;
13. visualizar el expediente en modo sólo lectura cuando corresponda;
14. conocer fecha límite, estado y mensajes funcionales relevantes.

NO debe duplicarse lógica de dominio ya implementada.

---

# 2. FUENTES OBLIGATORIAS

Antes de modificar código, leer completamente y respetar la precedencia
establecida en AGENTS.md:

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

Inspeccionar también la implementación real antes de asumir nombres de clases,
rutas, capabilities, tablas, columnas o servicios.

La implementación real aprobada de SPRINT-02/03 tiene precedencia sobre
ejemplos conceptuales de este rector cuando no exista contradicción
contractual.

---

# 3. NUEVA DECISIÓN DEL PROJECT OWNER — RECOVERED_AT

Registrar una nueva decisión en DECISION_LOG.md:

## Recovered-at Future Date Rule

El Project Owner aprueba:

`recovered_at <= current business datetime`

No se permite registrar una fecha/hora de recuperación futura.

Timezone empresarial:

`America/Mexico_City`

La validación debe existir obligatoriamente server-side.

La UI debe además impedir/prevenir razonablemente fechas futuras como mejora
de experiencia, pero la validación del navegador NO constituye control de
seguridad ni regla de negocio suficiente.

No modificar retrospectivamente decisiones históricas para incorporar esta
regla.

Usar el siguiente DEC disponible; NO asumir número.

---

# 4. PREFLIGHT OBLIGATORIO

Antes de implementar:

1. comprobar estado Git/repository;
2. comprobar PROJECT_STATE;
3. ejecutar suite baseline;
4. registrar tests/assertions/fallos;
5. inspeccionar rutas del Portal Cliente;
6. inspeccionar layout, navegación y convenciones visuales;
7. inspeccionar autenticación;
8. inspeccionar RoleType.is_customer y capabilities reales;
9. inspeccionar NotificationCase y servicios de SPRINT-02;
10. inspeccionar Document Services de SPRINT-03;
11. inspeccionar estado real de authorization;
12. inspeccionar DataTables existentes sólo como referencia visual;
13. inspeccionar normalización de texto existente;
14. inspeccionar validaciones/FormRequests existentes;
15. inspeccionar protección CSRF;
16. inspeccionar manejo de errores/flash messages;
17. inspeccionar paginación disponible;
18. verificar timezone/config efectiva;
19. verificar estados/capabilities del expediente;
20. presentar riesgos o contradicciones materiales antes de implementar.

No modificar producción.

---

# 5. ALCANCE FUNCIONAL — NUEVA OPCIÓN DE MENÚ

Crear en el Portal Cliente una opción claramente identificable como:

`Proceso de Notificaciones`

o conservar el nombre contractual vigente si la documentación aprobada ya
establece otro texto exacto.

Debe ser visible únicamente para usuarios Cliente autorizados conforme al
sistema actual de roles/menús/permisos.

No utilizar únicamente ocultamiento visual como autorización.

La ruta correspondiente debe estar protegida server-side.

---

# 6. LISTADO DE EXPEDIENTES DEL CLIENTE

La vista mostrará exclusivamente expedientes cuya responsabilidad corresponda
al usuario autenticado.

Nunca aceptar un `user_id` del browser para determinar ownership.

Como mínimo debe permitir identificar:

- folio;
- VIN;
- placas;
- marca;
- modelo cuando exista;
- año;
- fecha de consulta/origen cuando corresponda;
- fecha límite para notificar;
- Estado General del Proceso;
- fecha de última actualización;
- acciones disponibles.

Puede incluir otros campos útiles si ya están aprobados y no amplían el
contrato.

No implementar aquí el Historial de Vehículos Consultados.

Ese módulo pertenece a SPRINT-06.

---

# 7. PRESENTACIÓN DE ESTADOS

Usar exclusivamente los estados canónicos existentes:

- PENDING
- SUBMITTED
- UNDER_REVIEW
- REJECTED
- VALIDATED
- CLOSED_NO_FOLLOW_UP

No crear nuevos estados de UI persistentes.

Los textos amigables pueden traducirse al español, por ejemplo:

PENDING
→ PENDIENTE

SUBMITTED
→ ENVIADO / PRESENTADO

UNDER_REVIEW
→ EN VALIDACION

REJECTED
→ RECHAZADO

VALIDATED
→ VALIDADO

CLOSED_NO_FOLLOW_UP
→ CERRADO POR FALTA DE SEGUIMIENTO

Antes de fijar etiquetas exactas, verificar terminología aprobada existente.

No crear un segundo `status_validacion`.

El Estado General continúa siendo la fuente canónica.

---

# 8. PRIORIDAD VISUAL

Los expedientes propios que requieran acción del Policía deben ser visualmente
distinguibles.

Mantener la intención funcional previamente aprobada de dar mayor relevancia
a pendientes propios.

No hardcodear reglas de dominio mediante colores.

Los colores son presentación; el estado/capability real proviene del servidor.

Debe conservarse accesibilidad razonable: el color NO debe ser el único
indicador del estado.

---

# 9. FORMULARIO DEL EXPEDIENTE

El formulario debe manejar los siguientes datos:

0. Folio automático
1. VIN
2. Lugar de recuperación
3. País
4. Estado
5. Alcaldía/Municipio
6. Colonia
7. Código Postal
8. Calle
9. Número
10. Fecha y hora de recuperación
11. Placas
12. Marca
13. Modelo
14. Año
15. Número de motor
16. Color
17. Procedencia
18. Autoridad
19a. IPH
19b. NUC
20. Carpeta de Investigación
21. Resguardo
22. Inventario
23. Notas
24. Archivos adjuntos
25. Estado General del Proceso — sólo visual para Cliente

No agregar campos funcionales nuevos sin aprobación.

---

# 10. FOLIO

El folio:

- es generado automáticamente;
- es único;
- es inmutable;
- es read-only para Cliente;
- no se obtiene desde input confiable del browser;
- debe mostrarse claramente.

La UI nunca genera el folio.

---

# 11. VIN

El VIN:

- proviene de la consulta/expediente;
- forma parte del snapshot;
- es obligatorio;
- es INMUTABLE;
- no puede ser modificado por Policía;
- no puede ser modificado por Analista;
- debe mostrarse read-only.

No confiar en un VIN enviado por un input hidden para modificar identidad.

La autoridad debe provenir del expediente server-side.

---

# 12. SNAPSHOT / DEFAULTS

Cuando el expediente tenga información obtenida de la consulta que lo originó,
utilizarla como default/snapshot conforme al modelo existente.

Como mínimo revisar disponibilidad real de:

- VIN;
- placas;
- marca;
- modelo;
- año;
- procedencia;
- otros datos contractualmente disponibles.

Excepto VIN y folio, los campos permitidos pueden ser corregidos posteriormente
según capability.

No volver a consultar al provider externo para poblar el formulario.

No consumir créditos.

No llamar APIs externas para completar defaults.

---

# 13. CAMPOS OBLIGATORIOS

Son obligatorios:

- VIN;
- Lugar de recuperación;
- País;
- Estado;
- Alcaldía/Municipio;
- Fecha y hora de recuperación;
- Placas;
- Marca;
- Año;
- Procedencia;
- Autoridad;
- Carpeta de Investigación;
- Resguardo;
- IPH/NUC conforme a regla condicional.

Son opcionales:

- Colonia;
- Código Postal;
- Calle;
- Número;
- Modelo;
- Número de motor;
- Color;
- Inventario;
- Notas.

VIN es obligatorio pero read-only.

Folio es automático y no constituye dato capturable.

---

# 14. REGLA IPH / NUC

`iph` y `nuc` son campos independientes.

Regla:

`IPH OR NUC`

Debe existir al menos uno.

Son válidos conceptualmente:

IPH informado + NUC vacío
IPH vacío + NUC informado
IPH informado + NUC informado

No es válido:

IPH vacío + NUC vacío

Implementar server-side.

La UI debe mostrar claramente la condición:

`Capture al menos IPH o NUC.`

No concatenar ambos valores en una sola columna.

---

# 15. NORMALIZACIÓN TEXTUAL

Todos los campos textuales capturables sujetos a la regla aprobada deben
normalizarse server-side:

- MAYÚSCULAS;
- SIN ACENTOS;
- SIN DIÉRESIS.

Ejemplos:

México
→ MEXICO

Núñez
→ NUNEZ

José María
→ JOSE MARIA

La UI puede realizar normalización inmediata como UX.

La autoridad final es server-side.

NO aplicar esta transformación automáticamente a:

- filename original;
- IDs;
- timestamps;
- datos técnicos no textuales;
- storage keys;
- hashes;
- campos donde la especificación vigente explícitamente lo prohíba.

Verificar que la normalización no destruya caracteres funcionales permitidos
en placas, IPH, NUC o identificadores.

No diseñar una transliteración arbitraria fuera de la regla aprobada.

---

# 16. FECHA Y HORA DE RECUPERACIÓN

`recovered_at`:

- obligatorio;
- editable cuando capability lo permita;
- interpretado en `America/Mexico_City`;
- NO puede estar en el futuro.

Regla server-side:

`recovered_at <= now(America/Mexico_City)`

La UI debe configurar razonablemente el máximo seleccionable al momento
actual.

No confiar en ese máximo del navegador.

Probar boundary conditions.

---

# 17. GUARDADO PARCIAL

Distinguir claramente:

`SAVE/DRAFT`

de:

`SUBMIT`

Mientras el expediente permanezca editable, el Policía puede guardar
información parcial sin satisfacer todavía todos los requisitos finales,
SI Y SOLO SI el contrato existente de SPRINT-02 permite esta semántica.

Antes de implementar, inspeccionar los commands/services existentes.

No debilitar `SubmitCase`.

La validación completa de obligatorios debe ocurrir antes de SUBMIT.

Si el modelo existente ya exige reglas específicas para persistencia parcial,
reutilizarlas.

---

# 18. SUBMIT DEFINITIVO

El botón de envío debe utilizar el servicio existente de SPRINT-02.

No reimplementar la transición en Controller.

Antes del submit debe validarse:

- ownership;
- capability;
- campos obligatorios;
- IPH/NUC;
- recovered_at no futuro;
- invariantes existentes;
- condiciones documentales que el dominio ya exija.

Si SubmitCase ya realiza estas verificaciones, la UI debe delegar en él.

No hacer:

`status = SUBMITTED`

directamente desde Controller/ORM.

---

# 19. CONFIRMACIÓN DE ENVÍO

El envío definitivo es una operación de negocio relevante.

La UI debe informar claramente que después del envío el expediente dejará
de ser editable por el Cliente hasta que una transición posterior vuelva
a habilitarlo, por ejemplo REJECTED.

Incorporar confirmación explícita antes del submit.

Evitar doble submit desde UI.

La protección real contra doble submit debe seguir siendo server-side.

---

# 20. MATRIZ DE MUTABILIDAD DEL CLIENTE

La UI debe reflejar las capabilities server-side.

Baseline esperado:

PENDING
- editar: SÍ
- documentos: SÍ
- submit: SÍ

SUBMITTED
- editar: NO
- documentos: NO
- submit: NO

UNDER_REVIEW
- editar: NO
- documentos: NO
- submit: NO

REJECTED
- editar: SÍ
- documentos: SÍ
- resubmit: según máquina de estados existente

VALIDATED
- editar: NO
- documentos: NO
- submit: NO

CLOSED_NO_FOLLOW_UP
- editar: NO
- documentos: NO
- submit: NO

Esta tabla NO debe convertirse en una segunda autoridad.

Antes de implementar, verificar capabilities reales de SPRINT-02/03.

Si existe diferencia contractual, usar la fuente aprobada y documentarla.

---

# 21. RECHAZADO

Cuando un Analista haya colocado el expediente en REJECTED:

- el mismo expediente permanece;
- el mismo folio permanece;
- no crear expediente nuevo;
- Cliente puede corregir los campos permitidos;
- VIN permanece inmutable;
- puede agregar/remover evidencia conforme a capability;
- puede volver a enviar cuando satisfaga requisitos.

No implementar todavía la pantalla mediante la cual el Analista rechaza:
eso pertenece a SPRINT-05.

SPRINT-04 únicamente debe consumir correctamente el estado REJECTED.

---

# 22. ARCHIVOS ADJUNTOS

Integrar exclusivamente los servicios/endpoints seguros de SPRINT-03.

No crear un segundo mecanismo de uploads.

La UI debe permitir, cuando capability lo autorice:

- seleccionar archivo;
- cargar;
- listar;
- descargar;
- remover lógicamente.

Mostrar:

- nombre;
- tipo;
- tamaño;
- fecha;
- acción permitida.

Reglas:

- PDF/JPG/JPEG/PNG;
- máximo 3 MiB;
- máximo 8 activos.

La validación client-side es sólo UX.

La autoridad sigue en SPRINT-03.

No mostrar storage paths.

No crear URLs públicas.

---

# 23. EXPERIENCIA DE UPLOAD

La UI debe informar:

`Formatos permitidos: PDF, JPG, JPEG y PNG.`
`Tamaño máximo: 3 MB por archivo.`
`Máximo: 8 archivos activos por expediente.`

Mostrar cantidad actual:

`5 de 8 archivos`

Cuando llegue a 8, deshabilitar visualmente nueva carga.

La protección real debe permanecer server-side.

Manejar errores de forma comprensible:

- formato inválido;
- tamaño excedido;
- máximo alcanzado;
- expediente no editable;
- acceso no autorizado;
- retry/idempotencia;
- error temporal.

No revelar excepciones internas.

---

# 24. FECHA LÍMITE

Mostrar claramente `submission_due_at` o equivalente real.

La fecha límite se calcula según regla ya implementada de 3 días calendario
hasta 23:59:59.

NO recalcularla en JavaScript como autoridad.

Utilizar valor persistido/dominio.

Mostrar fecha/hora en timezone empresarial.

No modificar la regla temporal en este Sprint.

---

# 25. EXPEDIENTES VENCIDOS

La expiración del deadline NO debe impedir la captura si el dominio vigente
permite todavía completar el expediente.

Recordar la regla contractual:

el incumplimiento bloquea nuevas consultas según las reglas del dominio,
pero no debe impedir al Policía resolver el expediente pendiente.

No implementar cierre automático aquí.

CLOSED_NO_FOLLOW_UP continúa perteneciendo al mecanismo automático futuro.

---

# 26. BLOQUEO DE CONSULTAS

SPRINT-04 NO debe reimplementar el límite de 3.

El admission gate de SPRINT-02 continúa siendo la autoridad.

La UI puede informar al usuario que tiene pendientes que afectan su capacidad
de realizar nuevas consultas, pero no utilizar JavaScript/UI como bloqueo
real.

No modificar wallet/API/admission salvo corrección imprescindible y
contractualmente justificada.

---

# 27. MENSAJES AL CLIENTE

Implementar mensajes claros para:

- expediente pendiente;
- fecha límite;
- expediente enviado;
- en revisión;
- rechazado;
- validado;
- cerrado por falta de seguimiento;
- expediente no editable;
- errores de validación;
- evidencia inválida;
- máximo de documentos;
- acceso denegado.

No implementar todavía el Notification Center persistente de SPRINT-07.

Estos son mensajes contextuales de UI.

---

# 28. AUTORIZACIÓN / IDOR

Todos los endpoints nuevos deben validar server-side.

Probar como mínimo:

Cliente A:
- lista únicamente expedientes propios;
- abre expediente propio;
- guarda propio;
- submit propio;
- opera documentos propios.

Cliente A NO puede:
- abrir expediente B;
- editar B;
- submit B;
- acceder documentos B;
- modificar IDs para acceder B.

No confiar en:

- hidden user_id;
- hidden owner_id;
- hidden VIN;
- query string;
- botones ocultos;
- JavaScript.

El servidor deriva ownership del usuario autenticado.

---

# 29. MASS ASSIGNMENT

Usar DTO/FormRequest/command mapping explícito.

No permitir que el browser modifique mediante payload:

- id;
- folio;
- user_id;
- owner/responsible user;
- consultation_id;
- VIN;
- status;
- opened_at;
- submission_due_at;
- closed_at;
- validated_at;
- lock_version;
- storage metadata;
- audit fields;
- cualquier campo server-owned.

Probar payload adversarial.

---

# 30. CSRF Y HTTP

Mantener protección CSRF existente.

Usar métodos HTTP semánticamente adecuados.

No crear endpoints GET que muten estado.

No desactivar CSRF globalmente para simplificar implementación.

---

# 31. CONCURRENCIA / DOBLE SUBMIT

SPRINT-02 dejó pendiente una carrera contractual real de doble submit.

SPRINT-04 es el Sprint natural para resolver/probar ese Production Gate.

Ejecutar una prueba real con procesos/conexiones separadas:

mismo expediente PENDING
+ dos SUBMIT simultáneos

Resultado esperado:

- una única transición efectiva;
- ningún evento contradictorio;
- ningún outbox duplicado;
- estado final consistente;
- operación repetida obtiene resultado estable o error de negocio estable,
  conforme al diseño existente.

No declarar concurrencia real si se prueba secuencialmente.

Registrar el resultado respecto de OBS-02-02.

---

# 32. DOBLE ASIGNACIÓN VIN

Si el flujo del Portal Cliente expone o ejecuta alguna capacidad relacionada
con asignación de VIN pendiente, probar la carrera correspondiente.

Si SPRINT-04 no ejecuta esa operación, documentar:

`NOT APPLICABLE TO SPRINT-04`

y conservarla como Production Gate.

No ampliar alcance artificialmente para cerrarla.

---

# 33. UX Y RESPONSIVE

Respetar el diseño visual existente de VINTrack.

No rediseñar globalmente el Portal Cliente.

La vista debe funcionar razonablemente en:

- desktop;
- tablet;
- viewport móvil compatible con layout actual.

Campos relacionados pueden agruparse:

Identificación
Recuperación
Ubicación
Vehículo
Autoridad / Investigación
Resguardo
Evidencias
Notas

No cambiar arquitectura CSS global innecesariamente.

---

# 34. ACCESIBILIDAD BÁSICA

Incluir:

- labels asociados;
- mensajes de error vinculados;
- required indicators;
- estado no indicado sólo por color;
- botones con texto/aria-label cuando corresponda;
- navegación razonable por teclado;
- foco visible según estilos existentes.

No convertir SPRINT-04 en proyecto integral WCAG, pero evitar regresiones
evidentes.

---

# 35. PERFORMANCE / LISTADO

El listado de Proceso de Notificaciones pertenece a un conjunto acotado por
usuario.

No cargar relaciones innecesarias N+1.

Usar paginación server-side Laravel si el volumen lo justifica.

NO implementar todavía el DataTable global/histórico de SPRINT-06.

No duplicar la arquitectura server-side DataTables futura por anticipación.

---

# 36. AUDITORÍA

Las mutaciones deben continuar usando auditoría de dominio existente.

Guardar/corregir datos:
→ evento/auditoría conforme a servicios existentes.

Submit:
→ auditoría existente.

Documentos:
→ auditoría SPRINT-03.

No crear logs paralelos sólo para UI.

No guardar payload completo del formulario en logs.

---

# 37. OUTBOX

No implementar delivery.

Los eventos/outbox generados por operaciones de dominio deben continuar
persistiéndose conforme a SPRINT-02.

SPRINT-04 puede provocar legítimamente nuevos eventos mediante operaciones
reales.

No enviar email.

No crear worker.

No configurar Cron.

SPRINT-07 entregará esas notificaciones.

---

# 38. DATOS Y PRIVACIDAD EN LA UI

No exponer:

- IDs internos innecesarios;
- storage keys;
- paths;
- hashes;
- provider raw payload;
- costos;
- wallet internals;
- audit internals;
- información de otros clientes.

Mostrar únicamente información funcional necesaria.

---

# 39. TESTS OBLIGATORIOS — LISTADO

Probar:

- Cliente ve sólo propios;
- otro Cliente no aparece;
- estados correctos;
- fecha límite correcta;
- acciones dependen de capability;
- paginación si se implementa;
- usuario administrativo no obtiene accidentalmente portal Cliente salvo
  diseño vigente.

---

# 40. TESTS OBLIGATORIOS — FORMULARIO

Probar:

- folio read-only/server-owned;
- VIN read-only/server-owned;
- defaults correctos;
- campos opcionales;
- campos obligatorios;
- IPH solo;
- NUC solo;
- ambos;
- ninguno → error;
- normalización;
- recovered_at pasado → válido;
- recovered_at ahora → válido;
- recovered_at futuro → inválido;
- timezone boundary;
- payload intenta cambiar VIN → ignorado/rechazado;
- payload intenta cambiar owner → rechazado;
- payload intenta cambiar status → rechazado;
- payload intenta cambiar deadline → rechazado.

---

# 41. TESTS OBLIGATORIOS — ESTADOS

Para cada estado:

PENDING
SUBMITTED
UNDER_REVIEW
REJECTED
VALIDATED
CLOSED_NO_FOLLOW_UP

probar:

- vista;
- editabilidad;
- botones;
- server-side capability;
- documentos;
- submit/resubmit cuando corresponda.

No probar sólo visibilidad del botón.

Intentar la operación HTTP aunque el botón esté oculto.

---

# 42. TESTS OBLIGATORIOS — EVIDENCIAS

Desde la integración UI:

- upload válido;
- upload inválido;
- >3 MiB;
- 8 activos;
- listado;
- descarga;
- remove;
- documento ajeno;
- documento removido;
- mensaje de error seguro.

No duplicar innecesariamente toda la suite SPRINT-03, pero conservar regresión.

---

# 43. TESTS OBLIGATORIOS — IDOR

Crear al menos Cliente A y Cliente B.

Probar manipulación de:

- case id;
- document id;
- route model binding;
- form action;
- submit endpoint;
- update endpoint;
- document endpoint.

Resultado:

404/403 conforme a convención actual, nunca información ajena.

---

# 44. TESTS OBLIGATORIOS — DOBLE SUBMIT REAL

Usar MySQL local y procesos/conexiones separados.

Registrar:

- estado inicial;
- procesos;
- resultados;
- estado final;
- eventos;
- outbox;
- errores/retries.

Debe cerrar, si técnicamente procede, la parte correspondiente de
OBS-02-02.

---

# 45. REGRESIÓN

Ejecutar suite completa.

Debe incluir y preservar:

- 82 tests / 295 assertions baseline de SPRINT-03, o el baseline exacto
  observado al iniciar;
- admission gate;
- límite 3;
- expediente;
- 90 días;
- deadline;
- estados;
- storage/evidence;
- carrera 7+2;
- IDOR;
- idempotencia;
- auditoría;
- outbox;
- wallet/provider relevante.

No aceptar tests eliminados/deshabilitados para obtener verde.

---

# 46. FUERA DE ALCANCE

NO implementar:

- Portal Administrativo de revisión;
- rechazo desde UI administrativa;
- validación desde UI administrativa;
- Historial de Vehículos Consultados Cliente;
- Historial Global Administrativo;
- filtro global por Cliente;
- DataTables de SPRINT-06;
- Notification Center persistente;
- email delivery;
- outbox worker;
- Cron/cPanel;
- Scheduler productivo;
- auto-close operativo programado;
- producción;
- deployment;
- MariaDB productivo;
- malware scanner;
- purga física;
- backfill;
- expedientes retroactivos;
- merge automático de VIN;
- cambios generales a wallet/provider;
- rediseño global del portal.

---

# 47. MIGRATIONS

Preferencia: CERO migrations nuevas.

SPRINT-02/03 deberían proporcionar el modelo necesario.

Si aparece una necesidad objetiva:

1. demostrarla;
2. verificar contrato;
3. determinar si requiere Change Request;
4. no crear migration hasta resolver contradicción contractual cuando exista.

No usar migration para acomodar una preferencia de UI.

No producción.

---

# 48. ARCHIVOS EXISTENTES

Preferir reutilizar:

- Application Services;
- repositories;
- policies/capabilities;
- DTOs;
- document services;
- layout;
- components;
- validation patterns.

No duplicar `NotificationCaseService` sólo para Portal Cliente.

No crear una segunda implementación de estados.

No crear una segunda implementación de documentos.

---

# 49. DOCUMENTACIÓN

Durante SPRINT-04:

- registrar la decisión `recovered_at` con el siguiente DEC disponible;
- actualizar PROJECT_STATE;
- actualizar DECISION_LOG sólo cuando corresponda;
- CHANGE_REQUESTS únicamente ante cambio contractual aprobado;
- generar SPRINT-04-RESULT.md.

No modificar resultados históricos para ocultar discrepancias.

---

# 50. ORDEN RECOMENDADO

1. Read governance.
2. Baseline.
3. Preflight.
4. Registrar decisión recovered_at.
5. Tests de aplicación/authorization primero.
6. Query/listado de expedientes.
7. Form DTO/validation.
8. Save/edit.
9. Normalización.
10. recovered_at rule.
11. Integración Evidence SPRINT-03.
12. Submit/resubmit.
13. Estado read-only.
14. Mensajes/UX.
15. IDOR adversarial.
16. Mass-assignment adversarial.
17. Double-submit real.
18. Regression.
19. Pint/static checks.
20. Diff review.
21. Documentation.
22. SPRINT-04-RESULT.md.
23. STOP.

---

# 51. CONDICIONES DE PARADA

Detener la parte afectada y consultar al Project Owner si:

- la máquina de estados real contradice el contrato;
- PENDING/REJECTED no permiten la mutabilidad esperada;
- SubmitCase no admite el flujo aprobado;
- guardar parcial exige cambio contractual;
- el schema carece de campos aprobados;
- se requiere cambiar VIN;
- se requiere crear nuevo status;
- se requiere cambiar ownership;
- se requiere cambiar 3/30/90;
- se requiere cambiar máximo de documentos;
- se requiere cambiar reglas de evidencia;
- se requiere una migration contractual no prevista;
- se requiere producción;
- aparece conflicto documental no resoluble por precedencia.

No detener todo el Sprint por un error ordinario de implementación.

---

# 52. CRITERIOS DE ACEPTACIÓN

## Governance
- [ ] DEC-036 sigue siendo roadmap canónico.
- [ ] DEC-037 sigue siendo cierre SPRINT-03.
- [ ] recovered_at rule registrada con nuevo DEC.
- [ ] PROJECT_STATE refleja SPRINT-04 durante ejecución.
- [ ] producción permanece no autorizada.

## Listing
- [ ] Cliente ve únicamente expedientes propios.
- [ ] Folio/VIN/status/deadline visibles.
- [ ] Acciones derivadas de capability.
- [ ] Sin Historial de Vehículos Consultados.

## Form
- [ ] Folio inmutable.
- [ ] VIN inmutable.
- [ ] Snapshot/defaults.
- [ ] Campos contractuales.
- [ ] Obligatorios correctos.
- [ ] Opcionales correctos.
- [ ] IPH OR NUC.
- [ ] normalización server-side.
- [ ] recovered_at no futuro.

## Evidence
- [ ] SPRINT-03 reutilizado.
- [ ] Upload/list/download/remove.
- [ ] 3 MiB.
- [ ] 8 activos.
- [ ] Sin storage público.

## States
- [ ] PENDING editable.
- [ ] REJECTED editable.
- [ ] SUBMITTED read-only.
- [ ] UNDER_REVIEW read-only.
- [ ] VALIDATED read-only.
- [ ] CLOSED_NO_FOLLOW_UP read-only.
- [ ] server-side coincide con UI.

## Submit
- [ ] Validación completa.
- [ ] Confirmación.
- [ ] servicio de dominio existente.
- [ ] sin asignación directa de status.
- [ ] doble submit real probado.

## Security
- [ ] IDOR.
- [ ] mass assignment.
- [ ] CSRF.
- [ ] ownership server-side.
- [ ] errores sin información interna.

## Quality
- [ ] suite completa verde.
- [ ] baseline preservado.
- [ ] Pint/static checks.
- [ ] ninguna migration innecesaria.
- [ ] ninguna producción.

---

# 53. SPRINT-04-RESULT.md

Generar obligatoriamente:

`docs/sprints/SPRINT-04-RESULT.md`

Estructura mínima:

1. Executive Summary
2. Scope Completed
3. Explicit Exclusions
4. Governance / Decisions
5. Baseline
6. Preflight Findings
7. Client Portal Architecture
8. Routes / Authorization
9. Notification Process Listing
10. Form Implementation
11. Snapshot / Defaults
12. Validation Rules
13. IPH / NUC
14. Text Normalization
15. recovered_at Rule
16. Save/Edit
17. Evidence Integration
18. Submit / Resubmit
19. State / Capability Matrix
20. Deadline Presentation
21. UX / Accessibility
22. IDOR Protection
23. Mass Assignment Protection
24. CSRF / HTTP
25. Audit / Outbox Integration
26. Tests Added
27. Tests Executed
28. Real Double-submit Concurrency
29. Regression
30. Files Modified
31. Database Operations
32. Migrations
33. Limitations / Risks
34. Production Gates
35. Requirement Traceability
36. Acceptance Criteria
37. Recommended Follow-up
38. Sprint Conclusion

Incluir cifras exactas.

No exponer secretos.

---

# 54. ATTESTATION FINAL

El resultado debe declarar explícitamente:

Portal Cliente — Proceso de Notificaciones:
IMPLEMENTADO LOCALMENTE

Portal Administrativo:
NO IMPLEMENTADO EN ESTE SPRINT

Historial de Vehículos Consultados:
NO IMPLEMENTADO EN ESTE SPRINT

DataTables SPRINT-06:
NO IMPLEMENTADOS

Notification Delivery:
NO IMPLEMENTADO

Email:
NO ENVIADO

Cron/cPanel:
NO CONFIGURADO

Producción:
NO MODIFICADA

SPRINT-05:
NO INICIADO

Finalizar exactamente con:

READY FOR OWNER REVIEW

STOP.