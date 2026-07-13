<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo de restablecimiento de contraseña (¿Olvidaste tu contraseña?).
 */
final class RestablecerPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $nombre,
        public readonly string $urlReset,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Restablecer tu contraseña — Rooster',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.restablecer-password',
        );
    }
}
