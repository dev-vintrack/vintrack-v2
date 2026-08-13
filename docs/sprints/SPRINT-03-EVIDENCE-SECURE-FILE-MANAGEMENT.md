# VINTrack — SPRINT-03: Evidence & Secure File Management

**Estado:** PENDIENTE DE AUTORIZACIÓN DE EJECUCIÓN DEL PROJECT OWNER
**Tipo:** Implementación local + pruebas
**Baseline:** SPRINT-02 `APPROVED WITH OBSERVATIONS`
**Entorno autorizado:** repositorio y base de datos local de desarrollo
**Producción:** FUERA DE ALCANCE
**Resultado obligatorio:** `docs/sprints/SPRINT-03-RESULT.md`

---

## 1. Objetivo

Implementar y probar localmente el subsistema seguro de evidencia documental
de los expedientes de notificación vehicular, utilizando el modelo de dominio,
persistencia, ownership, autorización, auditoría y concurrencia establecidos
en SPRINT-01 y materializados en SPRINT-02.

Al finalizar SPRINT-03, VINTrack debe disponer de:

- almacenamiento privado de evidencia;
- upload seguro de PDF/JPG/JPEG/PNG;
- límite configurable de 3 MB por archivo;
- límite configurable de 8 documentos activos por expediente;
- validación server-side de tipo, tamaño y pertenencia;
- generación segura de storage keys;
- hash SHA-256;
- metadata persistente;
- descarga privada y autorizada;
- eliminación lógica controlada;
- autorización server-side contra IDOR;
- protección concurrente del límite de documentos;
- auditoría append-only;
- integración transaccional con el núcleo existente donde corresponda;
- pruebas unitarias, integración, autorización, seguridad y concurrencia.

Este Sprint NO construye todavía los formularios finales del Portal Cliente
ni las pantallas administrativas.

---

## 2. Fuentes obligatorias y precedencia

Antes de modificar código, leer completamente:

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
docs/sprints/SPRINT-02-CORE-DOMAIN-PERSISTENCE.md
docs/sprints/SPRINT-02-RESULT.md

La fuente contractual del subsistema de evidencia es el diseño aprobado en
SPRINT-01, las decisiones posteriores aprobadas y la implementación real
resultante de SPRINT-02.

No implementar desde borradores históricos cuando contradigan documentos
posteriores aprobados.

Toda afirmación sobre implementación existente debe citar evidencia concreta
de archivo, clase, método, migration, tabla, configuración o test.

Si existe una contradicción material no resoluble mediante la precedencia
documental vigente, detener únicamente la parte afectada y solicitar decisión.

---

## 3. Preflight obligatorio

Antes de implementar:

1. Verificar estado actual del repositorio.
2. Verificar que SPRINT-02 permanece íntegro.
3. Ejecutar la suite existente y registrar baseline exacto.
4. Inspeccionar la implementación real de:
   - NotificationCase;
   - NotificationCaseDocument;
   - NotificationCaseEvent;
   - NotificationOutbox;
   - authorization/policies/capabilities;
   - configuración tipada;
   - repositorios;
   - migrations de SPRINT-02.
5. Inspeccionar `config/filesystems.php` y configuración efectiva de storage.
6. Confirmar ubicación física del disk privado.
7. Confirmar que evidencia NO será almacenada en un disk público.
8. Inspeccionar convenciones actuales de controllers/routes/application
   services antes de agregar endpoints.
9. Verificar `max_files` y `max_file_bytes` desde configuración central.
10. Registrar riesgos o contradicciones antes de modificar código.

No asumir que los nombres propuestos en este documento existen exactamente:
adaptarlos a la arquitectura real sin cambiar las reglas contractuales.

---

## 4. Alcance autorizado

### 4.1 Storage privado

Implementar almacenamiento exclusivamente privado para evidencia documental.

Los archivos NO deben:

- almacenarse dentro de `public/`;
- almacenarse en un disk público;
- obtener una URL pública permanente;
- ser accesibles conociendo únicamente su storage path;
- depender de obscuridad del nombre como mecanismo de autorización.

Usar el mecanismo privado existente de Laravel cuando sea compatible con el
hosting objetivo.

El storage key físico debe ser generado por el sistema y no derivarse
directamente del nombre proporcionado por el usuario.

Ejemplo conceptual:

notification-cases/{case-id}/{generated-file-key}

El ejemplo NO constituye obligación de usar exactamente ese path.

Nunca permitir segmentos arbitrarios proporcionados por usuario.

---

### 4.2 Tipos de archivo

Tipos permitidos:

- PDF;
- JPG;
- JPEG;
- PNG.

Validar server-side.

No confiar únicamente en:

- extensión;
- `Content-Type` enviado por navegador;
- nombre original.

Usar mecanismos de detección MIME disponibles y confiables en el stack actual.

Debe existir correspondencia permitida entre MIME detectado y extensión
aceptada.

Archivos ambiguos, incompatibles o no reconocidos deben rechazarse.

No intentar reparar ni transformar automáticamente archivos inválidos.

---

### 4.3 Tamaño máximo

Usar configuración central existente:

max_file_bytes = 3145728

equivalente a 3 MiB.

La validación debe ejecutarse server-side antes de aceptar definitivamente
la evidencia.

No dispersar `3145728` como número mágico fuera de la configuración/política
central.

---

### 4.4 Máximo de documentos

Usar:

max_files = 8

El límite corresponde a documentos ACTIVOS del expediente.

Documentos removidos lógicamente no cuentan para el límite operativo, salvo
que el diseño contractual vigente indique lo contrario.

El límite debe protegerse ante concurrencia.

No implementar:

count()
→ comprobar < 8
→ insertar

sin serialización adecuada.

La operación debe adquirir el lock correspondiente del expediente o utilizar
el mecanismo equivalente ya aprobado antes de comprobar el límite e insertar
metadata.

Caso contractual de concurrencia:

7 documentos activos
+ dos uploads simultáneos
→ exactamente uno puede completar
→ máximo final = 8
→ el segundo recibe resultado de negocio estable
→ nunca 9.

---

## 5. Metadata documental

Usar `notification_case_documents` creada en SPRINT-02.

Antes de modificarla, inspeccionar su schema real.

No crear una tabla paralela.

La metadata debe conservar, según schema aprobado/real:

- notification_case_id;
- original_name;
- storage_disk;
- storage_key;
- mime_type;
- extension;
- size_bytes;
- sha256;
- uploaded_by_user_id;
- timestamps;
- removed_at;
- removed_by_user_id;

y cualquier otro campo aprobado realmente existente.

No almacenar bytes del archivo en la base de datos.

No almacenar paths absolutos dependientes del servidor cuando pueda utilizarse
disk + storage key portable.

---

## 6. Hash e integridad

Calcular SHA-256 del contenido almacenado/aceptado.

Persistir el hash en metadata.

El hash sirve para integridad/trazabilidad.

No asumir automáticamente que hashes iguales significan que los documentos
deben deduplicarse físicamente.

No reutilizar un archivo entre expedientes únicamente porque su SHA-256
coincida.

Si se detecta una oportunidad de deduplicación global, documentarla como
posible optimización futura; NO implementarla sin aprobación.

---

## 7. UploadCaseDocument

Implementar un application command/use case equivalente a:

UploadCaseDocument

El nombre final debe respetar las convenciones reales.

Responsabilidades:

1. identificar actor autenticado;
2. resolver expediente;
3. autorizar acción sobre el expediente;
4. comprobar estado compatible con carga;
5. validar archivo;
6. adquirir lock requerido;
7. contar documentos activos;
8. aplicar máximo configurable;
9. generar storage key segura;
10. persistir archivo privado;
11. calcular/verificar metadata;
12. persistir `notification_case_documents`;
13. emitir auditoría;
14. mantener consistencia ante fallo parcial.

No colocar reglas de negocio críticas exclusivamente en Controller,
FormRequest, Blade o JavaScript.

---

## 8. Consistencia DB + filesystem

El filesystem no participa en la transacción SQL.

Por tanto, diseñar explícitamente compensación ante fallos.

Debe evitarse razonablemente:

A)
archivo almacenado
+
metadata inexistente permanentemente

y:

B)
metadata activa
+
archivo inexistente

Definir orden de operaciones y compensación.

Ejemplo permitido conceptualmente:

validate
→ authorize
→ lock/check limit
→ store private file
→ DB transaction metadata + audit
→ si DB falla, eliminar archivo recién almacenado

o estrategia equivalente más segura compatible con Laravel.

La solución final debe documentarse y probarse mediante fault injection
razonable.

No crear infraestructura distribuida innecesaria para resolver este problema.

---

## 9. DownloadCaseDocument

Implementar descarga privada autorizada.

La descarga debe:

1. autenticar;
2. resolver expediente autorizado;
3. resolver documento dentro de ese expediente;
4. comprobar que no está removido;
5. comprobar autorización server-side;
6. comprobar existencia física;
7. responder mediante stream/download privado.

Nunca autorizar solamente mediante:

document_id

sin comprobar pertenencia al expediente autorizado.

Manipular:

case_id
document_id
folio
storage_key

no debe permitir IDOR.

No revelar storage path físico.

No generar URL pública permanente.

El nombre descargado puede usar `original_name` únicamente después de
sanitización adecuada para headers.

---

## 10. ListCaseDocuments

Implementar application query/use case equivalente a:

ListCaseDocuments

Debe respetar ownership/permisos del expediente.

Por defecto retornar únicamente documentos activos.

La metadata expuesta al cliente debe ser mínima:

- identificador;
- nombre original seguro;
- MIME/tipo;
- tamaño;
- fecha;
- información funcional necesaria.

No exponer:

- path absoluto;
- storage credentials;
- información interna innecesaria;
- metadata de auditoría sensible.

---

## 11. RemoveCaseDocument

Implementar eliminación lógica equivalente a:

RemoveCaseDocument

No realizar destrucción física definitiva como comportamiento normal del
usuario.

La operación debe:

- autorizar;
- bloquear cuando sea necesario;
- comprobar pertenencia;
- comprobar que el documento está activo;
- establecer `removed_at`;
- establecer `removed_by_user_id`;
- emitir evento de auditoría;
- ser idempotente o producir resultado estable ante retry.

No implementar todavía purga física.

La política de retención contractual permanece vigente y separada.

---

## 12. Reglas de autorización

La autorización debe ejecutarse server-side.

Como mínimo demostrar:

### Owner/Cliente

Puede operar documentos únicamente sobre expedientes propios y únicamente
cuando el estado/capability contractual permita la operación.

### Otro Cliente

No puede:

- listar;
- descargar;
- subir;
- remover

documentos de otro expediente.

### Analista

Aplicar exclusivamente las capabilities aprobadas existentes.

No asumir que "admin" implica automáticamente acceso ilimitado si el modelo
de permisos vigente exige capability explícita.

### Manipulación directa

Cambiar IDs, folios, VIN, storage keys o nombres de archivo no puede evadir
authorization.

---

## 13. Estados y mutabilidad

Inspeccionar la matriz contractual vigente antes de codificar.

No inventar una nueva política de edición documental.

Las operaciones de upload/remove deben respetar los estados en los que el
expediente/documentación sean editables conforme a SPRINT-01 y las decisiones
aprobadas.

Si la documentación aprobada no determina inequívocamente una operación para
un estado específico, detener únicamente esa regla y solicitar decisión.

No agregar estados nuevos.

No modificar la máquina de estados de SPRINT-02.

---

## 14. Auditoría

Toda mutación documental debe generar evento append-only.

Como mínimo:

- documento agregado;
- documento removido.

Registrar de acuerdo con infraestructura existente:

- case;
- actor;
- rol efectivo;
- timestamp;
- IP;
- user agent;
- correlation/request/idempotency key cuando corresponda;
- identificador lógico del documento;
- metadata mínima necesaria.

No guardar en auditoría:

- contenido del archivo;
- secretos;
- paths absolutos;
- payload HTTP completo;
- información innecesaria.

Las descargas pueden auditarse si la arquitectura contractual vigente lo
requiere o si existe precedente explícito.

No inventar auditoría masiva de lecturas sin evidencia contractual.

---

## 15. Seguridad del contenido

Implementar defensas razonables dentro de las capacidades actuales del stack:

- allowlist MIME;
- allowlist extensión;
- tamaño máximo;
- nombre físico aleatorio;
- almacenamiento privado;
- autorización server-side;
- headers seguros de descarga;
- no ejecución;
- no path traversal.

Inspeccionar si existe antivirus/malware scanner real disponible.

Si NO existe:

- NO instalar servicios externos;
- NO fingir malware scanning;
- documentar `NOT AVAILABLE` como riesgo residual;
- diseñar el componente para permitir incorporación futura si es razonable.

No agregar dependencia SaaS sin aprobación.

---

## 16. Idempotencia

Uploads y removals deben considerar retries.

Para upload, evaluar mecanismo idempotente compatible con arquitectura actual,
por ejemplo request/idempotency key.

Un retry de la misma operación no debe crear múltiples documentos activos
accidentalmente.

No deduplicar operaciones distintas únicamente porque:

- filename coincide;
- hash coincide.

Distinguir:

same request retry

de:

intentional second upload.

Para remove, un retry no debe generar múltiples efectos contradictorios.

Eventos deben conservar `event_key`/deduplicación según infraestructura
existente.

---

## 17. Endpoints mínimos

Crear únicamente endpoints necesarios para probar/consumir el subsistema.

Ejemplos conceptuales:

POST   /cases/{case}/documents
GET    /cases/{case}/documents
GET    /cases/{case}/documents/{document}
DELETE /cases/{case}/documents/{document}

Los paths definitivos deben respetar routing existente.

No crear páginas finales.

No crear rutas temporales inseguras.

No utilizar IDs enviados por cliente sin autorización de recurso.

---

## 18. Fuera de alcance

NO implementar en SPRINT-03:

- formulario final del Portal Cliente;
- Proceso de Notificaciones visual;
- Portal Administrativo;
- DataTables;
- historial Cliente;
- historial global Admin;
- revisión/validación visual;
- centro de notificaciones;
- email delivery;
- worker del outbox;
- Scheduler;
- cPanel Cron;
- endpoint HMAC;
- cierre automático programado;
- purga física;
- retención automática;
- malware scanning externo;
- almacenamiento cloud/S3 salvo que ya exista como requisito aprobado;
- merge automático de conciliaciones VIN;
- backfill;
- expedientes retroactivos;
- modificaciones generales de wallet;
- producción;
- SQL productivo;
- modificaciones de datos productivos.

---

## 19. Migrations

Preferencia: NO crear nuevas migrations si el schema aprobado de SPRINT-02
es suficiente.

Si durante inspección se descubre que una migration es imprescindible:

1. demostrar por qué el schema actual no satisface una regla aprobada;
2. verificar que no se trata de una preferencia técnica;
3. mantenerla aditiva y portable;
4. MySQL 8.4.3 / MariaDB 10.6.27;
5. no usar features prohibidas;
6. probar rollback en BD desechable;
7. documentarla completamente.

Si la migration implica cambiar el contrato de SPRINT-01, detener y solicitar
Change Request.

No ejecutar migrations en producción.

---

## 20. Pruebas obligatorias

### 20.1 Upload

Probar:

- PDF válido;
- JPG válido;
- JPEG válido;
- PNG válido;
- extensión inválida;
- MIME inválido;
- extensión permitida con contenido incompatible;
- archivo vacío si corresponde rechazarlo;
- exactamente límite permitido;
- un byte sobre límite;
- filename extraño;
- caracteres Unicode;
- path traversal en filename;
- nombres duplicados;
- storage key única;
- SHA-256 correcto.

### 20.2 Máximo de documentos

- 0 → upload permitido;
- 7 → upload permitido;
- 8 → bloqueado;
- removido no cuenta como activo conforme a política;
- 7 + dos uploads concurrentes → exactamente 8.

La carrera debe utilizar conexiones/procesos separados cuando la
infraestructura lo permita.

No declarar concurrencia real si se ejecutó secuencialmente.

### 20.3 Authorization / IDOR

- owner lista documentos propios;
- owner descarga propio;
- owner carga cuando permitido;
- owner remueve cuando permitido;
- otro Cliente no lista;
- otro Cliente no descarga;
- otro Cliente no carga;
- otro Cliente no remueve;
- manipulación de document_id falla;
- documento de case B enviado bajo case A falla;
- storage_key no funciona como autorización;
- Analista solamente según capabilities.

### 20.4 Eliminación lógica

- remove establece metadata correcta;
- archivo deja de estar disponible funcionalmente;
- retry produce resultado estable;
- documento removido no aparece en listado normal;
- descarga de removido se rechaza;
- metadata histórica permanece;
- auditoría permanece.

No probar purga porque está fuera de alcance.

### 20.5 Consistencia storage/DB

Mediante fault injection o equivalente:

- fallo de storage → no metadata activa;
- fallo DB después de almacenar → compensación elimina orphan;
- fallo de auditoría/transacción → no metadata parcialmente confirmada;
- archivo físico faltante → error controlado, no exposición interna.

### 20.6 Auditoría

- upload genera exactamente evento esperado;
- remove genera exactamente evento esperado;
- retry no duplica evento cuando deba ser idempotente;
- actor/IP/UA/correlation correctos;
- no payload/binario/secreto;
- no path absoluto innecesario.

### 20.7 Regresión

Ejecutar:

- suite completa existente;
- tests SPRINT-02;
- consultation admission;
- creación/reutilización;
- submit;
- estados;
- authorization;
- wallet/provider regression relevante.

SPRINT-03 no debe romper el núcleo.

---

## 21. Pruebas de seguridad adicionales

Intentar explícitamente:

- `../../archivo`;
- doble extensión;
- extensión mayúscula/minúscula;
- MIME falso;
- archivo ejecutable renombrado;
- document ID de otro usuario;
- case ID de otro usuario;
- documento removido;
- storage key manipulada;
- nombre con CR/LF para headers;
- nombre extremadamente largo;
- upload concurrente;
- replay de request.

No afirmar protección contra malware desconocido si no existe scanner.

---

## 22. Compatibilidad de hosting

Recordar restricciones productivas conocidas:

- Neubox/cPanel;
- sin SSH productivo;
- sin terminal PHP productiva;
- SFTP/File Manager;
- Cron cPanel disponible;
- PHP 8.3;
- MariaDB 10.6.27.

SPRINT-03 sigue siendo LOCAL.

Sin embargo, verificar estáticamente que el diseño de storage privado pueda
operar bajo esas restricciones.

No depender de:

- daemon permanente;
- symlink público para evidencia;
- proceso background obligatorio;
- extensión PHP no confirmada;
- comando shell en runtime.

Si se requiere una capacidad no comprobada del hosting, documentar riesgo y
no inventar disponibilidad.

---

## 23. Performance

No realizar optimización prematura.

Comprobar como mínimo que:

- listado usa índices/relaciones razonables;
- conteo de activos para límite no carga archivos;
- descarga usa streaming y no carga archivos completos innecesariamente en
  memoria;
- SHA-256 se calcula de forma razonable para máximo 3 MiB.

No realizar benchmark artificial salvo que exista evidencia de problema.

---

## 24. Orden recomendado

1. Leer fuentes obligatorias.
2. Ejecutar baseline tests.
3. Inspeccionar schema/document model/storage/auth.
4. Presentar inventario de archivos/clases previstos y riesgos.
5. Agregar tests que fallen por funcionalidad ausente.
6. Implementar políticas/VOs necesarios.
7. Implementar private storage abstraction si es necesaria.
8. Implementar UploadCaseDocument.
9. Implementar límite concurrente.
10. Implementar ListCaseDocuments.
11. Implementar DownloadCaseDocument.
12. Implementar RemoveCaseDocument.
13. Integrar auditoría/idempotencia.
14. Ejecutar security/IDOR tests.
15. Ejecutar carrera 7+2 real.
16. Ejecutar fault-injection de consistencia.
17. Ejecutar regresión completa.
18. Revisar diff y alcance.
19. Actualizar documentación permitida.
20. Generar SPRINT-03-RESULT.md.
21. DETENERSE.

No iniciar SPRINT-04.

---

## 25. Condiciones de parada

Detener únicamente la parte afectada y solicitar decisión si:

- schema real de `notification_case_documents` contradice SPRINT-01;
- se requiere cambiar una regla contractual;
- no puede determinarse qué estados permiten upload/remove;
- se requiere storage público;
- hosting exige exponer evidencia públicamente;
- se requiere nueva infraestructura externa;
- se requiere modificar máquina de estados;
- se requiere modificar ownership;
- se requiere cambiar reglas 3/30/90;
- se requiere cambiar máximo 8 o 3 MB;
- se requiere nueva política de retención;
- se requiere producción;
- una migration necesaria no puede ser portable;
- autorización existente contradice capabilities aprobadas.

Un fallo ordinario de implementación/test no constituye condición automática
de parada: diagnosticar y corregir dentro del alcance.

---

## 26. Documentación durante el Sprint

Actualizar únicamente cuando corresponda:

- docs/DECISION_LOG.md
  - registrar DEC-035 del roadmap aprobado;
- docs/PROJECT_STATE.md;
- documentación maestra únicamente si la implementación real requiere
  aclaración no contractual;
- docs/sprints/SPRINT-03-RESULT.md.

Actualizar CHANGE_REQUESTS.md únicamente si surge un cambio contractual que
requiere aprobación.

No marcar ningún CR como aprobado sin Project Owner.

No reescribir resultados históricos de SPRINT-01 o SPRINT-02.

---

## 27. Criterios de aceptación

### Gobernanza

- [ ] DEC-035 registrado.
- [ ] PROJECT_STATE identifica SPRINT-03 durante ejecución.
- [ ] SPRINT-02 permanece cerrado/aprobado.
- [ ] No se inicia SPRINT-04.

### Storage

- [ ] Evidencia almacenada exclusivamente en storage privado.
- [ ] No existen URLs públicas permanentes.
- [ ] Storage keys son generadas por sistema.
- [ ] No existe path traversal.

### Validación

- [ ] PDF/JPG/JPEG/PNG permitidos.
- [ ] MIME real validado.
- [ ] Máximo 3 MiB centralizado.
- [ ] Archivos inválidos rechazados.

### Límite

- [ ] Máximo 8 documentos activos.
- [ ] Carrera 7+2 probada realmente.
- [ ] Nunca se crean 9 activos.

### Integridad

- [ ] SHA-256 persistido.
- [ ] DB/filesystem tienen estrategia de compensación.
- [ ] Fault injection demuestra comportamiento estable.

### Autorización

- [ ] Owner opera únicamente recursos permitidos.
- [ ] Otro Cliente no accede.
- [ ] Analista usa capability explícita.
- [ ] IDOR probado adversarialmente.
- [ ] Documento se resuelve dentro del case autorizado.

### Download

- [ ] Descarga privada.
- [ ] Sin exposición de path.
- [ ] Documento removido no descargable.
- [ ] Headers seguros.
- [ ] Streaming razonable.

### Remove

- [ ] Eliminación lógica.
- [ ] Metadata histórica permanece.
- [ ] Auditoría append-only.
- [ ] Retry estable.
- [ ] Sin purga física.

### Seguridad

- [ ] Filename no controla storage path.
- [ ] MIME spoofing probado.
- [ ] Double extension probada.
- [ ] CR/LF filename probado.
- [ ] No ejecución de evidencia.
- [ ] Riesgo malware documentado honestamente si no existe scanner.

### Calidad

- [ ] Tests nuevos pasan.
- [ ] Suite SPRINT-02 continúa pasando.
- [ ] Regresión relevante pasa.
- [ ] Formato/análisis estático pasa para archivos afectados.
- [ ] Ningún test deshabilitado para ocultar fallo.

### Alcance

- [ ] Sin Portal Cliente final.
- [ ] Sin Portal Administrativo.
- [ ] Sin DataTables.
- [ ] Sin email/outbox worker.
- [ ] Sin Cron.
- [ ] Sin purga.
- [ ] Sin producción.

---

## 28. Formato obligatorio de SPRINT-03-RESULT.md

1. Executive Summary
2. Scope Completed
3. Scope Not Completed / Explicit Exclusions
4. Documentation and Evidence Reviewed
5. Roadmap / DEC-035 Registration
6. Baseline Verification
7. Architecture Implemented
8. Private Storage Design
9. Document Metadata Model
10. Upload Implementation
11. MIME / Extension / Size Validation
12. SHA-256 and Integrity
13. File Limit and Concurrency
14. Download Authorization
15. Listing
16. Logical Removal
17. Filesystem / Database Consistency Strategy
18. Idempotency
19. Audit Events
20. Authorization and IDOR Protection
21. Security Tests
22. Files Modified
23. Database Operations Executed
24. Tests Added
25. Tests Executed and Exact Results
26. Concurrency Evidence
27. Fault Injection Evidence
28. Hosting Compatibility Review
29. Regression Results
30. Failures, Limitations and Known Issues
31. Production Impact
32. Rollback and Recovery
33. Requirement Traceability
34. Acceptance Criteria Verification
35. Recommended Follow-up Sprint Boundaries
36. Sprint Conclusion

Incluir comandos ejecutados, conteos exactos y evidencia concreta.

No exponer secretos.

### Attestation final

Producción: NO MODIFICADA
Cron/cPanel: NO CONFIGURADO
Emails/notificaciones: NO ENTREGADOS
Portal Cliente: NO IMPLEMENTADO
Portal Administrativo: NO IMPLEMENTADO
DataTables: NO IMPLEMENTADOS
Purga física: NO IMPLEMENTADA
Expedientes retroactivos: NO CREADOS

Finalizar con:

READY FOR OWNER REVIEW

No iniciar SPRINT-04.

---

## 29. Definición de terminado

SPRINT-03 termina cuando puede demostrarse localmente:

expediente autorizado
→ upload válido
→ storage privado
→ metadata
→ SHA-256
→ auditoría
→ listado autorizado
→ descarga privada autorizada
→ eliminación lógica
→ trazabilidad preservada

y simultáneamente:

7 documentos activos
+ dos uploads concurrentes
→ uno aceptado
→ uno rechazado
→ máximo final 8

y:

Cliente A
→ intenta acceder a evidencia de Cliente B
→ acceso rechazado server-side

y:

fallo DB después de almacenar
→ compensación
→ sin orphan permanente conocido

sin haber construido portales finales ni modificado producción.