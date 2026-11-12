<div>
    @if ($message)<p>{{ $message }}</p>@endif
    <select wire:model.live="statusFilter">
        <option value="pending">pending</option>
        <option value="verified">verified</option>
        <option value="rejected">rejected</option>
        <option value="semua">semua</option>
    </select>
    <ul>
        @foreach ($pembayarans as $bayar)
            <li>#{{ $bayar->id }} {{ $bayar->nominal }} — {{ $bayar->status }}
                <button wire:click="verify({{ $bayar->id }})">Verifikasi</button>
                <button wire:click="reject({{ $bayar->id }})">Tolak</button>
            </li>
        @endforeach
    </ul>
    {{ $pembayarans->links() }}
</div>
