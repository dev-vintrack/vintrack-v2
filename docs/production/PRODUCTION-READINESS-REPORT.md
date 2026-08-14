# VINTrack — Production Readiness Report

Fecha: 2026-08-14
Alcance: SPRINT-08 local
Production Authorization: **NOT AUTHORIZED**

## 1. System Baseline

Laravel 12.62.0, PHP 8.3.30, MySQL 8.4.3, `America/Mexico_City` como timezone contractual del módulo. Baseline previo: 104 tests, 449 assertions, 0 fallos. Mail local usa el driver `log`; `fileinfo`, GD, OpenSSL, mbstring y PDO MySQL están disponibles localmente. Esto no acredita Neubox.

## 2. Sprint Coverage

SPRINT-01 a SPRINT-07 se mantienen contractuales. SPRINT-08 añadió idempotencia persistente request→consultation, clasificación non-retryable de destinatario inválido y documentación operativa. No se ejecutó deployment, Cron real, SMTP real ni acceso a producción.

## 3. Production Gate Matrix

| Gate | Estado | Evidencia / condición |
|---|---|---|
| PG-01 MariaDB 10.6.27 | BLOCKED_EXTERNAL | No existe runtime aislado disponible; Docker/Podman ausentes. |
| PG-02 idempotencia request→consultation | OPEN | Garantía persistente implementada y pruebas funcionales verdes; falta prueba obligatoria multiproceso instrumentada. |
| PG-03 EXPLAIN con volumen | OPEN | No se generó todavía dataset representativo desechable ni evidencia Before/After. |
| PG-04 deuda histórica migrations | OPEN | Confirmadas dependencia `adapter_code`, `down()` irreversible y rollback global `label/icon`; requiere estrategia de baseline/forward-fix. |
| PG-05 malware scanning | OPEN | MIME/hash no escanean malware; no hay solución aprobada. |
| PG-06 storage/fileinfo/GD/permisos Neubox | BLOCKED_EXTERNAL | Extensiones verificadas sólo localmente. |
| PG-07 backup/rollback | OPEN | Runbooks preparados; restauración integral local aún no ejecutada. |
| PG-08 PHP CLI cPanel | BLOCKED_EXTERNAL | Ruta/binario no probados en Neubox. |
| PG-09 Artisan por cPanel Cron | BLOCKED_EXTERNAL | Requiere entorno real autorizado. |
| PG-10 working directory | BLOCKED_EXTERNAL | Ruta real desconocida. |
| PG-11 frecuencia/timeout/overlap Cron | BLOCKED_EXTERNAL | Diseño preparado; ejecución real no autorizada. |
| PG-12 logs/cache | BLOCKED_EXTERNAL | Drivers locales conocidos; permisos/rotación productivos no verificados. |
| PG-13 URL productiva | BLOCKED_EXTERNAL | No se modificó DNS/config productiva. |
| PG-14 SMTP/TLS/remitente | BLOCKED_EXTERNAL | SMTP productivo no autorizado. |
| PG-15 SPF/DKIM/DMARC | BLOCKED_EXTERNAL | Requiere DNS/dominio real. |
| PG-16 límites/rate correo | BLOCKED_EXTERNAL | Requiere proveedor real. |
| PG-17 rebotes | BLOCKED_EXTERNAL | Requiere proveedor real. |
| PG-18 DataTables CDN/CSP | OPEN | CDN 1.13.6 aceptado; política CSP/SRI/local asset pendiente. |
| PG-19 autorización producción | DEFERRED_BY_OWNER | Producción explícitamente no autorizada. |
| PG-20 APP_DEBUG/config segura | BLOCKED_EXTERNAL | Checklist preparado; valores productivos no verificados. |
| PG-21 secrets/log sanitization | OPEN | Revisión enfocada sin hallazgo confirmado; requiere auditoría formal previa al artifact. |

## 4. Closed Gates

Ningún Gate que requiere entorno real fue cerrado. OBS-07-02 sí quedó resuelta como hardening local, pero no sustituye los Gates SMTP.

## 5. Open Gates

PG-02, PG-03, PG-04, PG-05, PG-07, PG-18 y PG-21.

## 6. External Blockers

MariaDB 10.6.27, Neubox/cPanel, rutas y permisos, Cron, SMTP/TLS, DNS, límites y rebotes.

## 7. Security Assessment

La suite conserva pruebas negativas de auth, roles, IDOR, mass assignment, uploads/downloads, MIME, límites, XSS y parámetros DataTables. La nueva identidad idempotente se vincula a usuario, servicio, criterio, valor y servicios solicitados; la misma key con otro payload se rechaza.

## 8. Database Assessment

MySQL 8.4.3 ejecutó la migration `consultation_operations` y su ciclo reversible. La tabla usa hashes SHA-256, unique `(user_id,idempotency_key_hash)`, FK restrictivas y estados explícitos. MariaDB permanece no verificada.

## 9. Performance Assessment

Arquitectura server-side y tests de paginación/búsqueda permanecen verdes. Sin dataset representativo ni EXPLAIN reproducible, performance es **NOT VERIFIED** y no se agregaron índices especulativos.

## 10. Storage Assessment

Máximo teórico: 24 MiB/case. Escenarios brutos: 1,000 = 24,000 MiB (~23.44 GiB); 10,000 = ~234.38 GiB; 100,000 = ~2.29 TiB. No representan consumo esperado ni cuota contratada.

## 11. Cron Assessment

Los tres comandos discretos e idempotentes existen. Scheduler no es dependencia productiva. cPanel Cron no fue ejecutado.

## 12. Email Assessment

SMTP conserva semántica at-least-once. Usuario inexistente/email inválido es terminal non-retryable/skipped en un intento; Portal sigue independiente. El crash window SMTP externo permanece.

## 13. Backup Assessment

Procedimiento documentado; backup/restauración productivos no ejecutados.

## 14. Rollback Assessment

La migration SPRINT-08 pasó rollback local. La cadena histórica no garantiza rollback global seguro; para migrations destructivas o de datos debe preferirse forward-fix/restauración validada.

## 15. Known Risks

- Crash después de respuesta externa y antes de persistencia local: se bloquea retry automático como `FAILED_AMBIGUOUS`, pero requiere reconciliación operativa.
- No existe exactly-once externo si el provider no acepta idempotency key.
- Falta scanner antimalware.
- Cadena histórica no reconstruible/reversible de extremo a extremo sin remediación.

## 16. Required Production Verification

Ejecutar todos los Gates `BLOCKED_EXTERNAL` sólo bajo autorización posterior y registrar evidencia, sin secretos.

## 17. Recommended Deployment Sequence

No desplegar. Primero cerrar PG-02/03/04/05/07/21; luego staging/MariaDB; después verificar Neubox, backup/restauración, artifact, config, storage, Cron y SMTP; finalmente solicitar autorización separada.

## 18. Production Readiness Verdict

**NOT READY**

Production Authorization: **NOT AUTHORIZED**
Deployment: **NOT EXECUTED**
