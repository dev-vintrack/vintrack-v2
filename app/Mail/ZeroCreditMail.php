<?php

namespace App\Mail;

use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ZeroCreditMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly UserProviderWallet $wallet,
        public readonly ProviderService $service
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'VINTRACK: Tus créditos se han agotado',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.zero_credit',
        );
    }
}
