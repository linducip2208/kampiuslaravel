<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * M-064: pengingat tagihan via email (channel ke-3 setelah WA + inbox).
 * Dikirim hanya bila MailService::terkonfigurasi().
 */
class TagihanReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nama,
        public string $pesan,
        public string $jenis,
        public string $nominal,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Pengingat Tagihan UKT — KAMPUS');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.tagihan-reminder');
    }
}
