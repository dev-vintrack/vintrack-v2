# VINTrack — Production Readiness Report

## Current execution addendum — Staging database 31 → 44 complete

The Owner executed the unchanged approved SQL bundle 01–06 manually through phpMyAdmin against `vintrack_dev` only. The mandatory private staging DB backup was verified before mutation. PRECHECK passed the exact MariaDB 10.6.27 / 31-table / 11-ledger-entry baseline. The additive schema, indexes, configuration/reference data and ledger reconciliation completed in order. POSTCHECK passed the approved target: **44 tables, 462 columns, 147 indexes, 54 foreign keys, 55 ledger entries, max batch 4, zero missing target objects, zero invalid configuration rows, zero null normalized values, zero duplicate case numbers and no duplicate migration rows**. No legacy DB, production DB, `vintrack_app`, migration Artisan command, restore, provider, SMTP or Cron operation occurred.

This completes the schema transition only. It does not authorize production, mark staging golden, close PG-06, create `vintrack_app`, or replace the required staging application validation. Detailed reproducible evidence is in `docs/production/verification/STAGING-DB-MIGRATION-31-TO-44-EVIDENCE.md`.

## Current execution addendum — Golden Staging Candidate

The Owner subsequently completed the authorized authenticated staging validation against the migrated `vintrack_dev` target. Login/OTP/session/logout, role-appropriate menus, Client Portal, history/DataTables, notification-process/Evidence views without upload, Portal Notifications, Admin Global History and client filtering all passed. The evidence was handled without recording OTP, credentials or private values. No provider call, credit-consuming consultation, SMTP, Cron, file upload, malware scan/provider, irreversible case action or production/legacy action occurred.

**GOLDEN STAGING CANDIDATE — READY FOR OWNER REVIEW.** This status means only that current approved code + PHP 8.3.32 + staging target schema + authorized functional validation are coherent. Production Readiness remains **READY WITH CONDITIONS**, PG-06 remains **PARTIALLY VERIFIED — FINAL LAYOUT/STORAGE VERIFICATION REQUIRED**, Production Authorization remains **NOT AUTHORIZED**, and deployment to production remains **NOT EXECUTED**.

## Current execution addendum — Class B Staging Code Deployment Prepared

The Owner authorized a staging-code-only deployment to `dev.vintrack.com.mx` / `public_html_dev`. Local release verification selected `develop` commit `07f0921440726c968c0460304d95bc9b46d600c4` (`07f0921`): PHP 8.3.30, `composer validate` valid (with the existing non-blocking exact-version warning), `composer check-platform-reqs --no-dev` PASS, and **116 tests / 545 assertions / 0 failures**.

The reproducible complete Laravel artifact is `vintrack-staging-07f0921-20260814.zip`, 67,389,846 bytes (64.27 MiB), 11,827 entries, SHA-256 `385ab579af0eb2ed70b3deed9affc66602ef7bef615bf4aeed241a69fe2eda2f`. It includes the locked `vendor/` tree and the approved self-hosted frontend asset set; it excludes local `.env`, `storage/`, SQL/dumps, logs, caches, test artifacts, Git metadata and deployment archives. The exact include/preserve/exclude rules and rollback classification are in `docs/production/deployment/STAGING-DEPLOYMENT-MANIFEST.md`.

The Owner completed the hosting File Manager steps and supplied evidence: the ZIP was uploaded/extracted, the active staging `.env` and `storage/app/private` were preserved, and the temporary ZIP was removed from hosting. The recoverable local backup `backup-vintrack.com.mx-8-14-2026.tar.gz` is readable (205.28 MiB; SHA-256 `94f03fbecd667a13e8f3a8bbab548f1ce44500575f2ce5131f607a444893aa2c`) and includes the protected staging paths. It also contains legacy-tree content and must remain private. Public read-only verification of `https://dev.vintrack.com.mx/` rendered the expected Laravel home page with no visible boot failure or browser-console warning/error; Bootstrap is served from its approved local `/vendor/vintrack/` path. Direct browser inspection of standalone minified JS was client-blocked, not a hosting error.

No SQL, migration, Artisan command, DB access, `.env` change, storage probe or other write action was performed by Codex during that staging-code checkpoint. At that historical point `vintrack_dev` still held the 31-table pre-migration baseline. The later, separately authorized Owner execution of bundle 01–06 completed the recorded 31→44 transition above; it must not be repeated. Neither checkpoint is production deployment, and neither by itself grants production authorization.

Fecha: 2026-08-14
Alcance: SPRINT-08 local
Production Authorization: **NOT AUTHORIZED**
Owner decision: **SPRINT-08 — APPROVED WITH OBSERVATIONS**
Approval date: **2026-08-14**
Local Production Hardening Gate: **APPROVED WITH OBSERVATIONS — DEC-047**
PG-01 closure: **CLOSED — VERIFIED FOR SCHEMA COMPATIBILITY — DEC-048**

## 1. System Baseline

Laravel 12.62.0, PHP 8.3.30, MySQL 8.4.3, `America/Mexico_City` como timezone contractual del módulo. Baseline previo: 104 tests, 449 assertions, 0 fallos. Mail local usa el driver `log`; `fileinfo`, GD, OpenSSL, mbstring y PDO MySQL están disponibles localmente. Esto no acredita Neubox.

## 2. Sprint Coverage

SPRINT-01 a SPRINT-07 se mantienen contractuales. SPRINT-08 añadió idempotencia persistente request→consultation, clasificación non-retryable de destinatario inválido y documentación operativa. No se ejecutó deployment, Cron real, SMTP real ni acceso a producción.

## 3. Production Gate Matrix

| Gate | Estado | Evidencia / condición |
|---|---|---|
| PG-01 MariaDB 10.6.27 | CLOSED — VERIFIED FOR SCHEMA COMPATIBILITY | Rehearsal real 10.6.27 portable: baseline 31/11/2, bundle 01–06 PASS, target 44/462/147/54, ledger 55/4 y compatibilidad Laravel read-only PASS. DEC-048. |
| PG-02 idempotencia request→consultation | CLOSED | MySQL multiproceso: misma key produjo provider=1, debit=1, consultation=1 y operation=1; keys distintas produjeron 2/2/2/2. |
| PG-03 EXPLAIN con volumen | CLOSED | Dataset 100k/10k/10k; escenarios medidos <5 s, búsqueda sin timeout y EXPLAIN Before/After reproducible. |
| PG-04 deuda histórica migrations | CLOSED | Bootstrap `database/schema/mysql-schema.sql` probado en DB vacía (43 tablas/53 FK/54 migrations); historia previa declarada forward-only. |
| PG-05 malware scanning | IMPLEMENTED LOCALLY — PROVIDER SELECTION AND PRODUCTION VERIFICATION REQUIRED | Quarantine/fail-closed, estados, processor concurrent-safe, retry acotado y fake probados localmente. Sin provider real; no está CLOSED. |
| PG-06 PHP runtime/extensions/private storage | PARTIALLY VERIFIED — FINAL LAYOUT/STORAGE VERIFICATION REQUIRED | PHP global y web efectivo 8.3.32, extensiones requeridas, límites publicados y respuesta HTTPS básica verificados. Layout Laravel productivo, writability privada/upload temporal y valores no expuestos siguen pendientes. |
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
| PG-18 DataTables CDN/CSP | CLOSED LOCALLY | Assets críticos self-hosted con manifest/hash; DataTables 1.13.6 preservado; regresión y smoke offline verdes. CSP productiva no activada. |
| PG-19 autorización producción | DEFERRED_BY_OWNER | Producción explícitamente no autorizada. |
| PG-20 APP_DEBUG/config segura | BLOCKED_EXTERNAL | Checklist preparado; valores productivos no verificados. |
| PG-21 secrets/log sanitization | CLOSED | Auditoría formal sin secretos reales versionados; excepción/provider/OTP/mail sanitizados y regresión verde. |

## 4. Closed Gates

PG-01, PG-02, PG-03, PG-04, PG-07 y PG-21 quedaron cerrados con evidencia reproducible dentro del alcance de cada Gate. PG-01 acredita compatibilidad de esquema sobre MariaDB 10.6.27 real; no acredita operación Linux/Neubox ni duración sobre filas productivas.

## 5. Open / Decided Gates

PG-05 está implementado localmente pero no CLOSED hasta seleccionar/aprobar provider y verificar integración productiva. PG-18 está CLOSED LOCALLY; activación CSP y verificación del artifact en el entorno real permanecen condiciones externas.

## 6. External Blockers

PG-06 está parcialmente verificado: la remediación autorizada acreditó PHP web 8.3.32, extensiones, límites visibles y respuestas HTTPS, pero el layout Laravel final y la writability/storage privada siguen sin verificar. Permanecen además Neubox/cPanel para las capacidades restantes, Cron, SMTP/TLS de aplicación, DNS de correo, límites y rebotes. MariaDB 10.6.27 ya no es blocker de compatibilidad de esquema; sus riesgos con datos/tráfico reales se gestionan como riesgos operativos de deployment.

## 7. Security Assessment

La suite conserva pruebas negativas de auth, roles, IDOR, mass assignment, uploads/downloads, MIME, límites, XSS y parámetros DataTables. La nueva identidad idempotente se vincula a usuario, servicio, criterio, valor y servicios solicitados; la misma key con otro payload se rechaza.

## 8. Database Assessment

MySQL 8.4.3 ejecutó la migration `consultation_operations` y su ciclo reversible. La tabla usa hashes SHA-256, unique `(user_id,idempotency_key_hash)`, FK restrictivas y estados explícitos. El bundle reconciliado también pasó sobre MariaDB 10.6.27 real y PG-01 quedó cerrado mediante DEC-048.

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
- Falta seleccionar/aprobar e integrar un provider antimalware real; el Gate local sólo usa fake.
- Cadena histórica no reconstruible/reversible de extremo a extremo sin remediación.
- Riesgos operativos de deployment: duración sobre filas productivas, metadata locks, backfill de `normalized_value`, construcción de índices, espacio temporal/disco, tráfico concurrente y diferencias operativas Linux/Neubox.

## 16. Required Production Verification

Ejecutar todos los Gates `BLOCKED_EXTERNAL` sólo bajo autorización posterior y registrar evidencia, sin secretos.

PG-06 requiere primero una autorización separada para cambiar el dominio objetivo a PHP 8.3 y confirmar manualmente el runtime efectivo y sus extensiones. Después de existir el layout Laravel final, writability/private HTTP denial requerirá un Class B disposable probe separado si no puede acreditarse de otra forma.

## 17. Recommended Deployment Sequence

No desplegar. Resolver provider/integración productiva de PG-05 y verificar artifact/CSP de PG-18 sólo bajo autorizaciones separadas; después atender los Gates externos.

## 18. Production Readiness Verdict

**READY WITH CONDITIONS**

Interpretación: PG-01 quedó cerrado para compatibilidad de esquema, PG-05 quedó implementado con fake y PG-18 cerrado localmente, pero faltan provider/integración real de malware, CSP/artefacto objetivo y verificaciones `BLOCKED_EXTERNAL`. No significa `READY FOR DEPLOYMENT`, Neubox/SMTP verificados ni producción autorizada.

Production Authorization: **NOT AUTHORIZED**
Deployment: **NOT EXECUTED**

## 19. Owner Closure Observations

- `OBS-08-01`: DEC-045 fue implementada localmente con fake; PG-05 permanece no CLOSED y sin provider seleccionado/verificación productiva.
- `OBS-08-02`: DEC-046 fue implementada y PG-18 quedó CLOSED LOCALLY; CSP y artifact productivos no fueron verificados.
- `OBS-08-03`: históricamente PG-01, PG-06, PG-08–PG-17 y PG-20 permanecían BLOCKED_EXTERNAL al cierre de SPRINT-08; DEC-048 cerró posteriormente PG-01 para compatibilidad de esquema. Los demás conservan su estado.
- `OBS-08-04`: estado inicial y evidencia final de remediation quedaron armonizados en el RESULT.

## 20. PG-05 Approved Architecture

DEC-045 establece **QUARANTINE-FIRST + MALWARE SCANNING + FAIL-CLOSED**. Toda evidencia nueva inicia en cuarentena privada; upload exitoso no implica limpieza. Estados mínimos equivalentes: `PENDING`, `SCANNING`, `CLEAN`, `INFECTED`, `ERROR`. Sólo `CLEAN` puede usarse normalmente; los demás estados bloquean uso y `SUBMIT`/`RESUBMIT` server-side. Error de scanner nunca se convierte en CLEAN e INFECTED no se elimina automáticamente.

El scanner se ubicará detrás de una abstracción Application/Domain con adapter en Infrastructure, retries acotados y auditoría. SHA-256 puede apoyar cache, no confianza permanente. SPRINT-03 conserva storage/authorization privado, sin URLs públicas, bypass de Evidence o servicios públicos que compartan muestras. Provider: **NOT SELECTED / NOT APPROVED**. Implementación, tests e integración productiva siguen pendientes.

## 21. PG-18 Approved Architecture

DEC-046 establece **SELF-HOST CRITICAL FRONTEND ASSETS**. DataTables y las extensiones utilizadas serán parte del artifact versionado y producción no dependerá de `cdn.datatables.net`. La migración conservará DataTables `1.13.6`; no se aprobó upgrade.

Antes de implementar se inventariarán DataTables, jQuery, Bootstrap, icon libraries, fonts, JavaScript y CSS externos. Los assets críticos deberán versionarse, tener versión explícita o package lock equivalente, construirse localmente, funcionar sin CDN y no requerir Node/NPM/Composer frontend en producción. No habrá fallback silencioso al CDN.

La CSP futura se basará en el inventario, minimizará origins, preferirá `'self'` y documentará excepciones a `unsafe-inline`/`unsafe-eval`; no se fija aún una política productiva. Los externos deliberadamente conservados evaluarán SRI. Para assets locales, integridad mediante source control, commit aprobado, build reproducible, manifest/hash y verificación de deployment.

PG-18 quedó CLOSED LOCALLY mediante el Local Production Hardening Gate: inventario, migración local, eliminación de CDN crítico, regresión, smoke offline y assessment CSP completados. CSP productiva no fue activada.

## 22. Local Production Hardening Evidence

PG-05: schema aditivo reversible, cuarentena/fail-closed, scan history, claim por fila, máximo tres intentos y comando discreto. Multiproceso MySQL sobre un documento: scanner fake=1, attempts=1, final=CLEAN. No existe provider productivo; PG-05 no está CLOSED.

PG-18: jQuery 3.7.1, Bootstrap 5.3.2, Bootstrap Icons 1.11.2, DataTables 1.13.6 + Buttons/Responsive, JSZip 3.10.1 y pdfmake 0.2.7 forman parte del artifact local con manifest SHA-256. Smoke de navegador inicializó DataTables/paginación sin CDN. CSP sólo fue evaluada, no activada.

## 23. Local Production Hardening Gate Closure

DEC-047 registra el Gate como `APPROVED WITH OBSERVATIONS`.

- `OBS-LPH-01`: PG-05 permanece `IMPLEMENTED LOCALLY — PROVIDER SELECTION AND PRODUCTION VERIFICATION REQUIRED`, no CLOSED. Fake/Test valida el workflow, no scanning productivo; provider, revisión contractual/privacidad, adapter, tests e integración/verificación real siguen pendientes.
- `OBS-LPH-02`: PG-18 está `CLOSED LOCALLY` por inventario, self-hosting, eliminación de dependencias CDN críticas y smoke offline. CSP productiva, inline scripts/styles y artifact/hash objetivo siguen pendientes.
- `OBS-LPH-03`: el scaffold `welcome` no utilizado referencia Bunny Fonts; debe excluirse del routing/artifact productivo o limpiarse antes de activar CSP.

Quality accepted: baseline 112/496/0; final 116/545/0; migration up/down/up, Pint afectados y `git diff --check` PASS. Production Environment Verification está en curso exclusivamente bajo autorizaciones separadas; PG-01 está cerrado mediante DEC-048 y los demás Gates no cambian por inferencia.

## 24. PG-01 Formal Closure

DEC-048 registra `PG-01 — CLOSED — VERIFIED FOR SCHEMA COMPATIBILITY`. DEC-049 aclara que el baseline de 31 tablas/11 migrations/batch 2 es `vintrack_dev`, la base Laravel de staging; `vintrack_system_db` es una base legacy distinta de 11 tablas y no forma parte del bundle. Se aceptan MariaDB `10.6.27-MariaDB`, los seis SQL con hashes intactos, target 44 tablas/462 columnas/147 índices/54 FKs, ledger 55 migrations únicas (batch 3 = 34; batch 4 = 10), ocho defaults y compatibilidad Laravel read-only. El modelo de deployment es `APPLY-ONCE BUNDLE WITH STRICT PRECHECK`.

La promoción aprobada es `vintrack_dev` 31→44 con datos preservados → validación completa en `dev.vintrack.com.mx` → clon completo a la futura `vintrack_app`. Las dos primeras etapas ya fueron ejecutadas y registradas exclusivamente en staging; no se crea una producción vacía ni se aplica el bundle dos veces de forma independiente. El clon a `vintrack_app` y toda operación de producción siguen sin ejecutarse.

Las migrations históricas permanecen forward-only y requieren backup/restore o forward-fix. Duración real, metadata locks, backfill, index-build, disco/temporales, tráfico concurrente y Linux/Neubox son `DEPLOYMENT OPERATIONAL RISKS`, no motivos para reabrir PG-01. Production Authorization permanece `NOT AUTHORIZED`; Deployment `NOT EXECUTED`.

## 25. PG-06 Manual Evidence Review

Owner-supplied cPanel and browser evidence verifies PHP 8.2 selection/effective 8.2.32, the required extension set for that PHP family, 512M memory, 128M upload/post limits, `file_uploads=ON`, development private-path separation, approximately 47.7 GiB free capacity, AutoSSL/TLS coverage, effective HTTPS and several baseline security headers.

The authorized cPanel remediation changed Account Global PHP from 8.2 to 8.3. Both domains now return HTTPS `200 OK` and expose effective `PHP/8.3.32`; the Laravel site renders normally. Required extensions and visible limits were reconfirmed without manual extension/option changes. PG-06 is **PARTIALLY VERIFIED — FINAL LAYOUT/STORAGE VERIFICATION REQUIRED**. The final production Laravel public-root/private-storage layout, web-handler writability, private HTTP denial, multipart behavior, `max_file_uploads`, `default_socket_timeout`, `upload_tmp_dir` and SAPI remain unknown. No deployment occurred.

## 26. CR-004 Provider Result Assessment Closure

CR-004 is **APPROVED — IMPLEMENTED LOCALLY — OWNER LOCAL VALIDATION ACCEPTED**. It corrects qualification for immutable `provider_services.service_code` values `placas_service` and `nmvtis_plus` without schema, migration, backfill, provider configuration or delivery-mechanism changes. The classification is versioned and fail-closed: only `ACTIVE_QUALIFYING` may project `alerta_robo` and enter the existing notification-case/outbox path.

Local evidence: versioned contract fixtures, integration regressions for `Recovered Theft` (no case/event/outbox) and `Active Theft` (normal case/Portal intent), a CARFAX `data.robo=false` banner regression, local visual validation accepted by the Owner, and full suite **130 tests / 605 assertions / 0 failures**. No billable provider request, staging deployment or production action occurred.

The release preparation is documented in `DEPLOYMENT-RUNBOOK.md`. It is not production authorization and does not close PG-05, PG-06, PG-08–PG-17, PG-19 or PG-20. Production Readiness remains **READY WITH CONDITIONS**; Production Authorization remains **NOT AUTHORIZED**; Deployment remains **NOT EXECUTED**.
