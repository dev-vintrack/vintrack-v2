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
