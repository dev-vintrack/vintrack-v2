<?php

namespace App\Application\NotificationCases\Services;

use App\Application\NotificationCases\Exceptions\NonRetryableDeliveryException;
use App\Infrastructure\Persistence\Models\NotificationDelivery;
use App\Infrastructure\Persistence\Models\NotificationOutbox;
use App\Infrastructure\Persistence\Models\PortalNotification;
use App\Mail\NotificationCaseMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

final class NotificationOutboxProcessor
{
    public const MAX_BATCH_LIMIT = 500;

    public const MAX_ATTEMPTS = 5;

    public function __construct(private readonly NotificationCaseMessageFactory $messages) {}

    /** @return array{claimed:int,delivered:int,retried:int,failed:int} */
    public function process(int $limit = 100, int $maxSeconds = 50, ?string $workerId = null): array
    {
        $limit = max(1, min($limit, self::MAX_BATCH_LIMIT));
        $maxSeconds = max(1, min($maxSeconds, 300));
        $workerId ??= gethostname().':'.getmypid().':'.Str::random(8);
        $deadline = microtime(true) + $maxSeconds;
        $stats = ['claimed' => 0, 'delivered' => 0, 'retried' => 0, 'failed' => 0];

        while ($stats['claimed'] < $limit && microtime(true) < $deadline) {
            $message = $this->claim($workerId);
            if (! $message) {
                break;
            }
            $stats['claimed']++;

            try {
                $this->deliver($message);
                $message->forceFill([
                    'status' => 'DELIVERED', 'sent_at' => now(), 'failed_at' => null,
                    'last_error' => null, 'locked_at' => null, 'locked_by' => null,
                ])->save();
                $stats['delivered']++;
            } catch (NonRetryableDeliveryException $exception) {
                $message->forceFill([
                    'status' => 'FAILED', 'failed_at' => now(),
                    'last_error' => 'NON_RETRYABLE:'.$this->safeError($exception),
                    'locked_at' => null, 'locked_by' => null,
                ])->save();
                $stats['failed']++;
            } catch (Throwable $exception) {
                $terminal = $message->attempts >= self::MAX_ATTEMPTS;
                $message->forceFill([
                    'status' => $terminal ? 'FAILED' : 'PENDING',
                    'available_at' => now()->addSeconds($this->backoffSeconds($message->attempts)),
                    'failed_at' => now(),
                    'last_error' => $this->safeError($exception),
                    'locked_at' => null,
                    'locked_by' => null,
                ])->save();
                $stats[$terminal ? 'failed' : 'retried']++;
                report($exception);
            }
        }

        return $stats;
    }

    private function claim(string $workerId): ?NotificationOutbox
    {
        return DB::transaction(function () use ($workerId) {
            $message = NotificationOutbox::query()
                ->where(function ($query) {
                    $query->where('status', 'PENDING')
                        ->orWhere(function ($stale) {
                            $stale->where('status', 'PROCESSING')->where('locked_at', '<=', now()->subMinutes(10));
                        });
                })
                ->where('available_at', '<=', now())
                ->where('attempts', '<', self::MAX_ATTEMPTS)
                ->orderBy('available_at')->orderBy('id')
                ->lockForUpdate()->first();

            if (! $message) {
                return null;
            }

            $message->forceFill([
                'status' => 'PROCESSING',
                'attempts' => $message->attempts + 1,
                'locked_at' => now(),
                'locked_by' => $workerId,
            ])->save();

            return $message;
        }, 3);
    }

    private function deliver(NotificationOutbox $message): void
    {
        $recipient = User::find($message->recipient_user_id);
        if (! $recipient) {
            throw new NonRetryableDeliveryException('RECIPIENT_NOT_FOUND');
        }

        $content = $this->messages->make($message->event_type, $message->payload ?? []);
        $actionPath = $message->case_id ? route('customer.notification-cases.show', ['case' => $message->case_id], false) : null;

        if ($message->channel === 'PORTAL') {
            PortalNotification::firstOrCreate(['outbox_id' => $message->id], [
                'recipient_user_id' => $recipient->id,
                'case_id' => $message->case_id,
                'type' => $message->event_type,
                'title' => $content['title'],
                'body' => $content['body'],
                'action_path' => $actionPath,
                'created_at' => now(),
            ]);

            return;
        }

        if ($message->channel !== 'EMAIL') {
            throw new \RuntimeException('UNSUPPORTED_CHANNEL');
        }
        if (! filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
            NotificationDelivery::firstOrCreate(['dedup_key' => 'notification-case-outbox:'.$message->id], [
                'uuid' => (string) Str::uuid(), 'user_id' => $recipient->id,
                'event_type' => $message->event_type, 'related_type' => 'notification_case',
                'related_id' => $message->case_id, 'recipient' => (string) $recipient->email,
                'subject' => $content['subject'], 'status' => 'skipped', 'attempts' => 0,
                'error' => 'RECIPIENT_EMAIL_UNAVAILABLE', 'metadata' => ['outbox_id' => $message->id],
            ]);
            throw new NonRetryableDeliveryException('RECIPIENT_EMAIL_UNAVAILABLE');
        }

        $delivery = NotificationDelivery::firstOrCreate(['dedup_key' => 'notification-case-outbox:'.$message->id], [
            'uuid' => (string) Str::uuid(),
            'user_id' => $recipient->id,
            'event_type' => $message->event_type,
            'related_type' => 'notification_case',
            'related_id' => $message->case_id,
            'recipient' => $recipient->email,
            'subject' => $content['subject'],
            'status' => 'pending',
            'attempts' => 0,
            'metadata' => ['outbox_id' => $message->id],
        ]);
        if ($delivery->status === 'sent') {
            return;
        }

        $delivery->forceFill([
            'recipient' => $recipient->email,
            'status' => 'pending',
            'attempts' => $delivery->attempts + 1,
            'error' => null,
        ])->save();
        try {
            Mail::to($recipient->email)->send(new NotificationCaseMail(
                $content['subject'], $content['title'], $content['body'], $content['case_number'],
                $actionPath ? url($actionPath) : null,
            ));
            $delivery->forceFill(['status' => 'sent', 'sent_at' => now(), 'failed_at' => null, 'error' => null])->save();
        } catch (Throwable $exception) {
            $delivery->forceFill(['status' => 'failed', 'failed_at' => now(), 'error' => $this->safeError($exception)])->save();
            throw $exception;
        }
    }

    private function backoffSeconds(int $attempt): int
    {
        return min(3600, 30 * (2 ** max(0, $attempt - 1)));
    }

    private function safeError(Throwable $exception): string
    {
        return Str::limit(preg_replace('/[\r\n\t]+/', ' ', $exception->getMessage()) ?: 'DELIVERY_FAILED', 1000, '');
    }
}
