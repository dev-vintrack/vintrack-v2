<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Crear la columna como NULLABLE (aun no se conoce el valor histórico).
        Schema::table('consultations', function (Blueprint $table) {
            $table->unsignedBigInteger('provider_service_id')->nullable()->after('provider_id');
        });

        // 2) Migrar el histórico.
        // Regla: si el provider_id de la consulta tiene un unico provider_service asociado,
        // la relacion es deterministica y se asigna directamente.
        // Si un provider_id tuviera mas de un provider_service, se debe desambiguar usando
        // el campo `services` (json) comparando contra `key`/`service_code` de provider_services.
        // NOTA: esta migracion NO asume ningun ID fijo; todo se resuelve dinamicamente contra
        // el esquema real presente al momento de ejecutar la migracion.

        if (DB::getDriverName() === 'mysql') {
            // Caso A: providers con un unico provider_service -> asignacion directa.
            DB::statement(<<<'SQL'
                UPDATE consultations c
                JOIN (
                    SELECT provider_id, MIN(id) AS only_service_id
                    FROM provider_services
                    GROUP BY provider_id
                    HAVING COUNT(*) = 1
                ) ps ON ps.provider_id = c.provider_id
                SET c.provider_service_id = ps.only_service_id
                WHERE c.provider_service_id IS NULL
            SQL);

            // Caso B: providers con multiples provider_services -> desambiguar por `services`
            // (coincidencia case-insensitive contra `key` o `service_code`).
            DB::statement(<<<'SQL'
                UPDATE consultations c
                JOIN provider_services ps
                    ON ps.provider_id = c.provider_id
                    AND (
                        LOWER(JSON_UNQUOTE(JSON_EXTRACT(c.services, '$[0]'))) = LOWER(ps.key)
                        OR LOWER(JSON_UNQUOTE(JSON_EXTRACT(c.services, '$[0]'))) = LOWER(ps.service_code)
                    )
                SET c.provider_service_id = ps.id
                WHERE c.provider_service_id IS NULL
            SQL);
        }

        // 3) Validacion de cero ambiguedad: si queda algun registro sin asignar, detener la migracion.
        $unassigned = DB::table('consultations')->whereNull('provider_service_id')->count();
        if ($unassigned > 0) {
            throw new \RuntimeException(
                "Migracion detenida: existen {$unassigned} consultas sin provider_service_id determinable. " .
                'Revise consultations.services / provider_id antes de continuar.'
            );
        }

        // 4) Validacion de integridad provider/service.
        $mismatched = DB::table('consultations as c')
            ->join('provider_services as ps', 'ps.id', '=', 'c.provider_service_id')
            ->whereColumn('c.provider_id', '<>', 'ps.provider_id')
            ->count();
        if ($mismatched > 0) {
            throw new \RuntimeException(
                "Migracion detenida: {$mismatched} consultas tienen provider_service_id de un proveedor distinto."
            );
        }

        // 5) Indice y FK.
        Schema::table('consultations', function (Blueprint $table) {
            $table->index(
                ['provider_service_id', 'criterio', 'valor', 'created_at'],
                'consultations_service_criterio_valor_created_idx'
            );
            $table->foreign('provider_service_id')
                ->references('id')->on('provider_services')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });

        // 6) NOT NULL final, solo si la validacion previa fue exitosa.
        $stillNull = DB::table('consultations')->whereNull('provider_service_id')->count();
        if ($stillNull === 0) {
            Schema::table('consultations', function (Blueprint $table) {
                $table->unsignedBigInteger('provider_service_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropForeign(['provider_service_id']);
            $table->dropIndex('consultations_service_criterio_valor_created_idx');
            $table->dropColumn('provider_service_id');
        });
    }
};
