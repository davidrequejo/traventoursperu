<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertaIncidenciasSunatMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly array $resumen,
        public readonly array $incidencias,
        public readonly string $ambiente = 'PRODUCCION',
        public readonly string $origen = 'CRON',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Incidencias en el envio a SUNAT',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sunat-incidencias',
        );
    }
}
