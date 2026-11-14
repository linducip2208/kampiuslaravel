<div>
    @if(!empty($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @endif
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    @if(!$quiz)
        <div class="alert alert-info">Buka dengan <code>?quiz=ID</code> untuk mengerjakan kuis.</div>
    @else
        <div class="card mb-3">
            <div class="card-body">
                <h3 class="card-title">{{ $quiz->judul }}</h3>
                <div class="text-secondary">
                    Durasi: {{ $quiz->durasi_menit }} menit ·
                    Status: {{ $attempt->status ?? 'belum mulai' }} ·
                    @if(!is_null($sisaDetik) && $sisaDetik !== PHP_INT_MAX)
                        Sisa: {{ intdiv($sisaDetik, 60) }}:{{ str_pad((string) ($sisaDetik % 60), 2, '0', STR_PAD_LEFT) }}
                    @endif
                    @if($attempt && $attempt->status === 'selesai')
                        · Nilai: <strong>{{ $attempt->total_skor }}</strong>
                    @endif
                </div>
                @if(!$attempt)
                    @if(!empty($quiz->token))
                        <div class="mt-2" style="max-width:280px">
                            <label class="form-label">Token CBT</label>
                            <input type="text" class="form-control" wire:model="tokenCbt" maxlength="12" placeholder="Token dari pengawas">
                        </div>
                    @endif
                    <button class="btn btn-primary mt-2" wire:click="mulai">Mulai Kerjakan</button>
                @endif
            </div>
        </div>
        @if($attempt && $attempt->status === 'berjalan')
            @foreach($quiz->soal as $i => $s)
                <div class="card mb-2">
                    <div class="card-body">
                        <div><strong>Soal {{ $i + 1 }}</strong> <span class="badge bg-secondary">{{ $s->tipe }} · {{ $s->bobot }}</span></div>
                        <p class="mt-1">{{ $s->pertanyaan }}</p>
                        @if($s->tipe === 'pg' && !empty($s->opsi))
                            @foreach((array) $s->opsi as $oi => $op)
                                <label class="d-block">
                                    <input type="radio" name="soal-{{ $s->id }}" value="{{ chr(65 + $oi) }}"
                                        @checked(($tersimpan[$s->id] ?? null) === chr(65 + $oi))
                                        wire:change="autosave({{ $s->id }}, $event.target.value)">
                                    {{ chr(65 + $oi) }}. {{ $op }}
                                </label>
                            @endforeach
                        @else
                            <textarea class="form-control" rows="3" wire:model.blur="jawaban.{{ $s->id }}"
                                placeholder="{{ $s->tipe === 'pg' ? 'Jawaban (A-F)' : 'Tulis jawaban...' }}">{{ $tersimpan[$s->id] ?? '' }}</textarea>
                        @endif
                    </div>
                </div>
            @endforeach
            <button class="btn btn-success" wire:click="submit">Kumpulkan Jawaban</button>
            <div class="text-secondary small mt-1">Jawaban tersimpan otomatis tiap perubahan.</div>
        @endif
    @endif
</div>
