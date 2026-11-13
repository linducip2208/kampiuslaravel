<?php

namespace App\Http\Controllers;

use App\Models\PmbPendaftar;
use Illuminate\View\View;

class PmbFunnelController extends Controller
{
    public function index(): View
    {
        $byStatus = PmbPendaftar::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byGelombang = PmbPendaftar::query()
            ->leftJoin('pmb_gelombangs', 'pmb_gelombangs.id', '=', 'pmb_pendaftars.gelombang_id')
            ->selectRaw("COALESCE(pmb_gelombangs.nama, 'Tanpa gelombang') as nama, COUNT(*) as total, SUM(CASE WHEN pmb_pendaftars.status = '".PmbPendaftar::STATUS_LULUS."' THEN 1 ELSE 0 END) as lulus")
            ->groupByRaw("COALESCE(pmb_gelombangs.nama, 'Tanpa gelombang')")
            ->orderByDesc('total')
            ->get();

        $bySekolah = PmbPendaftar::query()
            ->selectRaw('sekolah_asal, COUNT(*) as total')
            ->groupBy('sekolah_asal')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $byReferral = PmbPendaftar::query()
            ->selectRaw('COALESCE(referral_kode, ?) as kode, COUNT(*) as total', ['-'])
            ->groupBy('kode')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $total = (int) PmbPendaftar::query()->count();
        $vaTerbit = (int) PmbPendaftar::query()->whereNotNull('va_number')->count();
        $vaLunas = (int) PmbPendaftar::query()->where('bayar_status', 'lunas')->count();
        $beasiswaPakai = (int) PmbPendaftar::query()->whereNotNull('beasiswa_id')->count();
        $followUp = (int) PmbPendaftar::query()->whereNotNull('last_follow_up_at')->count();

        $diajukan = (int) ($byStatus[PmbPendaftar::STATUS_DIAJUKAN] ?? 0);
        $diverifikasi = (int) ($byStatus[PmbPendaftar::STATUS_DIVERIFIKASI] ?? 0);
        $lulus = (int) ($byStatus[PmbPendaftar::STATUS_LULUS] ?? 0);

        return view('pmb.funnel', [
            'byStatus' => $byStatus,
            'byGelombang' => $byGelombang,
            'bySekolah' => $bySekolah,
            'byReferral' => $byReferral,
            'total' => $total,
            'vaTerbit' => $vaTerbit,
            'vaLunas' => $vaLunas,
            'beasiswaPakai' => $beasiswaPakai,
            'followUp' => $followUp,
            'konversiVerifikasi' => $total > 0 ? (int) round(($diverifikasi + $lulus) / $total * 100) : 0,
            'konversiLulus' => $total > 0 ? (int) round($lulus / $total * 100) : 0,
            'konversiBayar' => $vaTerbit > 0 ? (int) round($vaLunas / $vaTerbit * 100) : 0,
            'diajukan' => $diajukan,
        ]);
    }
}
