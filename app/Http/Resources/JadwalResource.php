<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JadwalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id ?? null,
            'hari' => $this->resource->hari ?? null,
            'jam_mulai' => $this->resource->jam_mulai ?? null,
            'jam_selesai' => $this->resource->jam_selesai ?? null,
            'kelas' => $this->resource->nama ?? $this->resource->kelas ?? null,
            'ruang' => $this->whenLoaded('ruang', fn () => $this->resource->ruang?->nama),
            'matakuliah' => $this->whenLoaded('matakuliah', fn () => $this->resource->matakuliah?->nama),
            'dosen' => $this->whenLoaded('dosen', fn () => $this->resource->dosen?->nama ?? $this->resource->dosen?->user?->name),
        ];
    }
}
