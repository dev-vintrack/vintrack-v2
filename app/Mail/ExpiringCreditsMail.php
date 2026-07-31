<?php

namespace App\Mail;

use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpiringCreditsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly UserProviderWallet $wallet,
        public readonly ProviderService $service,
        public readonly int $daysRemaining
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Tus créditos VINTRACK vencen en {$this->daysRemaining} " . ($this->daysRemaining === 1 ? 'día' : 'días'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credits_expiring',
        );
    }
}
