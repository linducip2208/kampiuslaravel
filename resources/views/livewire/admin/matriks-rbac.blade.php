<div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Matriks Peran × Izin (baca-saja)</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter table-sm">
                <thead><tr><th>Peran</th><th>Izin (× = punya)</th></tr></thead>
                <tbody>
                    @foreach($roles as $r)
                        <tr>
                            <td><strong>{{ $r['name'] }}</strong></td>
                            <td>
                                @foreach($permissions as $p)
                                    <span class="badge {{ isset($r['milik'][$p]) ? 'bg-green-lt' : 'bg-secondary-lt' }} me-1 mb-1" title="{{ $p }}">{{ isset($r['milik'][$p]) ? '×' : '–' }} {{ $p }}</span>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer text-secondary small">Sumber: tabel roles/permissions (Spatie). Dokumen: <code>docs/FINAL_RBAC_MATRIX.md</code>. Perubahan peran dilakukan di <a href="{{ route('admin.users') }}">Kelola User</a>; riwayat di <a href="{{ route('admin.audit') }}">Audit Log</a>.</div>
    </div>
</div>
