<?php

namespace Tests\Feature\NotificationCases;

use App\Application\NotificationCases\Services\NotificationCaseOutboxService;
use App\Application\NotificationCases\Services\NotificationDeadlineReminderService;
use App\Application\NotificationCases\Services\NotificationOutboxProcessor;
use App\Infrastructure\Persistence\Models\Consultation;
use App\Infrastructure\Persistence\Models\NotificationCase;
use App\Infrastructure\Persistence\Models\PortalNotification;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Mail\NotificationCaseMail;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class NotificationDeliveryAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_processor_delivers_email_and_portal_independently_and_deduplicates(): void
    {
        Mail::fake();
        [$owner, $case] = $this->case();
        app(NotificationCaseOutboxService::class)->queueChannels($case->id, $owner->id, 'CASE_VALIDATED', 'validated:1', [
            'case_number' => $case->case_number, 'status' => 'VALIDATED',
        ]);

        $stats = app(NotificationOutboxProcessor::class)->process(10, 10, 'test-worker');
        $this->assertSame(['claimed' => 2, 'delivered' => 2, 'retried' => 0, 'failed' => 0], $stats);
        $this->assertDatabaseCount('portal_notifications', 1);
        $this->assertDatabaseCount('notification_deliveries', 1);
        $this->assertDatabaseCount('notification_outbox', 2);
        Mail::assertSent(NotificationCaseMail::class, 1);

        $this->assertSame(0, app(NotificationOutboxProcessor::class)->process(10, 10, 'again')['claimed']);
        $this->assertDatabaseCount('portal_notifications', 1);
        Mail::assertSent(NotificationCaseMail::class, 1);
    }

    public function test_email_failure_retries_without_reverting_portal_and_batch_limit_is_real(): void
    {
        [$owner, $case] = $this->case(['email' => 'invalid-address']);
        app(NotificationCaseOutboxService::class)->queueChannels($case->id, $owner->id, 'CASE_REJECTED', 'rejected:1', [
            'case_number' => $case->case_number, 'message' => 'CORREGIR DOCUMENTO',
        ]);

        $first = app(NotificationOutboxProcessor::class)->process(1, 10, 'batch-one');
        $this->assertSame(1, $first['claimed']);
        $this->assertDatabaseCount('portal_notifications', 1);
        $this->assertDatabaseHas('notification_outbox', ['channel' => 'EMAIL', 'status' => 'PENDING', 'attempts' => 0]);

        $second = app(NotificationOutboxProcessor::class)->process(1, 10, 'batch-two');
        $this->assertSame(1, $second['retried']);
        $this->assertDatabaseHas('notification_outbox', ['channel' => 'PORTAL', 'status' => 'DELIVERED']);
        $this->assertDatabaseHas('notification_outbox', ['channel' => 'EMAIL', 'status' => 'PENDING', 'attempts' => 1]);
    }

    public function test_retry_reaches_bounded_terminal_failure(): void
    {
        [$owner, $case] = $this->case(['email' => 'invalid-address']);
        $message = app(NotificationCaseOutboxService::class)->queue($case->id, $owner->id, 'CASE_VALIDATED', 'EMAIL', 'terminal:email', ['case_number' => $case->case_number]);

        for ($attempt = 1; $attempt <= NotificationOutboxProcessor::MAX_ATTEMPTS; $attempt++) {
            $message->forceFill(['available_at' => now()->subSecond()])->save();
            app(NotificationOutboxProcessor::class)->process(1, 10, 'retry-'.$attempt);
            $message->refresh();
        }

        $this->assertSame('FAILED', $message->status);
        $this->assertSame(NotificationOutboxProcessor::MAX_ATTEMPTS, $message->attempts);
        $this->assertSame(0, app(NotificationOutboxProcessor::class)->process(1, 10, 'retry-terminal')['claimed']);
    }

    public function test_deadline_queue_is_repeatable_and_commands_are_discrete(): void
    {
        CarbonImmutable::setTestNow('2026-08-14 12:00:00');
        [, $case] = $this->case([], '2026-08-15 11:59:59');

        $first = app(NotificationDeadlineReminderService::class)->queueDue(10);
        $second = app(NotificationDeadlineReminderService::class)->queueDue(10);
        $this->assertSame(2, $first['queued']);
        $this->assertSame(0, $second['queued']);
        $this->assertDatabaseHas('notification_outbox', ['case_id' => $case->id, 'event_type' => 'DEADLINE_REMINDER_T_MINUS_1_DAY', 'channel' => 'PORTAL']);

        $this->artisan('notifications:queue-deadline-reminders', ['--limit' => 10])->assertSuccessful();
        $this->artisan('notifications:process-outbox', ['--limit' => 1, '--max-seconds' => 5])->assertSuccessful();
        $this->artisan('notifications:auto-close', ['--limit' => 1])->assertSuccessful();
        CarbonImmutable::setTestNow();
    }

    public function test_portal_is_scoped_readable_and_escapes_untrusted_text(): void
    {
        [$owner, $case] = $this->case();
        [$other] = $this->case();
        $outbox = app(NotificationCaseOutboxService::class)->queue($case->id, $owner->id, 'CASE_REJECTED', 'PORTAL', 'xss:portal', [
            'case_number' => $case->case_number, 'message' => '<script>alert(1)</script>',
        ]);
        app(NotificationOutboxProcessor::class)->process(1, 10, 'portal-worker');
        $notification = PortalNotification::where('outbox_id', $outbox->id)->firstOrFail();

        $this->actingAs($owner)->get(route('customer.notifications.index'))
            ->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->actingAs($owner)->get(route('customer.notifications.unread-count'))->assertJson(['count' => 1]);
        $this->actingAs($other)->post(route('customer.notifications.read', $notification))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
        $this->actingAs($owner)->post(route('customer.notifications.read', $notification))->assertRedirect(route('customer.notification-cases.show', $case));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    private function case(array $userAttributes = [], string $deadline = '2026-08-15 23:59:59'): array
    {
        $owner = User::factory()->create(array_merge(['rol' => 'oficial', 'activo' => true, 'status' => 'active', 'approved_at' => now(), 'email' => fake()->unique()->safeEmail()], $userAttributes));
        $provider = Provider::firstOrCreate(['code' => 'VINDATA'], ['adapter_code' => 'vindata', 'name' => 'VINData', 'base_url' => 'https://example.test', 'enabled' => true]);
        $service = ProviderService::firstOrCreate(['provider_id' => $provider->id, 'key' => 'VHR'], ['service_code' => 'vhr', 'name' => 'VHR', 'credit_cost' => 1, 'available_credits' => 100, 'enabled' => true]);
        $consultation = Consultation::create(['user_id' => $owner->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id, 'criterio' => 'vin', 'valor' => '1HGCM82633A'.str_pad((string) $owner->id, 6, '0', STR_PAD_LEFT), 'services' => ['vhr'], 'success' => true, 'alerta_robo' => true]);
        $case = new NotificationCase;
        $case->forceFill(['consultation_id' => $consultation->id, 'user_id' => $owner->id, 'case_number' => 'NT-2026-'.str_pad((string) $consultation->id, 6, '0', STR_PAD_LEFT), 'vin' => $consultation->valor, 'vin_key' => $consultation->valor, 'status' => 'PENDING', 'notification_deadline_at' => CarbonImmutable::parse($deadline), 'opened_at' => now(), 'auto_close_at' => now()->addDays(30), 'lock_version' => 0, 'creation_key' => 'delivery:'.$consultation->id])->save();

        return [$owner, $case];
    }
}
