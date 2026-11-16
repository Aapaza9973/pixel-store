<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Venta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmación del pedido web (HU-3.3): incluye los enlaces con token para
 * confirmar o cancelar sin necesidad de cuenta.
 */
class PedidoConfirmacion extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Venta $venta) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pedido #'.$this->numero().' recibido — Pixel Store',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pedido-confirmacion',
            with: [
                'venta' => $this->venta,
                'numero' => $this->numero(),
                'urlConfirmar' => route('pedido.confirmar', [
                    'venta' => $this->venta->id,
                    'token' => $this->venta->token_confirmacion,
                ]),
                'urlCancelar' => route('pedido.cancelar', [
                    'venta' => $this->venta->id,
                    'token' => $this->venta->token_confirmacion,
                ]),
            ],
        );
    }

    private function numero(): string
    {
        return str_pad((string) $this->venta->id, 6, '0', STR_PAD_LEFT);
    }
}
