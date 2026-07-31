<?php

namespace App\Mail;

use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpiredCreditsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly UserProviderWallet $wallet,
        public readonly ProviderService $service,
        public readonly float $amount,
        public readonly CarbonInterface $expiredAt
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tus créditos VINTRACK han vencido',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credits_expired',
        );
    }
}
