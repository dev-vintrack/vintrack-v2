<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowCreditMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $userEmail,
        public float $balance
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'VINTRACK: Tus créditos están por agotarse',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.low_credit',
            with: [
                'userEmail' => $this->userEmail,
                'balance' => $this->balance,
            ],
        );
    }
}
