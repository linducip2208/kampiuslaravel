<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Akun COA Baru</h3></div>
        <div class="card-body d-flex gap-2 align-items-end">
            <div><label class="form-label">Kode</label><input type="text" class="form-control" wire:model="coa_kode" maxlength="20"></div>
            <div><label class="form-label">Nama</label><input type="text" class="form-control" wire:model="coa_nama" maxlength="150"></div>
            <div>
                <label class="form-label">Tipe</label>
                <select class="form-select" wire:model="coa_tipe">
                    <option value="aset">Aset</option>
                    <option value="kewajiban">Kewajiban</option>
                    <option value="ekuitas">Ekuitas</option>
                    <option value="pendapatan">Pendapatan</option>
                    <option value="beban">Beban</option>
                </select>
            </div>
            <div><button class="btn btn-outline-primary" wire:click="simpanCoa">Tambah</button></div>
            @error('coa_kode')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Jurnal Umum Baru</h3></div>
        <div class="card-body">
            <div class="row g-2 mb-2">
                <div class="col-md-3">
                    <label class="form-label">Tahun akademik</label>
                    <select class="form-select" wire:model.live="tahunId">
                        @foreach($tahuns as $t)
                            <option value="{{ $t->id }}">{{ $t->tahun }} · {{ ucfirst($t->semester) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tanggal</label>
                    <input type="date" class="form-control" wire:model="tanggal">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Keterangan</label>
                    <input type="text" class="form-control" wire:model="keterangan">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Referensi</label>
                    <input type="text" class="form-control" wire:model="referensi">
                </div>
            </div>
            @foreach($baris as $i => $b)
                <div class="row g-2 mb-1">
                    <div class="col-md-5">
                        <select class="form-select" wire:model="baris.{{ $i }}.coa_id">
                            <option value="0">— akun —</option>
                            @foreach($coas as $c)
                                <option value="{{ $c->id }}">{{ $c->kode }} — {{ $c->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><input type="number" class="form-control" wire:model="baris.{{ $i }}.debet" min="0" placeholder="Debet"></div>
                    <div class="col-md-3"><input type="number" class="form-control" wire:model="baris.{{ $i }}.kredit" min="0" placeholder="Kredit"></div>
                    <div class="col-md-1"><button class="btn btn-sm btn-outline-danger" wire:click="hapusBaris({{ $i }})">x</button></div>
                </div>
            @endforeach
            <div class="d-flex gap-2 mt-2">
                <button class="btn btn-outline-secondary" wire:click="tambahBaris">+ Baris</button>
                <button class="btn btn-primary" wire:click="posting">Posting (wajib seimbang)</button>
            </div>
            @error('baris')<div class="text-danger">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Neraca Saldo @if($neraca) — {{ $neraca['seimbang'] ? 'SEIMBANG' : 'TIDAK SEIMBANG' }} @endif</h3></div>
        @if($neraca)
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Kode</th><th>Akun</th><th>Debet</th><th>Kredit</th></tr></thead>
                    <tbody>
                        @foreach($neraca['rows'] as $r)
                            <tr><td>{{ $r['kode'] }}</td><td>{{ $r['nama'] }}</td><td>{{ number_format($r['debet'], 0, ',', '.') }}</td><td>{{ number_format($r['kredit'], 0, ',', '.') }}</td></tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr><th colspan="2">Total</th><th>{{ number_format($neraca['total_debet'], 0, ',', '.') }}</th><th>{{ number_format($neraca['total_kredit'], 0, ',', '.') }}</th></tr></tfoot>
                </table>
            </div>
        @endif
    </div>
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Laba-Rugi @if($labarugi) — Surplus: Rp {{ number_format($labarugi['surplus'], 0, ',', '.') }} @endif</h3></div>
        @if($labarugi)
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Kode</th><th>Akun</th><th>Kelompok</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @foreach($labarugi['pendapatan'] as $r)
                            <tr><td>{{ $r['kode'] }}</td><td>{{ $r['nama'] }}</td><td><span class="badge bg-green-lt">pendapatan</span></td><td class="text-end">{{ number_format($r['total'], 0, ',', '.') }}</td></tr>
                        @endforeach
                        @foreach($labarugi['beban'] as $r)
                            <tr><td>{{ $r['kode'] }}</td><td>{{ $r['nama'] }}</td><td><span class="badge bg-red-lt">beban</span></td><td class="text-end">({{ number_format($r['total'], 0, ',', '.') }})</td></tr>
                        @endforeach
                        @if(empty($labarugi['pendapatan']) && empty($labarugi['beban']))
                            <tr><td colspan="4" class="text-secondary">Belum ada jurnal pendapatan/beban periode ini.</td></tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr><th colspan="3">Total pendapatan</th><th class="text-end">{{ number_format($labarugi['total_pendapatan'], 0, ',', '.') }}</th></tr>
                        <tr><th colspan="3">Total beban</th><th class="text-end">{{ number_format($labarugi['total_beban'], 0, ',', '.') }}</th></tr>
                        <tr><th colspan="3">Surplus / (Defisit)</th><th class="text-end">{{ number_format($labarugi['surplus'], 0, ',', '.') }}</th></tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Buku Besar</h3></div>
        <div class="card-body d-flex gap-2">
            <select class="form-select" wire:model.live="bukuCoaId" style="max-width:320px">
                <option value="">— pilih akun —</option>
                @foreach($coas as $c)
                    <option value="{{ $c->id }}">{{ $c->kode }} — {{ $c->nama }}</option>
                @endforeach
            </select>
        </div>
        @if($buku)
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Debet</th><th>Kredit</th><th>Saldo</th></tr></thead>
                    <tbody>
                        @foreach($buku['rows'] as $r)
                            <tr><td>{{ $r['tanggal'] }}</td><td>{{ $r['keterangan'] }}</td><td>{{ number_format($r['debet'], 0, ',', '.') }}</td><td>{{ number_format($r['kredit'], 0, ',', '.') }}</td><td>{{ number_format($r['saldo'], 0, ',', '.') }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">Saldo akhir: <strong>{{ number_format($buku['saldo_akhir'], 0, ',', '.') }}</strong></div>
        @endif
    </div>
</div>
