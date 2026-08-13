<?php

namespace Tests\Feature\NotificationCases;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationCasePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_contains_contractual_tables_and_excludes_forbidden_case_columns(): void
    {
        foreach (['notification_cases', 'notification_case_documents', 'notification_case_events', 'notification_outbox', 'portal_notifications', 'notification_case_sequences', 'notification_case_user_guards', 'notification_case_vin_guards', 'notification_case_source_guards', 'notification_case_consultation_reservations', 'notification_case_vin_reconciliations'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }
        $this->assertTrue(Schema::hasColumns('notification_cases', ['consultation_id', 'user_id', 'case_number', 'vin', 'vin_key', 'notification_deadline_at', 'lock_version', 'creation_key']));
        $this->assertFalse(Schema::hasColumn('notification_cases', 'vehicle_id'));
        $this->assertFalse(Schema::hasColumn('notification_cases', 'provider_service_id'));
        $this->assertFalse(Schema::hasColumn('notification_cases', 'source_criterion'));
        $this->assertFalse(Schema::hasColumn('notification_cases', 'source_value'));
        $this->assertTrue(Schema::hasColumn('consultations', 'provider_service_id'));
    }
}
