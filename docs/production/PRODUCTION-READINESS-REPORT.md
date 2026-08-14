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
| PG-02 idempotencia request→consultation | CLOSED | MySQL multiproceso: misma key produjo provider=1, debit=1, consultation=1 y operation=1; keys distintas produjeron 2/2/2/2. |
| PG-03 EXPLAIN con volumen | CLOSED | Dataset 100k/10k/10k; escenarios medidos <5 s, búsqueda sin timeout y EXPLAIN Before/After reproducible. |
| PG-04 deuda histórica migrations | CLOSED | Bootstrap `database/schema/mysql-schema.sql` probado en DB vacía (43 tablas/53 FK/54 migrations); historia previa declarada forward-only. |
| PG-05 malware scanning | OPEN | MIME/hash no escanean malware; no hay solución aprobada. |
| PG-06 storage/fileinfo/GD/permisos Neubox | BLOCKED_EXTERNAL | Extensiones verificadas sólo localmente. |
| PG-07 backup/rollback | CLOSED | Dump+evidence archive+manifest SHA-256 restaurados en DB/storage desechables; 10 verificaciones pasaron. VERIFIED LOCALLY. |
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
| PG-21 secrets/log sanitization | CLOSED | Auditoría formal sin secretos reales versionados; excepción/provider/OTP/mail sanitizados y regresión verde. |

## 4. Closed Gates

PG-02, PG-04, PG-07 y PG-21 quedaron cerrados con evidencia local reproducible. Ningún Gate que requiere entorno real fue cerrado.

## 5. Open Gates

PG-05 y PG-18.

## 6. External Blockers

MariaDB 10.6.27, Neubox/cPanel, rutas y permisos, Cron, SMTP/TLS, DNS, límites y rebotes.

## 7. Security Assessment

La suite conserva pruebas negativas de auth, roles, IDOR, mass assignment, uploads/downloads, MIME, límites, XSS y parámetros DataTables. La nueva identidad idempotente se vincula a usuario, servicio, criterio, valor y servicios solicitados; la misma key con otro payload se rechaza.

## 8. Database Assessment

MySQL 8.4.3 ejecutó la migration `consultation_operations` y su ciclo reversible. La tabla usa hashes SHA-256, unique `(user_id,idempotency_key_hash)`, FK restrictivas y estados explícitos. MariaDB permanece no verificada.

## 9. PG-03 Performance Remediation

Dataset desechable: 200 users, 100,000 consultations, 10,000 cases y 10,000 events. Baseline: Cliente ~95 ms, Admin ~195 ms, filtro Cliente ~2.21 s y búsqueda derivada >180 s/timeout. La remediación separa conteos base, pagina IDs antes de enriquecer, materializa candidatos exactos de búsqueda/status, excluye asociaciones retroactivas o superadas y resuelve eventos por caso/página sin cambiar 3/30/90.

P50 local (3 runs): Cliente 54.831 ms; Admin 136.343 ms; filtro Cliente 94.548 ms; búsqueda derivada 1,447.694 ms; placa 1,646.462 ms; status 2,225.898 ms; orden VIN/página offset 5,000 2,039.715 ms. Ningún escenario ni statement medido superó 5 s; no se carga el universo en PHP.

Before, placa estimaba ~99,246 filas por lookup. After, `consultations_service_normalized_created_idx(provider_service_id, normalized_value, criterio, created_at, id)` estima 20; `consultations_normalized_created_idx(normalized_value, created_at, id)` resuelve candidatos; timeline usa `notification_case_events_timeline_idx`, estimación 1. Costo: dos índices secundarios y mantenimiento de escritura/storage. Migration `up/down/up` verificada. PG-03 CLOSED.

## 10. Storage Assessment

Máximo teórico: 24 MiB/case. Escenarios brutos: 1,000 = 24,000 MiB (~23.44 GiB); 10,000 = ~234.38 GiB; 100,000 = ~2.29 TiB. No representan consumo esperado ni cuota contratada.

## 11. Cron Assessment

Los tres comandos discretos e idempotentes existen. Scheduler no es dependencia productiva. cPanel Cron no fue ejecutado.

## 12. Email Assessment

SMTP conserva semántica at-least-once. Usuario inexistente/email inválido es terminal non-retryable/skipped en un intento; Portal sigue independiente. El crash window SMTP externo permanece.

## 13. Backup Assessment

Backup/restore integral fue VERIFIED LOCALLY con dump de 102,282 bytes, archive de evidencia de 3,584 bytes, manifest de 274 bytes y SHA-256. Producción no fue verificada.

## 14. Rollback Assessment

Las migrations SPRINT-08 pasaron ciclos reversibles locales. Para instalaciones nuevas, `php artisan migrate` carga el bootstrap versionado; configuración/datos de referencia requieren import aprobado separado. En entornos existentes sólo se aplican migrations incrementales. Todo lo anterior al baseline es forward-only: ante fallo, restaurar backup consistente o aplicar forward-fix.

## 15. Known Risks

- Crash después de respuesta externa y antes de persistencia local: se bloquea retry automático como `FAILED_AMBIGUOUS`, pero requiere reconciliación operativa.
- No existe exactly-once externo si el provider no acepta idempotency key.
- Falta scanner antimalware.
- Cadena histórica no reconstruible/reversible de extremo a extremo sin remediación.

## 16. Required Production Verification

Ejecutar todos los Gates `BLOCKED_EXTERNAL` sólo bajo autorización posterior y registrar evidencia, sin secretos.

## 17. Recommended Deployment Sequence

No desplegar. El Owner debe decidir PG-05/PG-18; luego staging/MariaDB y Gates Neubox bajo autorización separada.

## 18. Production Readiness Verdict

**READY WITH CONDITIONS**

Production Authorization: **NOT AUTHORIZED**
Deployment: **NOT EXECUTED**
