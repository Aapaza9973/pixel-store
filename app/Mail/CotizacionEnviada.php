<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Cotizacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Envío de la cotización al cliente (HU-2.1b). El PDF se adjunta solo si el
 * archivo existe; si no, el correo incluye el detalle completo.
 */
class CotizacionEnviada extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Cotizacion $cotizacion) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cotización '.$this->cotizacion->numero.' — Pixel Store',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cotizacion',
            with: ['cotizacion' => $this->cotizacion],
        );
    }
}
