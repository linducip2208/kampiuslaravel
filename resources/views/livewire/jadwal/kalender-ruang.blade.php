<div>
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <h3 class="card-title">Kalender Pemakaian Ruang</h3>
            <div class="ms-auto d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" wire:click="geser(-1)">‹ Minggu lalu</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="geser(1)">Minggu depan ›</button>
            </div>
        </div>
        @if($ruangs->isEmpty())
            <div class="card-body"><div class="alert alert-info mb-0">Belum ada data ruangan.</div></div>
        @else
        <div class="card-body">
            <select class="form-select mb-3" wire:model.live="ruangId" style="max-width:280px">
                <option value="">Semua ruangan</option>
                @foreach($ruangs as $r)
                    <option value="{{ $r->id }}">{{ $r->nama }} ({{ $r->gedung }})</option>
                @endforeach
            </select>
            <div class="table-responsive">
                <table class="table table-vcenter table-bordered">
                    <thead><tr><th style="min-width:140px">Hari / Tanggal</th>
                        @foreach($ruangs as $r)
                            @if(!$ruangId || $ruangId == $r->id)<th>{{ $r->nama }}</th>@endif
                        @endforeach
                    </tr></thead>
                    <tbody>
                        @foreach($this->minggu as $h)
                            <tr>
                                <td><strong>{{ $h['label'] }}</strong></td>
                                @foreach($ruangs as $r)
                                    @if(!$ruangId || $ruangId == $r->id)
                                        <td style="min-width:180px">
                                            @foreach(($kuliah[$r->id.'|'.$h['hari']] ?? []) as $k)
                                                <div><span class="badge bg-blue-lt">Kuliah</span>
                                                    {{ substr((string) $k->jam_mulai, 0, 5) }}–{{ substr((string) $k->jam_selesai, 0, 5) }}
                                                    {{ $k->matakuliah->nama ?? $k->nama }}</div>
                                            @endforeach
                                            @foreach(($booking[$r->id.'|'.$h['tanggal']] ?? []) as $b)
                                                <div><span class="badge bg-{{ $b->status === \App\Models\PeminjamanRuang::STATUS_DISETUJUI ? 'green' : 'yellow' }}-lt">{{ $b->status }}</span>
                                                    {{ substr((string) $b->jam_mulai, 0, 5) }}–{{ substr((string) $b->jam_selesai, 0, 5) }}
                                                    {{ $b->user->name ?? 'Pengaju' }}</div>
                                            @endforeach
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="text-secondary small">Kuliah = jadwal mingguan tetap. Booking = pengajuan disetujui/diajukan. Pengajuan baru via <a href="{{ route('fasilitas.pinjam') }}">Pinjam Ruang</a>.</div>
        </div>
        @endif
    </div>
</div>
