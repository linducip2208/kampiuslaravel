<?php

namespace App\Http\Controllers;

use App\Models\KalenderEvent;
use Illuminate\Http\Response;

class KalenderController extends Controller
{
    public function ics(): Response
    {
        $events = KalenderEvent::orderBy('tgl_mulai')->get();

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//SIMAK-Kuliah//Kalender Akademik//ID',
            'X-WR-CALNAME:Kalender Akademik',
        ];

        foreach ($events as $e) {
            $mulai = $e->tgl_mulai->format('Ymd');
            $selesai = ($e->tgl_selesai ?? $e->tgl_mulai)->addDay()->format('Ymd');
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:kalender-'.$e->id.'@simak';
            $lines[] = 'DTSTAMP:'.now()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART;VALUE=DATE:'.$mulai;
            $lines[] = 'DTEND;VALUE=DATE:'.$selesai;
            $lines[] = 'SUMMARY:'.$this->escape($e->judul.' ['.$e->jenis.']');
            if ($e->deskripsi) {
                $lines[] = 'DESCRIPTION:'.$this->escape($e->deskripsi);
            }
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="kalender-akademik.ics"',
        ]);
    }

    private function escape(?string $text): string
    {
        $clean = preg_replace('/\s+/', ' ', (string) $text) ?? '';

        return str_replace(['\\', ',', ';', "\n"], ['\\\\', '\\,', '\\;', '\\n'], $clean);
    }
}
