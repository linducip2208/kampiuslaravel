<div class="card">
    <div class="card-body d-flex align-items-center gap-3">
        <div>Total: <strong>{{ $total }}</strong> &middot; Terkunci: <strong>{{ $locked }}</strong> &middot; Kehadiran &lt; 75%: <strong>{{ $peringatan }}</strong></div>
        <div class="ms-auto d-flex gap-2">
            <button wire:click="kunci" class="btn btn-warning" onclick="return confirm('Kunci semua nilai kelas ini?')">Kunci Nilai</button>
            <button wire:click="buka" class="btn btn-outline-secondary" onclick="return confirm('Buka kunci nilai?')">Buka Kunci (Admin)</button>
        </div>
    </div>
    <div class="card-body border-top">
        <h4 class="card-title">Rekap EDOM Dosen</h4>
        @if(($edom['total_responden'] ?? 0) > 0)
            <div>Rata-rata skor: <strong>{{ $edom['avg_skor'] }}</strong> dari {{ $edom['total_responden'] }} responden ({{ $edom['total_kelas'] }} kelas).</div>
            <ul class="mt-2 mb-0">
                @foreach(($edom['per_kelas'] ?? []) as $rk)
                    <li>Kelas #{{ $rk['kelas_id'] }}: {{ $rk['avg_skor'] }} ({{ $rk['total_responden'] }} responden)</li>
                @endforeach
            </ul>
        @else
            <div class="text-secondary">Belum ada data EDOM untuk dosen pengampu kelas ini.</div>
        @endif
    </div>
</div>
