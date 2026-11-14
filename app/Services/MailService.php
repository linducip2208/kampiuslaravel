<?php

namespace App\Services;

use App\Mail\TagihanReminderMail;
use Illuminate\Support\Facades\Mail;

/**
 * M-064: channel email (pelengkap WA + inbox + FCM).
 * Tanpa SMTP terkonfigurasi: no-op (tidak error, tidak spam log).
 */
class MailService
{
    public static function terkonfigurasi(): bool
    {
        $mailer = (string) config('mail.default', 'log');

        if (in_array($mailer, ['log', 'array', 'fail', ''], true)) {
            return false;
        }

        if ($mailer === 'smtp' && trim((string) config('mail.mailers.smtp.host', '')) === '') {
            return false;
        }

        return true;
    }

    public static function kirimTagihan(string $email, string $nama, string $pesan, string $jenis, string $nominal): void
    {
        Mail::to($email)->send(new TagihanReminderMail($nama, $pesan, $jenis, $nominal));
    }
}
