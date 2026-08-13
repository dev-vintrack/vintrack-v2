# SPRINT-00 — Descubrimiento y Línea Base de VINTrack v2

**Fecha:** 12 de agosto de 2026  
**Rama inspeccionada:** develop (Laravel 12.62.0)  
**Reglas del sprint:** solo lectura y documentación. Sin modificaciones de código, base de datos, configuración de producción ni ejecución de migraciones destructivas.

---

## 1. Resumen Ejecutivo

VINTrack v2 es una aplicación web Laravel 12 construida con PHP 8.2+ que vende consultas vehiculares a clientes mediante consumo de créditos. Los proveedores actuales son **Placas.info** y **VINData**; cada uno tiene un adaptador propio. El sistema combina un modelo de dominio con repositorios Eloquent, un esquema de roles/permisos dinámico basado en base de datos, wallet de créditos por servicio, paquetes de créditos, inventario global de consultas y notificaciones por correo.

**Hallazgo clave:** la aplicación ya tiene operativa la mayor parte del flujo de consulta, consumo de créditos, generación de reportes HTML/PDF y alertas de robo. El despliegue está condicionado por un hosting compartido sin SSH ni terminal PHP, lo que obliga a generar scripts SQL y subir `vendor/` ya compilado.

---

## 2. Alcance y Metodología

### 2.1 Qué se hizo
- Lectura de archivos de configuración, rutas, modelos, migraciones, controladores, servicios y proveedores.
- Verificación del estado de migraciones mediante `php artisan migrate:status`.
- Inspección del esquema de tablas críticas con `php artisan db:table`.
- Sin alteraciones de código, base de datos ni archivos de configuración.

### 2.2 Qué NO se hizo
- No se ejecutaron migraciones nuevas.
- No se modificaron controladores, modelos, vistas ni jobs.
- No se accedió a producción.
- No se realizaron cambios funcionales.

---

## 3. Stack y Arquitectura General

| Capa | Tecnología | Observación |
|------|------------|-------------|
| Framework | Laravel 12.62.0 | Requiere PHP `^8.2` (`composer.json:7`). |
| Base de datos local | MySQL/MariaDB (`DB_CONNECTION=mysql` en `.env:23`) | Esquema `vintrack_dev`, motor InnoDB, collation `utf8mb4_unicode_ci`. |
| Sesiones | `database` (local) / `file` (hosting) | Local usa tabla `sessions` de Laravel; producción usaría `file`. |
| Colas | `database` | Tabla `jobs` presente; sin worker dedicado en hosting compartido. |
| Caché | `database` (local) / `file` (hosting) | |
| Frontend | Bootstrap 5.3.2 + Bootstrap Icons + DataTables 1.13.6 (CDN) | No hay bundle propio compilado para estas librerías. |
| PDF | `dompdf/dompdf` | Reportes de consulta en HTML y PDF. |
| Redis | Configurado pero no activo en local | En producción tampoco se menciona activación. |

**Arquitectura de carpetas (personalizada):**

- `app/Domain/` → entidades y contratos (repositories, value objects).
- `app/Application/` → casos de uso y servicios de aplicación (consultas, créditos, inventario, notificaciones, roles).
- `app/Infrastructure/` → adaptadores externos, persistencia Eloquent, modelos, notificaciones por correo.
- `app/Presentation/Http/` → controladores y middleware.
- `routes/web.php` y `routes/console.php`.

Referencia: `@c:\laragon\www\vintrack-v2\composer.json:1-77`, `@c:\laragon\www\vintrack-v2\bootstrap\app.php:1-24`, `@c:\laragon\www\vintrack-v2\routes\web.php:1-158`.

---

## 4. Estructura de la Base de Datos

### 4.1 Migraciones ejecutadas

`php artisan migrate:status` reportó **41 migraciones corridas** en batches 1‑10. Las más relevantes:

| Migración | Descripción |
|-----------|-------------|
| `2024_07_06_180000_create_providers_table` | Proveedores (`providers`). |
| `2024_07_06_180001_create_provider_services_table` | Servicios por proveedor (`provider_services`). |
| `2024_07_06_180003_create_user_provider_wallets_table` | Wallets de crédito por usuario/servicio. |
| `2024_07_06_180004_create_wallet_ledger_table` | Movimientos de crédito. |
| `2024_07_07_120001_create_consultations_table` | Consultas realizadas. |
| `2026_07_14_000000_create_vehicles_table` | Vehículos únicos consolidados. |
| `2026_07_17_000000_create_purchase_items_table` | Compras al proveedor. |
| `2026_07_17_000004_restructure_provider_services_for_placas_service` | Reestructura servicios de Placas. |
| `2026_07_21_190000_create_roles_table` | Roles. |
| `2026_07_23_033124_create_role_types_table` | Tipos de rol dinámicos. |
| `2026_07_23_101000_add_status_to_user_packages_and_wallets_tables` | Estados active/expired. |
| `2026_07_27_210000_create_notification_policies_and_deliveries_tables` | Notificaciones. |
| `2026_08_10_210000_add_provider_service_id_to_consultations_table` | Enlace de consulta a servicio concreto. |

Referencia: salida de `@shell:php artisan migrate:status`.

### 4.2 Tablas críticas inspeccionadas

#### `consultations`
- 24 columnas incluyendo `user_id`, `provider_id`, `provider_service_id`, `criterio`, `valor`, `api_id`, `services` (json), `costo_credito`, `success`, `error_message`, `alerta_robo`, flags por proveedor (`repuve_robo`, `pgj_robo`, `ocra_robo`, `carfax_robo`, `rapi_robo`), `flags_json`, `response_json`, `credits_api`.
- FK: `user_id → users.id (cascade)`, `provider_id → providers.id (cascade)`, `provider_service_id → provider_services.id (restrict/restrict)`.
- Índices: `criterio`, `valor`, `user_id`, y compuesto `provider_service_id + criterio + valor + created_at`.

Referencia: `@c:\laragon\www\vintrack-v2\database\migrations\2024_07_07_120001_create_consultations_table.php:1-45`, `@shell:php artisan db:table consultations`.

#### `vehicles`
- 13 columnas: `provider_id`, `provider_service_id`, `criterio`, `valor`, `marca`, `modelo`, `anio`, `ultimo_status_robo`, `total_consultas`, `ultima_consulta_at`.
- Unique: `(provider_service_id, valor)`. Índice en `ultima_consulta_at`.
- Función: cache/denormalización del último estado conocido por vehículo.

Referencia: `@c:\laragon\www\vintrack-v2\database\migrations\2026_07_14_000000_create_vehicles_table.php:1-34`, `@shell:php artisan db:table vehicles`.

#### `provider_services`
- 12 columnas: `provider_id`, `key`, `service_code`, `name`, `credit_cost`, `available_credits`, `min_alert_client`, `min_alert_admin`, `enabled`.
- Unique: `(provider_id, key)`, `service_code`.
- `available_credits` representa el inventario global de créditos comprados a ese servicio.

Referencia: `@c:\laragon\www\vintrack-v2\database\migrations\2024_07_06_180001_create_provider_services_table.php:1-28`, `@shell:php artisan db:table provider_services`.

#### `user_provider_wallets`
- 11 columnas: `user_id`, `provider_id`, `provider_service_id`, `balance`, `min_alert`, `validity_start`, `validity_end`, `status`.
- Unique: `(user_id, provider_service_id)`.
- FK `provider_service_id` con `set null` al borrar.

Referencia: `@c:\laragon\www\vintrack-v2\database\migrations\2024_07_06_180003_create_user_provider_wallets_table.php:1-30`, `@shell:php artisan db:table user_provider_wallets`.

#### `wallet_ledger`
- 8 columnas: `wallet_id`, `provider_service_id`, `delta`, `reason`, `meta` (json), `correlation_id`, `created_at`.
- `correlation_id` es único y sirve para idempotencia.

Referencia: `@c:\laragon\www\vintrack-v2\database\migrations\2024_07_06_180004_create_wallet_ledger_table.php:1-27`, `@shell:php artisan db:table wallet_ledger`.

#### `users`
- 18 columnas: autenticación (`email`, `password`), perfil (`nombre`, `telefono`, `es_oficial`, `entidad`), rol (`id_rol`, `rol`), estados (`activo`, `status`, `approved_at`), OTP (`email_otp`, `email_otp_expire`).
- `id_rol → roles.id_rol (set null)`.

Referencia: `@c:\laragon\www\vintrack-v2\database\migrations\2026_07_13_000000_update_users_roles_and_status.php`, `@shell:php artisan db:table users`.

---

## 5. Flujo de Consulta

### 5.1 Endpoints

| Ruta | Controlador | Uso |
|------|-------------|-----|
| `POST /consult` | `ConsultationController@consult` | Realizar consulta (cliente/admin). |
| `GET /reports/{id}` | `ReportController@show` | Ver reporte HTML. |
| `GET /reports/{id}/pdf` | `ReportController@pdf` | Descargar PDF. |
| `GET /customer/consultations` | `CustomerAccountController@consultations` | Historial del cliente. |
| `GET /admin/consultations` | `AdminConsultationController@index` | Historial administrativo. |
| `GET /admin/vehicles` | `AdminVehicleController@index` | Vehículos consultados. |

Referencia: `@c:\laragon\www\vintrack-v2\routes\web.php:1-158`.

### 5.2 Secuencia de una consulta

1. **Validación de entrada** en `ConsultationController::consult()`:
   - Requiere autenticación y rol cliente u otro permitido.
   - Valida `provider_id`, `provider_service_id`, `criterio` (placa/niv/vin), `valor`.
   - Verifica `RoleHelper::isServiceAllowed()` para el usuario actual.

2. **Orquestación** en `ConsultationService::consult()`:
   - Resuelve el `ProviderAdapter` mediante `ProviderAdapterRegistry` según `adapter_code`.
   - Valida que el servicio esté habilitado y que el crédito no esté vencido (`wallet.status !== 'expired'`).
   - Verifica saldo suficiente en `UserProviderWallet`.
   - **Debita créditos** con `DebitCreditsCommandHandler` **antes** de llamar al proveedor (aunque dentro de la misma petición).
   - Llama al adaptador externo (`PlacasProviderAdapter` o `VINDataProviderAdapter`).
   - Si la API responde exitosamente, guarda la consulta con `ConsultationRepository::save()`.
   - Ejecuta `VehicleUpserter` para actualizar/crear el registro en `vehicles`.
   - Si hay alerta de robo, dispara `ConsultationNotifierInterface` (implementado por correo).

3. **Persistencia**:
   - `ConsultationRepository::save()` utiliza `updateOrCreate` sobre el modelo Eloquent.
   - `VehicleUpserter` usa `firstOrNew` con `provider_service_id + valor` y acumula `total_consultas`.

4. **Presentación**:
   - `ReportController` decide entre `reports.placas` y `reports.vin_data` según el `adapter_code` del proveedor.
   - Para Placas, `PlacasReportPresenter::computeBanner()` determina el banner de 5 niveles.

Referencia: `@c:\laragon\www\vintrack-v2\app\Presentation\Http\Controllers\Web\ConsultationController.php:1-150`, `@c:\laragon\www\vintrack-v2\app\Application\Consultas\Services\ConsultationService.php:1-194`, `@c:\laragon\www\vintrack-v2\app\Application\Vehicles\Services\VehicleUpserter.php:1-58`, `@c:\laragon\www\vintrack-v2\app\Presentation\Support\PlacasReportPresenter.php:195-264`.

---

## 6. Consumo de Créditos

### 6.1 Modelo de créditos

- Cada usuario tiene un wallet por `provider_service_id` (`user_provider_wallets`).
- El costo por consulta se lee de `provider_services.credit_cost`.
- El saldo se almacena como `decimal(10,2)`.
- Los movimientos se registran en `wallet_ledger` con `correlation_id` único.

### 6.2 Puntos de débito y acreditación

| Operación | Clase | Comportamiento |
|-----------|-------|----------------|
| Débito por consulta | `DebitCreditsCommandHandler` | Verifica saldo, genera ledger negativo, actualiza wallet, notifica saldo bajo/cero. |
| Acreditación por paquete | `AddCreditsCommandHandler` | Crea/acredita wallet, descontando `provider_services.available_credits` (inventario). |
| Reintegro por vencimiento | `ReturnExpiredCreditsService` | Debita wallet vencida y devuelve saldo a inventario. |
| Inventario | `InventoryMovementService` | Ajusta `provider_services.available_credits` con transacción y `lockForUpdate()`. |

### 6.3 Flujo exacto en una consulta

En `@c:\laragon\www\vintrack-v2\app\Application\Consultas\Services\ConsultationService.php:1-194`, la línea de crédito se consume **después de validar saldo y antes de llamar a la API**. Esto implica que:

- Si la API falla, el crédito ya fue debitado; la consulta se guarda como `success = false`.
- El ledger queda registrado con `correlation_id` basado en `user_id + provider_service_id + time()`.

### 6.4 Vencimientos y estados

- `user_provider_wallets.status` y `user_packages.status` pueden ser `active`/`expired`.
- `UserProviderWallet::syncExpiredStatuses()` y `UserPackage::syncExpiredStatuses()` actualizan estados según `validity_end`/`expires_at`.
- El cron `inventory:return-expired-credits` procesa reintegros horariamente.

Referencia: `@c:\laragon\www\vintrack-v2\app\Application\Credits\CommandHandlers\DebitCreditsCommandHandler.php:1-70`, `@c:\laragon\www\vintrack-v2\app\Application\Credits\CommandHandlers\AddCreditsCommandHandler.php:1-98`, `@c:\laragon\www\vintrack-v2\app\Application\Inventory\Services\InventoryMovementService.php:1-102`, `@c:\laragon\www\vintrack-v2\app\Application\Inventory\Services\ReturnExpiredCreditsService.php:1-150`.

---

## 7. Proveedores Externos

### 7.1 Registro de adaptadores

`ProviderAdapterServiceProvider` registra dos adaptadores en un singleton:

- `PlacasProviderAdapter` → código `placas`.
- `VINDataProviderAdapter` → código `vindata`.

Referencia: `@c:\laragon\www\vintrack-v2\app\Providers\ProviderAdapterServiceProvider.php:1-28`.

### 7.2 Configuración

`config/providers.php` lee de `.env`:

- `PLACAS_API_URL`, `PLACAS_API_TOKEN`, `PLACAS_CALLBACK_URL`, `PLACAS_CALLBACK_SECRET`, timeouts y longitudes de placa/NIV.
- `VINDATA_API_URL`, `VINDATA_USERNAME`, `VINDATA_PASSWORD`, `VINDATA_SECRET_KEY`, timeout, token TTL y mapa de productos (`vhr`, `nmvtis_plus`).

Referencia: `@c:\laragon\www\vintrack-v2\config\providers.php:1-36`.

### 7.3 PlacasProviderAdapter

- Valida longitud de placa/NIV.
- Obtiene token, hace POST, y hace polling GET hasta obtener resultado.
- Detecta flags de robo/recuperado (`repuve_robo`, `pgj_robo`, `ocra_robo`, `carfax_robo`, `rapi_robo`).
- Guarda el cuerpo completo de la respuesta en `response_json`.

Referencia: `@c:\laragon\www\vintrack-v2\app\Infrastructure\External\Providers\Placas\PlacasProviderAdapter.php:1-278`.

### 7.4 VINDataProviderAdapter

- Valida VIN (17 caracteres).
- Autentica con usuario/password, cachea token en sesión.
- Dos pasos: "buy report" y luego "get raw report data".
- Detecta alertas: active theft, open lien, junk/salvage, odometer issues, etc.

Referencia: `@c:\laragon\www\vintrack-v2\app\Infrastructure\External\Providers\VINData\VINDataProviderAdapter.php:1-230`.

### 7.5 Manejo de fallos

- Los adaptadores devuelven `ConsultationResponse` con `success = false` y `error_message`.
- `ConsultationService` guarda el intento fallido con `costo_credito` igual al costo debitado.
- No hay reembolso automático del crédito si la API falla.

---

## 8. Roles, Permisos y Menús

### 8.1 Modelo de roles

- `role_types`: define `is_admin`, `is_customer`, `color`, etc.
- `roles`: tiene `id_rol` (PK propia), `nombre`, `role_type_id`, `home_route`, `requires_approval`, `status`.
- `users` tiene `id_rol` y `rol` (string redundante).

### 8.2 Permisos

- `admin_menu_permissions` y `customer_menu_permissions`: filas por `id_rol + route_name` con `enabled`.
- `menu_items`: catálogo maestro de opciones de menú con `scope` (`admin`/`customer`).
- `provider_service_roles`: controla qué servicios puede usar cada rol (`allowedServiceIds`).
- `provider_service_section_roles`: controla qué secciones de reporte ve cada rol (relevante para Placas).

### 8.3 Middleware

- `auth` → redirige a login.
- `role` → permite lista de roles; admin siempre implícito; fallback por permiso de menú.
- `active.customer` → solo roles de cliente/perito/oficial/unidad_analisis; redirige a `home.pending` si requiere aprobación.
- `customer.menu` → valida permiso de menú cliente.

Referencia: `@c:\laragon\www\vintrack-v2\bootstrap\app.php:13-19`, `@c:\laragon\www\vintrack-v2\app\Presentation\Http\Middleware\RequireRole.php:1-51`, `@c:\laragon\www\vintrack-v2\app\Presentation\Http\Middleware\RequireActiveCustomer.php:1-38`, `@c:\laragon\www\vintrack-v2\app\Presentation\Http\Middleware\RequireCustomerMenuPermission.php:1-47`, `@c:\laragon\www\vintrack-v2\app\Presentation\Support\RoleHelper.php:1-136`.

### 8.4 Aprobación de usuarios

- Roles con `requires_approval = true` quedan con `status = pending` al registrarse.
- `LoginController` bloquea acceso hasta aprobación.
- `AdminUserController::approve()` activa la cuenta.

Referencia: `@c:\laragon\www\vintrack-v2\app\Presentation\Http\Controllers\Web\RegisterController.php:138-156`, `@c:\laragon\www\vintrack-v2\app\Presentation\Http\Controllers\Web\LoginController.php:72-76`, `@c:\laragon\www\vintrack-v2\app\Presentation\Http\Controllers\Web\Admin\AdminUserController.php:72-87`.

---

## 9. Seguridad

### 9.1 Autenticación

- Login por email/password + OTP de 6 dígitos enviado por correo (`OtpService`).
- Bloqueo por intentos fallidos: 3 intentos → 10 minutos de bloqueo basado en sesión.
- La contraseña requiere mayúscula y dígito (`RegisterController:133`).

Referencia: `@c:\laragon\www\vintrack-v2\app\Presentation\Http\Controllers\Web\LoginController.php:16-64`, `@c:\laragon\www\vintrack-v2\app\Application\Auth\Services\OtpService.php:1-124`.

### 9.2 Autorización

- Middleware `role` y `customer.menu` consultan permisos en base de datos.
- `ConsultationController` verifica `RoleHelper::isServiceAllowed()` antes de consultar.
- Los reportes solo son visibles por el dueño o un admin.

### 9.3 Protección de borrado

- `AdminUserController::destroy()` impide borrar usuarios con paquetes, wallets o consultas.
- `AdminPackageController::destroy()` impide borrar paquetes asignados a usuarios activos.
- No se permiten editar/eliminar compras ya registradas (UI y confirmación).

### 9.4 Datos sensibles

- Credenciales de proveedores y SMTP solo en `.env`/`.env.hosting.txt`.
- `.env.hosting.txt` deja marcadores `PONER_AQUI_LA_KEY_GENERADA`, `TU_TOKEN`, etc.
- La BD local no contiene datos de producción reales (entorno de desarrollo).

### 9.5 Riesgos de seguridad identificados

| # | Riesgo | Evidencia |
|---|--------|-----------|
| 1 | **OTP enviado por correo sin cifrado**; si el correo es interceptado, se puede suplantar la sesión. | `@c:\laragon\www\vintrack-v2\app\Application\Auth\Services\OtpService.php:49-62` |
| 2 | **Rate limiting débil**: solo limita intentos de login por sesión, no por IP/cuenta. | `@c:\laragon\www\vintrack-v2\app\Presentation\Http\Controllers\Web\LoginController.php:35-51` |
| 3 | **Falta de validación de CSRF** en algunos formularios potencialmente vía fetch (aunque Blade suele incluir `@csrf`); verificar vistas. | Ver sección 11 |
| 4 | **Rol redundante en `users.rol`** string junto con `id_rol`; inconsistencia posible si un rol cambia de nombre. | `@c:\laragon\www\vintrack-v2\app\Models\User.php:1-103` |
| 5 | **Consultas sin rate limiting por usuario**: un cliente podría automatizar consultas y agotar créditos rápidamente. | `@c:\laragon\www\vintrack-v2\routes\web.php` |

---

## 10. Reportes, Frontend y UX

- Plantilla base `layouts.app` con Bootstrap 5, sidebar responsive y menú dinámico.
- DataTables se cargan desde CDN con botones Excel/PDF/Copiar/Imprimir.
- No hay un paquete DataTables de servidor (Yajra); el backend devuelve todos los registros y DataTables pagina en cliente. Para grandes volúmenes esto será lento.
- Reportes Placas usan banner de 5 niveles y resaltado rojo por palabras clave de robo/fraude.
- Reportes VINData reemplazan la marca "VINData" por "VINTrack".

Referencia: `@c:\laragon\www\vintrack-v2\resources\views\layouts\app.blade.php:1-304`, `@c:\laragon\www\vintrack-v2\resources\views\admin\consultations\index.blade.php:238-267`, `@c:\laragon\www\vintrack-v2\resources\views\customer\consultations.blade.php:1-54`, `@c:\laragon\www\vintrack-v2\app\Presentation\Http\Controllers\Web\ReportController.php:1-145`, `@c:\laragon\www\vintrack-v2\app\Presentation\Support\PlacasReportPresenter.php:1-313`.

---

## 11. Almacenamiento, Email y Notificaciones

### 11.1 Almacenamiento

- `FILESYSTEM_DISK=local` por defecto.
- Disco `public` apunta a `storage/app/public`, accesible vía `/storage` si existe symlink.
- En hosting compartido sin terminal, el symlink debe crearse manualmente desde cPanel o ajustar el código.

Referencia: `@c:\laragon\www\vintrack-v2\config\filesystems.php:1-81`.

### 11.2 Email

- Configuración por defecto local: `log` (guarda en log, no envía).
- Hosting: SMTP `mail.vintrack.com.mx:587` TLS, usuario `noreply@vintrack.com.mx`.
- Envío **síncrono**: no hay worker de colas, `Mail::send()` se ejecuta dentro de la petición HTTP.

Referencia: `@c:\laragon\www\vintrack-v2\.env.hosting.txt:32-39`, `@c:\laragon\www\vintrack-v2\config\mail.php:1-117`.

### 11.3 Notificaciones

- `NotificationPolicy` define eventos por servicio: `wallet.low_balance`, `wallet.zero_balance`, `wallet.expiring`, `wallet.expired`, `consultation.risk_alert`.
- `NotificationDelivery` registra cada intento con `dedup_key` para evitar duplicados.
- `CustomerMailNotificationService` envía correos de saldo bajo, créditos vencidos y alertas de robo.
- Alerta de robo incluye BCC al admin configurado en `vintrack.admin_email`.

Referencia: `@c:\laragon\www\vintrack-v2\app\Infrastructure\Persistence\Models\NotificationPolicy.php:1-85`, `@c:\laragon\www\vintrack-v2\app\Infrastructure\Persistence\Models\NotificationDelivery.php:1-49`, `@c:\laragon\www\vintrack-v2\app\Application\Notifications\Services\CustomerMailNotificationService.php:1-239`, `@c:\laragon\www\vintrack-v2\app\Infrastructure\Notifications\MailConsultationNotifier.php:1-21`.

---

## 12. Jobs, Cron y Procesamiento en Segundo Plano

### 12.1 Colas

- `QUEUE_CONNECTION=database` requiere tabla `jobs`.
- No se encontraron clases Job propias en `app/Jobs`.
- Todo envío de correo es síncrono.

### 12.2 Scheduler

- `routes/console.php` registra un único comando programado:
  - `inventory:return-expired-credits` cada hora.
- El comando `ReturnExpiredCredits` verifica existencia de columnas `status` en `user_packages` y `user_provider_wallets` antes de ejecutar.

Referencia: `@c:\laragon\www\vintrack-v2\routes\console.php:1-12`, `@c:\laragon\www\vintrack-v2\app\Console\Commands\ReturnExpiredCredits.php:1-96`.

### 12.3 Alternativas de cron para hosting compartido

Dado que no hay SSH/terminal en producción:

| Opción | Descripción | Recomendación |
|--------|-------------|---------------|
| A | **cPanel Cron Jobs** → si cPanel permite, configurar `php /home/.../artisan schedule:run` cada minuto. | **Primera opción**; verificar con Neubox. |
| B | **Servicio externo de ping/cron** (cron-job.org, UptimeRobot) → invocar una URL segura protegida por token que ejecute `schedule:run`. | Implementar endpoint oculto con middleware de token. |
| C | **Webhook manual diario** → un admin visita ruta protegida para disparar reintegros. | No escalable, solo respaldo. |
| D | **No ejecutar cron** → los créditos vencidos no se reintegran automáticamente. | **No recomendado**; acumularía saldo "fantasma". |

---

## 13. Deployment y Hosting

### 13.1 Restricciones del hosting

- cPanel básico, PHP 8.3, MariaDB 10.6.
- Sin SSH, sin terminal PHP/Artisan.
- Subida por FTP/File Manager.

Referencia: `@c:\laragon\www\vintrack-v2\deploy.md:1-151`.

### 13.2 Proceso documentado

1. Subir archivos modificados/nuevos vía FTP.
2. Aplicar scripts SQL de la carpeta `deploy/` en PHPMyAdmin.
3. Localmente ejecutar:
   - `composer dump-autoload --optimize`
   - `php artisan config:cache`
   - `php artisan route:cache`
   - `php artisan event:cache`
   - `npm run build` (si hay cambios de assets)
4. Subir `vendor/composer/*`, `vendor/autoload.php` y `bootstrap/cache/*`.
5. Asegurar permisos 755/775 en `storage/` y `bootstrap/cache/`.

### 13.3 Scripts SQL existentes en `deploy/`

Se encontraron 22 archivos en `deploy/` (inspección de directorio). Ejemplos relevantes:

- `2026-07-23_package_wallet_statuses.sql` — agrega `status` a wallets y paquetes, marca vencidos.
- `2026-07-23_role_types.sql` — pobla `role_types` y actualiza `roles`.
- `2026-08-04_add_vin_decoder_customer_menu_permission.sql` — agrega opción VIN Decoder al menú cliente.

La estrategia evita `ON DUPLICATE KEY UPDATE` porque PHPMyAdmin del hosting no lo soporta.

### 13.4 Compatibilidad MySQL/MariaDB

- Migraciones usan tipos de Laravel 12 (JSON, `foreignId`, `decimal`, `timestamp`, etc.).
- El esquema actual se ejecuta sin problemas en MariaDB 10.6 local (todas las tablas con engine InnoDB).
- Considerar que `json` en MariaDB 10.6 funciona; en versiones anteriores podría requerir `longtext`.

---

## 14. Riesgos y Deuda Técnica

| # | Categoría | Descripción | Impacto | Prioridad |
|---|-----------|-------------|---------|-----------|
| 1 | **Arquitectura de consulta** | El crédito se debita antes de la respuesta del proveedor. Si la API falla, el cliente pierde el crédito. | Financiero / confianza | Alta |
| 2 | **Frontend/performance** | DataTables renderiza toda la tabla en servidor; con miles de consultas la carga será pesada. | UX / rendimiento | Alta |
| 3 | **Deployment** | Dependencia de subir `vendor/` y cachés compilados manualmente por FTP; propenso a errores humanos. | Operativo | Alta |
| 4 | **Scheduler** | Sin cron en hosting, los reintegros de créditos vencidos no ocurren. | Financiero / inventario | Alta |
| 5 | **Email** | Envío síncrono puede ralentizar consultas y fallos de SMTP afectan respuesta al usuario. | UX / confiabilidad | Media |
| 6 | **Seguridad** | Rate limiting solo por sesión; OTP por correo; campo `rol` redundante. | Seguridad / integridad | Media |
| 7 | **Tests** | No se ejecutó suite completa; el proyecto tiene tests en `tests/` pero no se verificó cobertura. | Calidad | Media |
| 8 | **Documentación** | No existe documentación de proyecto en `docs/` (solo este sprint). | Mantenibilidad | Media |
| 9 | **Dependencias CDN** | Bootstrap, DataTables, jQuery desde CDN; si falla el CDN, la app pierde estilos/funcionalidad. | Disponibilidad | Baja |
| 10 | **Colas** | `QUEUE_CONNECTION=database` sin worker; aunque no hay jobs propios, el envío de correo síncrono puede saturar. | Escalabilidad | Baja |

---

## 15. Recomendaciones Prioritarias (sin implementar)

1. **Auditar el débito de créditos**: considerar un patrón de "reserva + confirmación" para no cobrar consultas fallidas por fallas del proveedor, o implementar reembolso automático.
2. **Paginación servidor para DataTables**: migrar al modo AJAX/server-side para listados grandes (consultas, vehículos, movimientos).
3. **Definir y probar estrategia de cron en producción**: confirmar si Neubox/cPanel permite cron y documentar el comando exacto.
4. **Revisar rate limiting y 2FA**: fortalecer protección de login y consultas (Laravel Throttle, reCAPTCHA opcional).
5. **Ejecutar suite de tests** y medir cobertura antes de nuevos sprints.
6. **Crear/documentar manuales de administración** para roles, menús, proveedores y paquetes.
7. **Establecer pipeline de deploy** (script de empaquetado ZIP + SQL) para reducir errores manuales por FTP.

---

## 16. Estado del Sprint 00 y Próximos Pasos

### 16.1 Entregables de este sprint
- Este documento (`docs/sprints/SPRINT-00-RESULT.md`) con mapeo técnico del sistema.
- Confirmación de que el repositorio local está en Laravel 12.62.0 con 41 migraciones aplicadas.
- Identificación de los flujos críticos: consulta, créditos, notificaciones, roles y deployment.

### 16.2 Bloqueantes para futuros sprints
- Confirmar disponibilidad de cron en hosting compartido.
- Asegurar credenciales correctas de proveedores y SMTP en `.env.hosting.txt`.
- Verificar que los scripts SQL de `deploy/` estén actualizados con el esquema actual (especialmente migraciones recientes de `menu_items` y `provider_service_id`).

### 16.3 Siguientes pasos sugeridos
1. Revisar y aprobar este documento.
2. Planificar el siguiente sprint con base en las recomendaciones (ej. cron en producción, server-side DataTables, reembolso de créditos fallidos).
3. Antes de cualquier implementación, ejecutar `php artisan test` y validar estado de salud.

---

## 17. Referencias Rápidas de Archivos Clave

- Rutas: `@c:\laragon\www\vintrack-v2\routes\web.php:1-158`
- Controlador de consulta: `@c:\laragon\www\vintrack-v2\app\Presentation\Http\Controllers\Web\ConsultationController.php:1-150`
- Servicio de consulta: `@c:\laragon\www\vintrack-v2\app\Application\Consultas\Services\ConsultationService.php:1-194`
- Adaptador Placas: `@c:\laragon\www\vintrack-v2\app\Infrastructure\External\Providers\Placas\PlacasProviderAdapter.php:1-278`
- Adaptador VINData: `@c:\laragon\www\vintrack-v2\app\Infrastructure\External\Providers\VINData\VINDataProviderAdapter.php:1-230`
- Wallet repository: `@c:\laragon\www\vintrack-v2\app\Infrastructure\Persistence\Eloquent\Credits\WalletRepository.php:1-91`
- Domain wallet: `@c:\laragon\www\vintrack-v2\app\Domain\Credits\Entities\Wallet.php:1-100`
- Notificaciones: `@c:\laragon\www\vintrack-v2\app\Application\Notifications\Services\CustomerMailNotificationService.php:1-239`
- Middleware de roles: `@c:\laragon\www\vintrack-v2\app\Presentation\Http\Middleware\RequireRole.php:1-51`
- Deployment: `@c:\laragon\www\vintrack-v2\deploy.md:1-151`
- Config hosting: `@c:\laragon\www\vintrack-v2\.env.hosting.txt:1-57`

---

*Documento generado como resultado del SPRINT-00 de descubrimiento. No contiene cambios de código ni base de datos.*

## Owner Review / Governance Closure

**Status:** APPROVED WITH OBSERVATIONS  
**Approval date:** 2026-08-12

SPRINT-00 was originally executed with Devin and subsequently verified by Codex. It is closed and must not be repeated.

### Official observations

1. The current real flow executes the provider/API before the debit. The debit occurs after a successful response. The earlier Discovery statement that placed the debit before the API was outdated.
2. There are currently 45 migrations applied locally, not 41.
3. The canonical field is `consultations.provider_service_id`.
4. There is no independent Client/Customer entity. A client is currently represented by `User` and its classification through `Role` / `RoleType.is_customer`.
5. `vehicles` is confirmed as a consolidated/cache/reporting structure and will not be the notification-expedient master.
6. Current DataTables use full collections and client-side processing. New views must evaluate a server-side architecture for scalability.
7. Email infrastructure and notification deliveries with deduplication exist, but a complete in-portal notification infrastructure was not confirmed.
8. Partial audit mechanisms exist, but there is no general domain-audit infrastructure adequate for the new process.
9. Authorization exists through dynamic roles and permissions, but there is no uniform Policies/Gates-based infrastructure.
10. cPanel Cron is available in production. The exact PHP/Artisan execution mechanism allowed by the hosting remains undetermined.
11. The production schema was not independently verified during the Codex inspection.
12. The definitive business timezone must be resolved during design before temporal calculations are implemented.

### Current technical consultation baseline

Request → validations → wallet/balance validation → provider/API → successful response → debit → persistence.

This is a record of current implementation behavior, not a new business rule. Refactoring the normal API/debit order is outside the current scope unless a later Change Request authorizes it.

The future maximum-three-pending-expedients rule must execute before provider/API, debit and any billable external operation.
