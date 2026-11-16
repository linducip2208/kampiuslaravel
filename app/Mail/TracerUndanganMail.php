<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Ide bank: undangan pengisian tracer study untuk alumni.
 * Dikirim hanya bila MailService::terkonfigurasi().
 */
class TracerUndanganMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nama,
        public string $tahunLulus,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Undangan Tracer Study Alumni — KAMPUS');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.tracer-undangan');
    }
}
