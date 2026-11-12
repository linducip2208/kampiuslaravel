<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagihanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $nominal = (float) ($this->resource->nominal ?? 0);
        $terbayar = (float) ($this->resource->terbayar ?? 0);

        return [
            'id' => $this->resource->id ?? null,
            'jenis' => $this->resource->jenis ?? null,
            'nominal' => $nominal,
            'terbayar' => $terbayar,
            'sisa' => $nominal - $terbayar,
            'status' => $this->resource->status ?? null,
            'tahun_akademik' => $this->whenLoaded('tahunAkademik', fn () => $this->resource->tahunAkademik?->tahun),
        ];
    }
}
