# VINTrack — Deployment Runbook (Neubox/cPanel)

Este documento es un procedimiento futuro. No autoriza deployment ni contiene rutas, credenciales o valores productivos.

1. Confirmar aprobación del Sprint y autorización productiva escrita; cerrar Gates críticos.
2. Decidir ventana/mantenimiento, responsables, criterio de aborto y canal de coordinación.
3. Generar backups consistentes de DB, evidence storage y código/config; registrar timestamp, tamaño, hash y versión.
4. Construir artifact local desde commit aprobado. Ejecutar `composer install --no-dev --prefer-dist --optimize-autoloader` en plataforma compatible y verificar requisitos PHP/extensiones. Incluir `vendor/` si Composer no está disponible en hosting.
5. Verificar suite, Pint y `git diff --check`; crear manifest/hash del artifact.
6. Subir por SFTP o File Manager a ubicación de staging/release definida por el Owner. No sobrescribir la release activa sin rollback preparado.
7. Configurar sin documentar secretos: `APP_ENV`, `APP_DEBUG=false`, `APP_URL`, `APP_KEY`, `DB_*`, `MAIL_*`, `FILESYSTEM_*`, timezone y configuración notification/outbox.
8. Verificar estrategia de migrations permitida por cPanel. No exponer endpoints genéricos Artisan/SQL. Confirmar backup y ejecutar sólo las migrations aprobadas con mecanismo posteriormente autorizado.
9. Configurar storage privado, ownership/permisos, capacidad, logs/cache y ausencia de acceso HTTP directo; ejecutar prueba de upload/download autorizado.
10. Validar cache sin asumir consola. Si se autoriza Artisan por cPanel, probar versión PHP, working directory, env y permisos antes de optimizar.
11. Configurar Cron sólo tras autorización. Candidatos: `notifications:process-outbox`, `notifications:queue-deadline-reminders`, `notifications:auto-close`. Registrar salida fuera de webroot, timeout y solapamiento.
12. Configurar SMTP/TLS/remitente sin revelar credenciales; verificar SPF/DKIM/DMARC, límites y rebotes.
13. Smoke tests: login/OTP controlado, consulta fake/no facturable si existe, historial cliente/admin, caso/evidencia autorizado, Portal, email de prueba aprobado y comandos discretos.
14. Verificar logs, outbox fallido, auto-close summary, storage errors y errores 4xx/5xx sin stack trace público.
15. Activar release sólo si todos los checks pasan. Ante trigger de rollback, seguir `ROLLBACK-RUNBOOK.md`.

## Tabla Cron pendiente de verificación

| Command | Purpose | Frecuencia recomendada | Máximo | Overlap | Log | Verified production |
|---|---|---|---|---|---|---|
| `notifications:process-outbox` | delivery | cada minuto | <50 s | lease DB | ruta por definir | NO |
| `notifications:queue-deadline-reminders` | reminders | cada 15 min | acotado | dedup DB | ruta por definir | NO |
| `notifications:auto-close` | cierre 30 días | cada 15 min | acotado | CAS/idempotencia | ruta por definir | NO |

No se afirma ninguna ruta PHP o ruta absoluta del proyecto hasta verificarla en cPanel.

## Estrategia definitiva de migrations

### Promoción canónica de la base Laravel

- `vintrack_system_db` (11 tablas) es exclusivamente legacy PHP y debe permanecer intacta; no recibe bundle, schema delta ni migrations Laravel.
- `vintrack_dev` (staging Laravel) tuvo 31 tablas como baseline del bundle 01–06. Con backup autorizado y datos preservados, el Owner ya ejecutó una vez la reconciliación a 44 tablas y validó el target desde `dev.vintrack.com.mx`; no volver a ejecutar el bundle.
- Sólo después de esa validación se crea la futura `vintrack_app` como clon completo de `vintrack_dev` (schema, datos, índices, constraints, ledger y referencia/configuración requerida). No crear `vintrack_app` vacía ni desde `vintrack_system_db`.
- El bundle es apply-once con precheck estricto; no aplicar de manera independiente la misma transformación a una `vintrack_app` vacía.

- Instalación nueva: una DB vacía carga `database/schema/mysql-schema.sql` mediante `php artisan migrate`; localmente produjo 43 tablas, 53 FK y 54 migrations registradas. Importar después un dataset de configuración/referencia explícitamente aprobado; el schema dump no contiene datos de negocio.
- Entorno existente: no cargar el schema dump; aplicar únicamente migrations incrementales posteriores a su batch actual.
- Migrations anteriores al baseline: forward-only. No ejecutar rollback global por las deudas `adapter_code`, reestructura irreversible y `label/icon`.
- Rollback seguro: sólo migration incremental identificada, reversible, sin dependencias posteriores y con backup validado.
- Si una migration de datos/destructiva falla tras modificar estado: restaurar el backup consistente o crear forward-fix aprobado; nunca improvisar un `down()`.

### Evidencia de ejecución staging 2026-08-15

El bundle aprobado 01–06 fue ejecutado una vez en `vintrack_dev` por el Owner mediante phpMyAdmin, con backup privado verificable previo. PRECHECK pasó el baseline 31 / ledger 11-batch 2 y POSTCHECK pasó el target 44 tablas, 462 columnas, 147 índices, 54 FKs y ledger 55-batch 4, sin objetos faltantes ni anomalías agregadas. Esta evidencia corresponde exclusivamente a staging. No repetir 02–05: el modelo sigue siendo apply-once con precheck estricto. Antes de cualquier clon a `vintrack_app` se requiere validación completa de la aplicación desplegada y nueva autorización del Owner.

La validación autenticada posterior del Owner pasó para sesión/logout, roles, Client/Admin histories, DataTables/filtro cliente, Proceso de Notificaciones, Evidence sin upload y Portal Notifications. Staging queda como `GOLDEN STAGING CANDIDATE`, no como producción autorizada. Cualquier backup/clon a `vintrack_app` o corte de producción requiere una autorización independiente y procedimiento revisado.

## Checkpoint autorizado: staging code deployment (sin DB)

Este procedimiento aplica exclusivamente al staging Laravel `dev.vintrack.com.mx`, con application root `public_html_dev` y Document Root `public_html_dev/public`. No aplica a `/public_html`, `vintrack.com.mx`, `vintrack_system_db`, `/vintrack_app` ni `vintrack_app`.

1. En cPanel File Manager, situarse en el directorio de cuenta, no en el Document Root. Crear una ubicación privada para respaldos, por ejemplo `staging-code-backups`, si no existe. No usar `public_html`, `public_html_dev/public` ni ninguna ruta web pública.
2. Comprimir el árbol actual completo `public_html_dev` en un archivo fechado dentro de esa ubicación privada. El respaldo debe abarcar `.env` y `storage/app/private`. Registrar nombre, fecha/hora, tamaño y confirmar que el archivo aparece en File Manager; no descargar ni abrir `.env`.
3. Si File Manager no permite crear/verificar dicho archive privado, detenerse: no sustituir código sin ese rollback recuperable.
4. Subir `vintrack-staging-07f0921-20260814.zip` a una ubicación privada temporal fuera de `public_html_dev/public`. Confirmar tamaño y SHA-256 local `385ab579af0eb2ed70b3deed9affc66602ef7bef615bf4aeed241a69fe2eda2f` antes de extraerlo. El ZIP no debe permanecer en una ubicación pública.
5. Extraer el ZIP en un directorio candidato privado. No extraer encima de la aplicación activa. Verificar que existan `artisan`, `vendor/autoload.php`, `public/`, `public/vendor/vintrack/` y el asset manifest.
6. Hacer el cambio de árbol sólo conservando el anterior/archivo de respaldo para rollback y preservando sin modificación el `.env` existente y todo `storage/`, en especial `storage/app/private`. No copiar archivos locales de `storage`, no subir un `.env` local y no ejecutar Artisan, SQL, migrations, cambios de permisos ni cache commands.
7. Después del cambio, comprobar únicamente `https://dev.vintrack.com.mx/` y assets locales críticos. Si hay fallo de syntax/autoload/dependencias/boot, restaurar el árbol anterior desde el respaldo y volver a comprobar el sitio. No tocar `vintrack_dev` en ninguno de los dos casos.

La dependencia histórica de esquema pre-migration quedó resuelta con la reconciliación validada a 44 tablas. Un objeto faltante en el target actual es un bloqueo que exige detenerse y revisar evidencia; no se corrige manualmente ni convierte este paso de código en una migration de DB.

## Preparación de release: CR-004 Provider Result Assessment

CR-004 está cerrado para implementación y validación local. Esta sección prepara un release futuro; no autoriza upload, activación, SQL, migrations, Cron, SMTP ni consultas facturables.

1. Partir exclusivamente de un commit aprobado que incluya la evaluación normalizada, los adaptadores de PlacasInfo/VINData, la respuesta de consulta y su documentación. Registrar commit, fecha, tamaño y SHA-256 del artifact. No reutilizar el nombre, hash ni ZIP de checkpoints históricos.
2. Construir el artifact en local o CI compatible con PHP 8.3. Incluir el `vendor/` verificado y los assets versionados requeridos por el release; el hosting no debe requerir Composer, Node ni NPM. No incluir `.env` ni contenido local de `storage/`.
3. Antes de cualquier upload, ejecutar y registrar `php artisan test`, Pint sobre PHP afectado y `git diff --check`. Para CR-004 la referencia local aceptada es 130 tests / 605 assertions / 0 failures; una nueva ejecución debe registrar su resultado real, no reutilizarlo por suposición.
4. Verificar que el artifact no contiene credenciales, dumps, logs, evidence, archivos temporales ni el ZIP de distribución dentro de una ruta pública. Preparar un manifest que enumere los archivos de release y sus hashes.
5. Confirmar que la DB destino ya tiene la estructura/ledger aprobados para el release aplicable. CR-004 no requiere migration, schema delta, backfill, reescritura de `consultations` ni cambio de configuración de provider. No ejecutar los bundles SQL 01–06 por este cambio.
6. Preservar íntegros `.env` y `storage/` del destino, incluido `storage/app/private`; no cambiar secretos, endpoints, credenciales, cache, permisos ni configuración de providers como parte de CR-004.
7. Definir rollback antes de sustituir archivos: restaurar el árbol/artifact previo completo y conservar el artifact nuevo para análisis. No borrar ni reescribir consultas creadas durante el intervalo; cualquier snapshot normalizado ya persistido es evidencia histórica y no se elimina como rollback.
8. Tras una futura activación autorizada, validar primero login y navegación sin ejecutar una consulta real. Luego comprobar, con datos preexistentes autorizados o una prueba separadamente aprobada y no facturable, que un resultado histórico/no calificable no abre expediente y que un resultado calificable conocido conserva el flujo de caso. No inferir esta evidencia de colores/texto genérico del reporte.
9. Si una consulta real, SMTP, Cron, upload o cambio de provider fuese necesario para completar la validación, detenerse y solicitar autorización específica. CR-004 no amplía dichas autorizaciones.

### Checklist de aceptación de release CR-004

| Control | Evidencia requerida | Estado actual |
|---|---|---|
| Clasificación por `service_code` | Artifact aprobado incluye `placas_service` y `nmvtis_plus`; no depende de ID/key/name mutable | VERIFIED LOCALLY |
| Falso positivo CARFAX | `data.robo=false` no produce alerta roja ni expediente | VERIFIED LOCALLY |
| NMVTIS histórico | `Recovered Theft` no crea caso, evento ni intent de outbox de caso | VERIFIED LOCALLY |
| NMVTIS activo | `Active Theft` conserva creación idempotente de caso e intent Portal existente | VERIFIED LOCALLY |
| Datos | No migration/backfill; raw response y snapshot quedan auditables | VERIFIED LOCALLY |
| Entorno destino | Artifact, paths, permisos, DB objetivo y smoke del hosting | PENDING OWNER AUTHORIZATION |
| Provider real | Llamadas facturables o verificación contractual en runtime | PENDING OWNER AUTHORIZATION |
