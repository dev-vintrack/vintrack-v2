# Prompt para iniciar VINTrack SPRINT-02 en Codex

Copia `SPRINT-02-CORE-DOMAIN-PERSISTENCE.md` a:

```text
C:\laragon\www\vintrack-v2\docs\sprints\SPRINT-02-CORE-DOMAIN-PERSISTENCE.md
```

Después abre una nueva tarea de Codex en la raíz `C:\laragon\www\vintrack-v2` y utiliza este prompt:

---

Ejecuta formalmente **VINTrack — SPRINT-02: Core Domain, Persistence & Consultation Admission**.

El Project Owner autoriza este Sprint de implementación local y pruebas conforme al documento rector:

```text
docs/sprints/SPRINT-02-CORE-DOMAIN-PERSISTENCE.md
```

Antes de actuar:

1. Lee completamente `AGENTS.md`, toda la documentación obligatoria indicada por el documento rector y los resultados de SPRINT-00 y SPRINT-01.
2. Confirma el repositorio, branch/worktree, estado Git, conexión de testing y versiones locales sin exponer secretos.
3. Ejecuta primero el preflight documental autorizado para armonizar `AGENTS.md` con las decisiones ya aprobadas de SPRINT-01.
4. Relee `AGENTS.md` después de corregirlo.
5. Inspecciona el código, migrations, esquema local, tests y adapters reales antes de crear archivos.
6. Presenta un inventario inicial de componentes afectados y luego continúa autónomamente mientras no exista una condición de parada contractual.

Alcance autorizado:

- migrations nuevas y aditivas ejecutadas únicamente en local/testing;
- dominio, persistencia y servicios de aplicación del expediente;
- admission guard antes de crédito/API;
- guards, reservas, locks, folios e idempotencia;
- creación/reutilización y reglas 3/30/90;
- VIN mappers y excepción por placa;
- asignación única de VIN y detección de conciliación;
- máquina de estados y submit sin UI;
- auditoría append-only y outbox sin entrega;
- autorización del núcleo;
- pruebas unitarias, integración, concurrencia, seguridad, regresión y portabilidad disponible;
- documentación y `docs/sprints/SPRINT-02-RESULT.md`.

Queda expresamente prohibido:

- modificar o desplegar producción;
- ejecutar migrations o SQL en MariaDB productivo;
- implementar portales, DataTables o formularios finales;
- implementar uploads/downloads;
- enviar emails o notificaciones de portal;
- configurar Cron, Scheduler o endpoint HMAC;
- crear expedientes retroactivos;
- implementar purga;
- cambiar `consultations.provider_service_id`;
- usar `vehicles` como master;
- iniciar el siguiente Sprint.

No utilices `migrate:fresh`, `db:wipe`, truncate o resets destructivos sobre la BD local principal. Las pruebas destructivas de migrations deben usar una base de testing desechable y verificada.

Cumple íntegramente las pruebas y criterios de aceptación del documento rector. No declares pruebas de concurrencia o MariaDB como ejecutadas si el entorno no permitió realizarlas; informa la limitación con precisión.

Al concluir:

- genera `docs/sprints/SPRINT-02-RESULT.md` con la estructura obligatoria;
- actualiza `PROJECT_STATE.md` conforme a la gobernanza;
- reporta archivos, migrations, operaciones locales y resultados exactos de pruebas;
- confirma que producción y los componentes fuera de alcance no fueron modificados;
- termina con `READY FOR OWNER REVIEW`;
- detente sin iniciar el siguiente Sprint.

Inicia ahora SPRINT-02.
