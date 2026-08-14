# VINTrack — SPRINT-08 Hardening & Production Readiness — RESULT

Fecha: 2026-08-14
Entorno: local
Producción: NOT AUTHORIZED

## 1. Executive Summary

Los targeted remediation passes cerraron localmente PG-02, PG-03, PG-04, PG-07 y PG-21. PG-03 quedó respaldado por dataset 100k, EXPLAIN Before/After y nueve escenarios sin timeout ni operación >5 s. PG-05/PG-18 permanecen OPEN por decisión del Owner y los Gates externos no se tocaron. Veredicto: **READY WITH CONDITIONS**.

## 2. Scope

Hardening local, migration aditiva, tests, revisión de Gates y runbooks.

## 3. Explicit Exclusions

Sin producción, Neubox, deployment, cPanel/Cron real, SMTP/DNS, datos productivos ni providers facturables.

## 4. Governance

DEC-036 confirmado como roadmap; DEC-043 como cierre SPRINT-07; Production permanece `NOT AUTHORIZED`. La autorización del Owner activa únicamente SPRINT-08 local.

## 5. Baseline

Antes de cambios: PHP 8.3.30; Laravel 12.62.0; MySQL 8.4.3; mail `log`; extensiones PDO MySQL/fileinfo/GD/OpenSSL/mbstring presentes; **104 tests, 449 assertions, 0 fallos**. Worktree ya contenía cambios documentales del Owner y se preservaron.

## 6. Production Gate Inventory

Se consolidaron PG-01 a PG-19 del rector y PG-20 configuración segura/APP_DEBUG y PG-21 secrets/logging. Matriz completa en `docs/production/PRODUCTION-READINESS-REPORT.md`.

## 7. Production Gate Final Matrix

| Gate | Final |
|---|---|
| PG-01 | BLOCKED_EXTERNAL |
| PG-02 | CLOSED |
| PG-03 | CLOSED |
| PG-04 | CLOSED |
| PG-05 | OPEN |
| PG-06 | BLOCKED_EXTERNAL |
| PG-07 | CLOSED |
| PG-08–PG-17 | BLOCKED_EXTERNAL |
| PG-18 | OPEN |
| PG-19 | DEFERRED_BY_OWNER |
| PG-20 | BLOCKED_EXTERNAL |
| PG-21 | CLOSED |

## 8. Idempotency Design

`consultation_operations` garantiza unique `(user_id,idempotency_key_hash)`. El fingerprint vincula provider service, criterio, valor y servicios. Estados: `IN_PROGRESS`, `COMPLETED`, `FAILED_RETRYABLE`, `FAILED_AMBIGUOUS`. `COMPLETED` reconstruye response y consultation; key/payload distinto se rechaza. El débito usa correlation determinista por operation ID.

## 9. Idempotency Concurrency

Prueba multiproceso real contra MySQL 8.4.3: misma key produjo un worker `COMPLETED`, otro `IDEMPOTENCY_IN_PROGRESS` y contadores provider=1, debit=1, consultation=1, operation=1. Con keys distintas ambos completaron y los contadores fueron 2/2/2/2. PG-02 CLOSED.

## 10. Idempotency Failure Injection

Provider exception fue inyectada: operation `FAILED_AMBIGUOUS`, segundo intento no reinvoca provider, 0 débito, 0 consultation. Se cubrieron replay, payload conflict y claves distintas. La matriz completa A–G no quedó ejecutada.

## 11. Provider Crash Window

No se afirma exactly-once externo. Si provider pudo recibir la operación y el proceso falla antes de persistencia, VINTrack bloquea reintento automático y exige revisión/reconciliación.

## 12. Performance Dataset

Construido reproduciblemente en `vintrack_sprint08_performance`: 200 users, 100,000 consultations, 10,000 cases y 10,000 events sintéticos; sin provider ni mail.

## 13. EXPLAIN Before/After

Before: búsqueda global derivada >180 s/timeout; filtro Cliente ~2.21 s; lookup placa estimaba ~99,246 filas y usaba temporary/filesort. After: candidatos de búsqueda/status se materializan antes de la proyección, conteos base se separan y sólo se enriquece la página. EXPLAIN usa `consultations_service_normalized_created_idx` con 20 filas estimadas, `consultations_normalized_created_idx` con 20 y timeline con 1.

## 14. Index Decisions

Se conservan dos índices demostrados: `consultations_service_normalized_created_idx(provider_service_id, normalized_value, criterio, created_at, id)` para identidad de placa y `consultations_normalized_created_idx(normalized_value, created_at, id)` para candidatos VIN/derived. El índice anterior con `criterio` antes de `normalized_value` fue descartado: >15 s versus 1.71 s en orden/paginación. Costo: escritura y storage de dos índices secundarios; migration reversible validada.

## 15. N+1 Assessment

Tests existentes de histories/listados permanecen verdes; no se hizo perfilado adicional con volumen.

## 16. Migration Debt

Confirmada y resuelta mediante estrategia combinada: bootstrap versionado para instalaciones nuevas; incremental para existentes; historia previa forward-only; backup/restore o forward-fix para `adapter_code`, reestructura irreversible y `label/icon`. No se reescribió historia.

## 17. Full Migration Validation

`database/schema/mysql-schema.sql` cargó en DB vacía: 43 tablas, 53 FK y 54 migrations registradas. La migration normalizada pasó `up → down → up`. El bootstrap es schema-only; datos de referencia/configuración requieren import aprobado separado.

## 18. MySQL Validation

MySQL 8.4.3: migration y suite completa verificadas localmente.

## 19. MariaDB Validation

MariaDB 10.6.27: **NOT VERIFIED**. Docker y Podman no están disponibles; no se alteró Laragon principal.

## 20. Security Hardening

Se conserva cobertura auth/authz, IDOR, CSRF del stack web, mass assignment, uploads/downloads, MIME, XSS y DataTables. La identidad idempotente no almacena la key en claro, sólo SHA-256.

## 21. Authorization Matrix

| Resource | Customer | Admin/Analyst | Other customer | Unauthenticated |
|---|---|---|---|---|
| own history/case/evidence/portal | allowed by capability | authorized scope | denied | denied |
| admin history/review | denied | allowed by role/menu | denied | denied |
| timeline/download | owner | authorized analyst | denied | denied |

## 22. IDOR

Regresión negativa verde para cases, evidence y portal notifications.

## 23. XSS

Portal payload se escapa; DataTables/Blade y mail conservan templates existentes. No se usa normalización textual como sustituto de escaping.

## 24. SQL/DataTables

Allowlist, length cap, filters/pagination server-side y pruebas adversariales existentes permanecen verdes.

## 25. Evidence Security

Private storage, MIME real, extensiones permitidas, 3 MiB, 8 activos, hash, soft delete y descarga autorizada permanecen verdes localmente.

## 26. Malware Scanning Assessment

MIME y SHA-256 no son scanning. Opciones: scanner previo al upload, API externa con cuarentena, proceso administrativo o aceptación explícita. Sin solución aprobada, PG-05 OPEN.

## 27. Storage Capacity

24 MiB/case máximo teórico; 1k ≈23.44 GiB, 10k ≈234.38 GiB, 100k ≈2.29 TiB.

## 28. Private Storage Assessment

Local extensions disponibles. Ruta, ownership, permisos, backup e inaccesibilidad HTTP en Neubox: BLOCKED_EXTERNAL.

## 29. Invalid Recipient Hardening

Email inválido crea delivery `skipped`, outbox `FAILED`, error `NON_RETRYABLE`, un intento. Portal independiente. SMTP temporal sigue retryable.

## 30. SMTP Assessment

At-least-once preservado; exactly-once externo no afirmado. SMTP/TLS/DNS/rate/rebotes no verificados.

## 31. cPanel/PHP CLI Assessment

No verificado; no se inventaron rutas/binarios.

## 32. Cron Assessment

Comandos candidatos y checklist documentados; no se configuró Cron.

## 33. Backup Assessment

Runbook preparado. Restauración integral local no ejecutada: NOT VERIFIED.

## 34. Rollback Assessment

Migration SPRINT-08 reversible localmente. Cadena global requiere forward-fix/restore; `migrate:rollback` no es garantía.

## 35. Failure Injection

Ejecutada para provider exception y email inválido. Matriz DB/filesystem/stale lease/malformed outbox no completada.

## 36. Data Integrity

Suite y constraints verdes; diagnóstico masivo formal de todas las inconsistencias no quedó ejecutado.

## 37. Concurrency Regression

Regresiones existentes pasan. Request/request multiproceso y stress/deadlock permanecen pendientes.

## 38. Deadlock/Retry Assessment

Operaciones DB críticas conservan transacciones/retries acotados. No se envolvieron llamadas externas en retry. Stress controlado no ejecutado.

## 39. Configuration Hardening

Se preservan defaults 3/30/90, limits, timezone, outbox attempts/lease. Checklist productivo exige `APP_DEBUG=false`.

## 40. Secrets/Logging Assessment

Auditoría formal: ningún `.env`, private key o credencial real versionados; sólo placeholders. Se eliminaron mensajes/traces de excepciones al usuario, mensajes externos de providers, email OTP en claro y errores SMTP crudos. Las keys de consulta se hashean. PG-21 CLOSED localmente.

## 41. Tests Added

Replay idempotente; multiproceso same/different key; fallos pre-provider, admission, provider inequívoco/ambiguo, débito y persistencia; retry seguro; invalid recipient; normalización/index de identity; performance generator/benchmark; backup/restore e integrity harnesses.

## 42. Tests Executed

Baseline original: 104/449. Enfocados finales: Core 12/70, History 7/35, Delivery 5/33 y HTTP status 16/16. Suite final: **112 tests, 496 assertions, 0 fallos**. Migration normalizada `down → up`: PASS. Multiproceso final: same key 1/1/1/1; different keys 2/2/2/2.

## 43. Performance Results

VERIFIED LOCALLY. P50 (3 runs): Cliente 54.831 ms; Admin 136.343 ms; filtro Cliente 94.548 ms; búsqueda derivada 1,447.694 ms; placa 1,646.462 ms; status 2,225.898 ms; orden VIN/página offset 5,000 2,039.715 ms. Ninguna operación medida >5 s; PG-03 CLOSED.

## 44. Migrations Added/Changed

Nueva reversible: `2026_08_14_130000_add_normalized_value_to_consultations.php`; bootstrap `database/schema/mysql-schema.sql`. Ninguna migration histórica modificada.

## 45. Files Modified

Hardening de consultation/admission/history/providers/logs/mail, modelo Consultation, tests, harnesses, migration normalizada, schema bootstrap y documentación SPRINT-08/production. Se preservaron migrations históricas.

## 46. Remaining Risks

PG-05 malware, PG-18 CDN/CSP y Gates externos.

## 47. External Blockers

MariaDB, Neubox/cPanel, PHP CLI/path, permissions/storage, Cron, SMTP/TLS, DNS y rebotes.

## 48. Production Readiness Verdict

**READY WITH CONDITIONS**

## 49. Requirement Traceability

Idempotencia: §§8–11; performance: §§12–15/43; migrations: §§16–19/44; security: §§20–26; operations: §§27–34; regression: §§35–42; Gates: §§6–7/46–48.

## 50. Recommended Next Step

No desplegar ni iniciar SPRINT-09. El Owner debe decidir PG-05/PG-18. Después, validar MariaDB/staging y Gates Neubox sólo bajo autorización separada.

## 51. Sprint Conclusion

Local hardening: PASS
Full regression: PASS
Request idempotency: VERIFIED
Performance: VERIFIED LOCALLY
Migration chain: VERIFIED LOCALLY
MySQL 8.4.3: VERIFIED
MariaDB 10.6.27: NOT VERIFIED
Evidence security: VERIFIED
Malware scanning: NOT VERIFIED
Neubox: NOT VERIFIED
cPanel Cron: NOT VERIFIED
SMTP production: NOT VERIFIED
Backup: VERIFIED LOCALLY
Rollback: VERIFIED LOCALLY
Production Readiness: READY WITH CONDITIONS
Production Authorization: NOT AUTHORIZED
Deployment: NOT EXECUTED

## 52. Targeted Remediation Evidence

### Failure injection matrix

| Caso | Estado operation | Provider | Débitos | Consultations | Retry / recuperación |
|---|---|---:|---:|---:|---|
| A. saldo falla antes de provider | `FAILED_RETRYABLE` | 0 | 0 | 0 | tras corregir saldo, misma key completa |
| B. admission falla después del claim | `FAILED_RETRYABLE` | 0 | 0 | 0 | retry sólo tras liberar capacidad |
| C. provider responde fallo inequívoco | `COMPLETED` | 1 | 0 | 1 fallida | replay local, no reinvoca |
| D. provider lanza fallo ambiguo | `FAILED_AMBIGUOUS` | 1 | 0 | 0 | revisión/reconciliación; no retry automático |
| E. débito falla después de provider | `FAILED_AMBIGUOUS` | 1 | 0 | 0 | revisión/reconciliación |
| F. persistencia falla después de débito | `FAILED_AMBIGUOUS` | 1 | 1 | 0 | reconciliar débito/provider antes de cualquier acción |
| G. retry posterior seguro | `COMPLETED` | 1 total | 1 | 1 | reutiliza la operation reservada |

No se afirma exactly-once externo. `FAILED_AMBIGUOUS` existe precisamente para cerrar el retry automático en la ventana provider→persistencia.

### Backup/restore local

Fixture: consultation, case `NT-2026-990001` `SUBMITTED`, event, outbox `DELIVERED`, Portal, delivery, metadata y PDF sintético. Artefactos: dump 102,282 bytes, evidence tar 3,584 bytes, manifest 274 bytes, todos con SHA-256. Se eliminó la DB/evidence fuente desechable, se restauró en `vintrack_sprint08_backup_restore` y pasaron consultation, case/folio/status, event, outbox, Portal, document, file, file hash, FK y ownership authorization basis. Resultado: **VERIFIED LOCALLY**, no production.

### Integrity diagnostics

En `vintrack_dev`, restore e idempotency disposable: cero folios duplicados, operation keys duplicadas/incompatibles, orphan evidence/events/outbox/deliveries, estados imposibles, deadlines inválidos, abiertos vencidos, VIN activos incompatibles y consultation→case duplicado.

### PG-05

`OPEN — OWNER RISK/ARCHITECTURE DECISION REQUIRED`. Opciones: scanner local previo, API con cuarentena, revisión administrativa o aceptación formal de riesgo. Recomendación: cuarentena + scanner verificable antes de habilitar evidencia productiva; no se instaló dependencia.

## 53. PG-03 Performance Remediation

### Baseline before y root cause

Dataset aislado reproducible: 200 users, 100,000 consultations, 10,000 notification cases y 10,000 events. Baseline del Owner: Cliente ~95 ms, Admin ~195 ms, filtro por Cliente ~2.21 s y búsqueda global derivada >180 s/timeout. El diagnóstico aisló: conteos sobre la proyección enriquecida; resolución correlacionada repetida de case/event; OR global sobre campos base/derivados; `LIKE %term%`; y el orden anterior del índice de placa, que hacía estimar ~99,246 filas por lookup.

### Query redesign

- `recordsTotal` y filtros base se cuentan directamente en `consultations`.
- autorización, fecha, robo y Cliente se aplican antes de paginar IDs.
- búsqueda base y derivada generan un conjunto SQL de candidatos; el conjunto derivado aplica 3/30/90, origin date, cierre/validación y `NOT EXISTS` para descartar un case histórico superado.
- términos `VALIDADO`, `RECHAZADO` y `PENDIENTE` se mapean a predicates de dominio; `SI/NO` no se sobrecargan porque son ambiguos entre dos columnas.
- sólo los IDs de página se enriquecen; evento submit usa el índice timeline por case.
- orden VIN combina el valor base VIN/NIV con el VIN histórico resuelto para placa antes de paginar, sin aproximación ni cache.

### Indexes Before/After

El índice previo `(provider_service_id, criterio, normalized_value, created_at, id)` dejó el lookup de placa con estimación ~99k y ordenación >15 s. Se reemplazó por `(provider_service_id, normalized_value, criterio, created_at, id)` y se añadió `(normalized_value, created_at, id)`. EXPLAIN After: placa 20 filas, candidato VIN 20, event 1; ordering/pagination 1.71 s en la corrida Before/After. Migration reversible `up → down → up`: PASS. Costo: dos índices secundarios adicionales durante escrituras y storage.

### Timings after

P50 de tres ejecuciones: Cliente 54.831 ms; Admin 136.343 ms; filtro Cliente 94.548 ms; búsqueda derivada 1,447.694 ms; placa 1,646.462 ms; status 2,225.898 ms; orden VIN/página offset 5,000 2,039.715 ms. Theft filter: 153.076 ms en la corrida consolidada. Ningún escenario o statement medido excedió 5 s y no hubo timeout.

### Regression y portabilidad

Las regresiones cubren origin, OWN/OTHER/NO_CASE, validated other, >90 days/previous case, VIN_NOT_AVAILABLE, múltiples cases, no retroactive association, CLOSED con/sin submit, Status Validado, actions, aislamiento Cliente, filtro Admin, búsqueda status histórica y orden VIN derivado. Se usan SQL estándar compatible y construcciones disponibles en MySQL 8.4/MariaDB 10.6 (`UNION`, `NOT EXISTS`, `DATE_ADD`, índices BTREE); `STRAIGHT_JOIN`/hints sólo se emiten para el driver MySQL, soportados también por MariaDB. PG-01 continúa BLOCKED_EXTERNAL: no se inventa ejecución MariaDB.

**PG-03 = CLOSED**

READY FOR OWNER REVIEW
