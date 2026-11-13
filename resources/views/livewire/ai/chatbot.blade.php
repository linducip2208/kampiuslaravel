<div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Chatbot Akademik</h3></div>
        <div class="card-body">
            @foreach($riwayat as $r)
                <div class="alert {{ $r['dari'] === 'ai' ? 'alert-info' : 'alert-secondary' }}">
                    <strong>{{ $r['dari'] === 'ai' ? 'SIMAK-AI' : 'Kamu' }}:</strong> {{ $r['teks'] }}
                </div>
            @endforeach
            @if(empty($riwayat))
                <p class="text-muted">Tanya soal IPK, tagihan, tugas, kehadiran, atau jadwal. Contoh: "berapa IPK saya?"</p>
            @endif
            <form wire:submit="kirim">
                <div class="input-group">
                    <input type="text" wire:model="pesan" class="form-control" placeholder="Tulis pertanyaan..." maxlength="2000">
                    <button class="btn btn-primary" type="submit">Kirim</button>
                </div>
                @error('pesan') <div class="text-danger">{{ $message }}</div> @enderror
            </form>
        </div>
    </div>
</div>
