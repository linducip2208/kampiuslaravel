<div>
    @if($message)
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <form wire:submit="save">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Payment Gateway (VA)</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Driver</label>
                        <select wire:model="pay_driver" class="form-select">
                            @foreach($drivers as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('pay_driver')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3"><label class="form-label">Server key / API key</label><input type="password" wire:model="pay_server_key" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Base URL (kosong = default config)</label><input type="text" wire:model="pay_base_url" class="form-control" placeholder="https://api.sandbox.midtrans.com"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Bank VA (bni/bri/bca/mandiri)</label><input type="text" wire:model="pay_bank" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Callback token (Xendit)</label><input type="password" wire:model="pay_callback_token" class="form-control"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Merchant code (Tripay)</label><input type="text" wire:model="pay_merchant_code" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Private key (Tripay)</label><input type="password" wire:model="pay_private_key" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Channel (Tripay, mis. BRIVA/QRIS)</label><input type="text" wire:model="pay_method" class="form-control"></div>
                </div>
                <button type="button" wire:click="test('tripay')" class="btn btn-outline-secondary mb-2">Tes Koneksi Tripay</button>
                @if(!empty($tes['tripay']))<div class="alert alert-info">{{ $tes['tripay'] }}</div>@endif
                <div class="text-muted small">QRIS &amp; e-wallet (GoPay/OVO/DANA/ShopeePay/LinkAja) dibuat dari halaman Bayar — pilih tombol kanal. Tripay: atur channel default di atas (BRIVA/QRIS). Midtrans: GoPay &amp; ShopeePay didukung; OVO/DANA/LinkAja via Tripay/Xendit.</div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">WhatsApp Penagihan (H-7 / H+7)</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Gateway URL (POST, Fonnte/Wablas-kompatibel)</label><input type="text" wire:model="wa_gateway_url" class="form-control" placeholder="https://api.fonnte.com/send"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Token / API key</label><input type="password" wire:model="wa_token" class="form-control"></div>
                </div>
                <div class="text-muted small">Jadwal: <code>tagihan:ingatkan</code> tiap 08:00. Nomor diambil dari profil user (<code>no_wa</code>). Tanpa gateway: pesan hanya masuk inbox notifikasi.</div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Pajak Kwitansi</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">NPWP Kampus</label><input type="text" wire:model="pajak_npwp" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">PPN % (0 = jasa pendidikan non-PPN)</label><input type="text" wire:model="pajak_ppn_persen" class="form-control">
                        @error('pajak_ppn_persen')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Push (FCM)</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Project ID</label><input type="text" wire:model="fcm_project_id" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Service account JSON path</label><input type="text" wire:model="fcm_service_account_path" class="form-control" placeholder="/secure/firebase-adminsdk.json"></div>
                </div>
                <button type="button" wire:click="test('fcm')" class="btn btn-outline-secondary mb-2">Tes Konfigurasi FCM</button>
                @if(!empty($tes['fcm']))<div class="alert alert-info">{{ $tes['fcm'] }}</div>@endif
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Feeder PDDikti</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Base URL Feeder</label><input type="text" wire:model="pddikti_base_url" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Username</label><input type="text" wire:model="pddikti_username" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Password</label><input type="password" wire:model="pddikti_password" class="form-control"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Live-sync</label>
                        <select wire:model="pddikti_live_enabled" class="form-select">
                            <option value="0">Mati (export CSV manual)</option>
                            <option value="1">Aktif (push per-record)</option>
                        </select>
                    </div>
                </div>
                <button type="button" wire:click="test('pddikti')" class="btn btn-outline-secondary mb-2">Tes Login Feeder</button>
                @if(!empty($tes['pddikti']))<div class="alert alert-info">{{ $tes['pddikti'] }}</div>@endif
                <div class="text-muted small">Live-sync: <code>POST /pddikti/sync {tabel, record}</code> · status: <code>GET /pddikti/status</code> · Sinta/Garuda/TTE: <code>POST /pddikti/{sinta,garuda,tte}</code>.</div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">SSO — Google &amp; Microsoft</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Google Client ID</label><input type="text" wire:model="sso_google_client_id" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Google Client Secret</label><input type="password" wire:model="sso_google_client_secret" class="form-control"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Microsoft Client ID</label><input type="text" wire:model="sso_microsoft_client_id" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Microsoft Client Secret</label><input type="password" wire:model="sso_microsoft_client_secret" class="form-control"></div>
                </div>
                <button type="button" wire:click="test('sso_google')" class="btn btn-outline-secondary mb-2">Tes Google</button>
                <button type="button" wire:click="test('sso_microsoft')" class="btn btn-outline-secondary mb-2">Tes Microsoft</button>
                @if(!empty($tes['sso_google']))<div class="alert alert-info">{{ $tes['sso_google'] }}</div>@endif
                @if(!empty($tes['sso_microsoft']))<div class="alert alert-info">{{ $tes['sso_microsoft'] }}</div>@endif
                <div class="text-muted small">Butuh <code>laravel/socialite</code> + route <code>/auth/{google,microsoft}/callback</code> (lihat docs INTEGRASI §4). Tanpa itu, login password tetap berfungsi.</div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Riset — Sinta &amp; Garuda</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Sinta Base URL</label><input type="text" wire:model="sinta_base_url" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Sinta API Key</label><input type="password" wire:model="sinta_api_key" class="form-control"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Garuda Base URL</label><input type="text" wire:model="garuda_base_url" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Garuda API Key</label><input type="password" wire:model="garuda_api_key" class="form-control"></div>
                </div>
                <button type="button" wire:click="test('sinta')" class="btn btn-outline-secondary mb-2">Tes Sinta</button>
                <button type="button" wire:click="test('garuda')" class="btn btn-outline-secondary mb-2">Tes Garuda</button>
                @if(!empty($tes['sinta']))<div class="alert alert-info">{{ $tes['sinta'] }}</div>@endif
                @if(!empty($tes['garuda']))<div class="alert alert-info">{{ $tes['garuda'] }}</div>@endif
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">TTE BSrE</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Base URL BSrE</label><input type="text" wire:model="tte_base_url" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Username</label><input type="text" wire:model="tte_username" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Password</label><input type="password" wire:model="tte_password" class="form-control"></div>
                </div>
                <button type="button" wire:click="test('tte')" class="btn btn-outline-secondary mb-2">Tes Koneksi TTE</button>
                @if(!empty($tes['tte']))<div class="alert alert-info">{{ $tes['tte'] }}</div>@endif
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Storage — R2/S3 + CDN</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">CDN URL</label><input type="text" wire:model="storage_cdn_url" class="form-control" placeholder="https://cdn.kampus.ac.id"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">R2 Endpoint</label><input type="text" wire:model="storage_r2_endpoint" class="form-control" placeholder="https://xxx.r2.cloudflarestorage.com"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">R2 Bucket</label><input type="text" wire:model="storage_r2_bucket" class="form-control"></div>
                </div>
                <button type="button" wire:click="test('storage')" class="btn btn-outline-secondary mb-2">Tes Konfigurasi Storage</button>
                @if(!empty($tes['storage']))<div class="alert alert-info">{{ $tes['storage'] }}</div>@endif
                <div class="text-muted small">Key/secret R2 tetap di <code>.env</code> (<code>R2_*</code>); halaman ini hanya endpoint+bucket+CDN. Disk: <code>s3</code> (AWS) atau <code>r2</code> (Cloudflare) via <code>FILESYSTEM_DISK</code>.</div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">API Publik v2 &amp; Webhook</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Webhook URL keluar (opsional)</label><input type="text" wire:model="webhook_url" class="form-control" placeholder="https://sistem-lain/webhook"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Webhook secret (min. 16 karakter)</label><input type="password" wire:model="webhook_secret" class="form-control"></div>
                </div>
                <button type="button" wire:click="test('webhook')" class="btn btn-outline-secondary mb-2">Tes Konfigurasi Webhook</button>
                @if(!empty($tes['webhook']))<div class="alert alert-info">{{ $tes['webhook'] }}</div>@endif
                <div class="text-muted small">Publik: <code>GET /api/v2/{status,prodi,pengumuman}</code> · masuk: <code>POST /api/v2/webhook/terima</code> (header <code>X-Webhook-Signature</code> = HMAC-SHA256 raw body).</div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Multi-tenant (sketsa)</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Mode tenant</label>
                        <select wire:model="tenant_mode" class="form-select">
                            <option value="single">Single (saat ini)</option>
                            <option value="sketsa_multi">Sketsa multi (fixed-schema + tenant_id)</option>
                        </select>
                    </div>
                </div>
                <div class="text-muted small">Sketsa: tambah kolom <code>tenant_id</code> + global scope per model + middleware pemilih tenant per domain. Belum diaktifkan — lihat docs INTEGRASI §10 sebelum migrasi.</div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">ComfyUI (Generate Gambar)</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Server URL</label><input type="text" wire:model="comfyui_url" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Model</label><input type="text" wire:model="comfyui_model" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Workflow API-format (opsional)</label><input type="text" wire:model="comfyui_workflow_file" class="form-control" placeholder="/path/wan21_api.json"></div>
                </div>
                <button type="button" wire:click="test('comfyui')" class="btn btn-outline-secondary mb-2">Tes Koneksi ComfyUI</button>
                @if(!empty($tes['comfyui']))<div class="alert alert-info">{{ $tes['comfyui'] }}</div>@endif
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">AI — Chatbot & Analitik</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3"><label class="form-label">Driver</label>
                        <select wire:model="ai_driver" class="form-select">
                            <option value="simulated">Simulasi (dev)</option>
                            <option value="openai_compatible">OpenAI-compatible</option>
                        </select>
                    </div>
                    <div class="col-md-5 mb-3"><label class="form-label">Base URL</label><input type="text" wire:model="ai_base_url" class="form-control" placeholder="https://api.openai.com/v1"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Model</label><input type="text" wire:model="ai_model" class="form-control" placeholder="gpt-4o-mini"></div>
                    <div class="col-md-12 mb-3"><label class="form-label">API Key</label><input type="password" wire:model="ai_api_key" class="form-control"></div>
                </div>
                <button type="button" wire:click="test('ai')" class="btn btn-outline-secondary mb-2">Tes Koneksi AI</button>
                @if(!empty($tes['ai']))<div class="alert alert-info">{{ $tes['ai'] }}</div>@endif
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
</div>
