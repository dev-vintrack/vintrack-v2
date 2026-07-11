<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlacasAlertMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $userInfo
     * @param array<string, mixed> $sections
     */
    public function __construct(
        public array $userInfo,
        public string $criterio,
        public string $valor,
        public array $sections,
        public ?string $adminBcc = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Alerta VINTRACK: posible reporte de robo o recuperado',
            bcc: $this->adminBcc ? [$this->adminBcc] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.placas_alert',
            with: [
                'userInfo' => $this->userInfo,
                'criterio' => $this->criterio,
                'valor' => $this->valor,
                'sections' => $this->sections,
            ],
        );
    }
}
