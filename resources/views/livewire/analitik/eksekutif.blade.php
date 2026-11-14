<div class="row row-cards">
    <div class="col-sm-6 col-lg-4">
        <div class="card card-sm"><div class="card-body">
            <div class="subheader">IPK Rata-rata (terkunci)</div>
            <div class="h1 mb-0">{{ number_format($kpi['ipk_rata'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card card-sm"><div class="card-body">
            <div class="subheader">Lulusan</div>
            <div class="h1 mb-0">{{ $kpi['lulusan'] }}</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card card-sm"><div class="card-body">
            <div class="subheader">Serapan Kerja (tracer)</div>
            <div class="h1 mb-0">{{ $kpi['serapan_kerja_pct'] }}%</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card card-sm"><div class="card-body">
            <div class="subheader">Rasio Mhs : Dosen</div>
            <div class="h1 mb-0">{{ $kpi['rasio_mhs_dosen'] }} : 1</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card card-sm"><div class="card-body">
            <div class="subheader">Realisasi Anggaran</div>
            <div class="h1 mb-0">{{ $kpi['realisasi_anggaran_pct'] }}%</div>
        </div></div>
    </div>
</div>
