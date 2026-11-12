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
                <button type="button" wire:click="test('pddikti')" class="btn btn-outline-secondary mb-2">Tes Login Feeder</button>
                @if(!empty($tes['pddikti']))<div class="alert alert-info">{{ $tes['pddikti'] }}</div>@endif
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">ComfyUI (Generate Gambar)</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Server URL</label><input type="text" wire:model="comfyui_url" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Model</label><input type="text" wire:model="comfyui_model" class="form-control"></div>
                </div>
                <button type="button" wire:click="test('comfyui')" class="btn btn-outline-secondary mb-2">Tes Koneksi ComfyUI</button>
                @if(!empty($tes['comfyui']))<div class="alert alert-info">{{ $tes['comfyui'] }}</div>@endif
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
</div>
