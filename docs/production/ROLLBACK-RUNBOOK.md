# VINTrack — Rollback Runbook

No ejecutar sin autorización, backup verificado y targets exactos.

## Triggers

Errores 5xx sostenidos, corrupción/integridad, migrations incompletas, pérdida de acceso a evidencia, doble débito/consulta, Cron duplicando efectos o fallo de seguridad.

## A. Código

Desactivar release afectada y restaurar el artifact anterior verificado mediante SFTP/File Manager. Confirmar que config y schema siguen siendo compatibles.

## B. Migrations

No asumir `migrate:rollback`. La migration SPRINT-08 es reversible mientras no haya dependencias posteriores. Para cambios destructivos/de datos o cadena histórica, detener y usar forward-fix o restauración completa aprobada.

## C. Config

Restaurar copia cifrada/versionada de variables sin escribir secretos en logs. Limpiar/reconstruir cache mediante mecanismo cPanel autorizado.

## D. Cron

Deshabilitar únicamente las entradas VINTrack identificadas; conservar captura previa. Verificar leases/outbox antes de reactivar.

## E. SMTP

Deshabilitar delivery email sin afectar Portal cuando sea posible. Restaurar config anterior y conservar outbox/deliveries auditables.

## F. DB

Validar timestamp, tamaño, hash e integridad del backup; restaurar primero en entorno aislado cuando sea posible. La restauración debe corresponder al mismo punto de evidence storage/código.

## G. Evidence storage

Restaurar por manifest/hash preservando rutas privadas, ownership y permisos. No sobreescribir evidencia nueva sin reconciliación y autorización.

## Verificación posterior

Ejecutar smoke tests, diagnósticos de huérfanos/duplicados, revisar logs/outbox, registrar pérdida potencial de datos y mantener producción detenida si la consistencia no está demostrada.

## Forward-fix

Preferir forward-fix cuando rollback borre datos, revierta un esquema ya consumido, rompa compatibilidad con artifacts o la migration histórica tenga `down()` vacío/no seguro.
