<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NilaiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id ?? null,
            'na' => $this->resource->na ?? null,
            'huruf' => $this->resource->huruf ?? null,
            'bobot' => $this->resource->bobot ?? null,
            'sks' => $this->whenLoaded('kelas', fn () => $this->resource->kelas?->matakuliah?->sks),
            'matakuliah' => $this->whenLoaded('kelas', fn () => $this->resource->kelas?->matakuliah?->nama),
        ];
    }
}
