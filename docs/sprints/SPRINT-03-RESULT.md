# VINTrack — SPRINT-03: Evidence & Secure File Management — RESULT

**Fecha:** 2026-08-13  
**Entorno:** local/testing  
**Estado:** APPROVED WITH OBSERVATIONS

## 1. Executive Summary
Se implementó evidencia privada con upload, metadata/SHA-256, límite concurrente, listado, descarga autorizada, soft removal, idempotencia, auditoría y compensación filesystem/DB. Resultado final: **82 tests, 295 assertions, 0 fallos**. Producción no fue tocada.

## 2. Scope Completed
Upload/list/download/remove; PDF/JPG/JPEG/PNG; 3 MiB; ocho activos; storage privado; ownership/IDOR; SHA-256; keys aleatorias; auditoría; replay; carrera 7+2; fault injection; endpoints mínimos.

## 3. Scope Not Completed / Explicit Exclusions
Sin portales, DataTables, revisión visual, delivery, email real, Cron/Scheduler/cPanel, purga, cloud, scanner externo, backfill, casos retroactivos, SPRINT-04 ni producción.

## 4. Documentation and Evidence Reviewed
Leídos `AGENTS.md`, siete documentos maestros, resultados SPRINT-00/01/02 y rector SPRINT-03. Inspeccionados schema, migrations, modelos, storage, rutas, roles, autorización, auditoría y tests.

## 5. Roadmap / DEC-035 Registration
Resolución formal del Project Owner: DEC-035 conserva su significado histórico como cierre de SPRINT-02 y no se renumera, sobrescribe ni reutiliza. El roadmap posterior a SPRINT-02 está registrado correctamente en **DEC-036**, que queda confirmado como identificador canónico y oficial. La referencia a DEC-035 en el rector de SPRINT-03 permanece intacta como error documental histórico.

## 6. Baseline Verification
Antes de cambios, `php artisan test --no-ansi`: **76 passed, 258 assertions, 0 failed**, 29.43 s. Se preservó el baseline local no versionado de SPRINT-02.

## 7. Architecture Implemented
Modelo documental, validador, adapter de storage privado, servicio de aplicación para las cuatro operaciones, autorización/auditoría central extendidas, controller delgado y rutas autenticadas.

## 8. Private Storage Design
Disco `local` en `storage/app/private`; key `notification-case-evidence/<UUID>.<ext>`. No acepta path del cliente, no genera URL pública y descarga por stream con `nosniff`, `private, no-store` y attachment.

## 9. Document Metadata Model
Se reutilizó `notification_case_documents` sin migration: case/uploader, original name, disk/key, MIME, extensión, bytes, SHA-256, removal actor/time y timestamps; UQ disk/key e índice activos existentes.

## 10. Upload Implementation
Valida; bloquea case; autoriza; resuelve replay; cuenta activos; almacena; inserta metadata y audit en transacción; compensa el objeto si falla DB/audit.

## 11. MIME / Extension / Size Validation
Allowlist conjunta extensión+MIME real. PDF exige `%PDF-`; imágenes decoder concordante. Rechaza vacío, spoof, ejecutable/doble extensión y >3,145,728 bytes. El límite exacto pasa.

## 12. SHA-256 and Integrity
`hash_file('sha256')` sobre el temporal validado, persistido en 64 caracteres. No deduplica uploads intencionales.

## 13. File Limit and Concurrency
Máximo desde settings. Lock `FOR UPDATE` del case antes de count/insert. Carrera MySQL 8.4.3: siete + dos procesos/conexiones → uno `OK`, otro `MAX_ACTIVE_DOCUMENTS`, final **8**.

## 14. Download Authorization
Autoriza case; verifica pertenencia document→case, activo y existencia física. No expone path/key. Sanitiza control chars, CR/LF, slash y backslash del header.

## 15. Listing
Solo activos; expone id, nombre, MIME, extensión, tamaño y fecha. No expone disk/key/path/hash/audit interno.

## 16. Logical Removal
Marca `removed_at/by`, conserva binario/metadata, audita una vez y responde estable a retry. Sin purga.

## 17. Filesystem / Database Consistency Strategy
Store bajo lock/transacción; metadata+audit comparten commit. Fallo storage: sin metadata. Fallo DB/audit posterior: rollback y delete compensatorio. Archivo ausente: 404 controlado.

## 18. Idempotency
Upload requiere `Idempotency-Key` ASCII ≤128; event key case+actor+key recupera el mismo documento. Igual filename/hash con keys distintas es intención distinta. Remove se estabiliza por estado y event key documental.

## 19. Audit Events
`DOCUMENT_UPLOADED`/`DOCUMENT_REMOVED`: case, actor/rol, timestamp, IP, UA, correlation/request key y metadata allowlisted. Sin binario, payload, secretos ni paths.

## 20. Authorization and IDOR Protection
Owner opera propios; muta solo PENDING/REJECTED. Otro cliente no lista/sube/descarga/remueve. Documento siempre se resuelve dentro del case. Analista/admin pasan por métodos explícitos del servicio central existente.

## 21. Security Tests
MIME falso, ejecutable renombrado, doble extensión, vacío, case/document cruzados, otro cliente, removido, key no expuesta, headers, límite, replay y carrera. Malware scanner: **NOT AVAILABLE**; riesgo residual honesto.

## 22. Files Modified
Nuevos: modelo, excepción, validador, storage adapter, servicio, controller, test y tres scripts harness. Modificados: case model, autorización, auditoría, rutas y PROJECT_STATE. Generado este RESULT. Sin migration.

## 23. Database Operations Executed
SQLite in-memory. MySQL `vintrack_dev`: harness aislado 7+2 y cleanup verificado. Conteo antes del cleanup: 8. Sin migration/SQL/producción.

## 24. Tests Added
Seis escenarios feature, 37 assertions; tres scripts para prepare, dos workers y cleanup concurrente.

## 25. Tests Executed and Exact Results
- Baseline: **76/258**, 0 fallos.
- Enfocada: **6/37**, 0 fallos.
- Completa final: **82/295**, 0 fallos, 10.22 s.
- `pint --test` archivos afectados: passed.
- `route:list`: cuatro rutas registradas.

## 26. Concurrency Evidence
Dos `Start-Process php` con barrier compartido. Resultados: worker 1 `ERROR/MAX_ACTIVE_DOCUMENTS`; worker 2 `OK/document_id=8`; `active_before_cleanup=8`; `cleaned=true`.

## 27. Fault Injection Evidence
Audit inyectó `RuntimeException` después del store: SQL revirtió metadata y compensación dejó `Storage::allFiles()=[]`. Fallos de validación/storage no insertan metadata.

## 28. Hosting Compatibility Review
Sin daemon, symlink público, shell runtime, cloud o background obligatorio. Fileinfo/GD existen localmente. Ruta/permisos productivos siguen pendientes de verificación autorizada.

## 29. Regression Results
Suite completa verde: núcleo SPRINT-02, admission antes de wallet/provider, creación/reutilización, submit, estados, authorization y wallet/provider.

## 30. Failures, Limitations and Known Issues
Scanner antimalware no disponible, aceptado como riesgo residual y Production Gate; no se instala infraestructura adicional. MariaDB 10.6.27 y la verificación productiva de fileinfo, GD, ruta privada, permisos, inaccesibilidad HTTP directa y streaming/download permanecen Production Gates. La base desechable anterior no existía; el harness usó datos aislados en MySQL local principal y tuvo cleanup exitoso. Capabilities siguen materializadas por el servicio role-based del baseline, sin catálogo persistente nuevo.

## 31. Production Impact
Producción no conectada, migrada, desplegada ni modificada.

## 32. Rollback and Recovery
Retirar rutas/clases y revertir extensiones puntuales; no hay migration. Con datos reales, preservar metadata/binarios y preferir roll-forward. Compensación toca solo el objeto recién creado.

## 33. Requirement Traceability
BR-019/DEC-017 formatos/límites; BR-020/DEC-021 audit; BR-026/035 y DEC-033 auth/estado; DEC-029/BR-033 soft remove; SPRINT-01 §15–16 storage/SHA/locks/eventos.

## 34. Acceptance Criteria Verification
Storage, validación, concurrencia, integridad, autorización/IDOR, download/list/remove, seguridad, tests/regresión/formato y alcance negativo: PASS. Gobernanza: PASS; DEC-036 confirmado como roadmap canónico y DEC-035 preservado como cierre histórico de SPRINT-02.

## 35. Recommended Follow-up Sprint Boundaries
No iniciar SPRINT-04 sin autorización expresa. Antes de producción: ejecutar MariaDB 10.6.27, evaluar malware scanning, verificar fileinfo/GD, ruta y permisos privados, inaccesibilidad HTTP directa y streaming/download en Neubox, y cerrar los Production Gates acumulados de SPRINT-02.

## 36. Sprint Conclusion
Demostrado localmente case autorizado → upload → privado → metadata/SHA/audit → list/download → soft remove; además IDOR rechazado, replay, compensación y carrera 7+2 con final ocho.

```text
Producción: NO MODIFICADA
Cron/cPanel: NO CONFIGURADO
Emails/notificaciones: NO ENTREGADOS
Portal Cliente: NO IMPLEMENTADO
Portal Administrativo: NO IMPLEMENTADO
DataTables: NO IMPLEMENTADOS
Purga física: NO IMPLEMENTADA
Expedientes retroactivos: NO CREADOS
```

## Owner Review / Governance Closure

**Decisión formal:** `SPRINT-03 — APPROVED WITH OBSERVATIONS`
**Fecha de aprobación:** 2026-08-13

Observaciones registradas:

1. `OBS-03-01`: DEC-036 es el identificador canónico del roadmap; DEC-035 conserva su significado histórico. El rector no se modifica.
2. `OBS-03-02`: ausencia de malware scanner aceptada como riesgo residual y Production Gate; sin instalar servicios adicionales.
3. `OBS-03-03`: fileinfo, GD, ruta privada, permisos, bloqueo de acceso HTTP directo y streaming/download deben verificarse en Neubox antes de producción.
4. `OBS-03-04`: MariaDB 10.6.27 continúa como Production Gate pendiente.

Estas observaciones no reabren ni modifican la implementación de SPRINT-03. Producción permanece no autorizada y SPRINT-04 requiere autorización explícita separada.

`APPROVED WITH OBSERVATIONS`
