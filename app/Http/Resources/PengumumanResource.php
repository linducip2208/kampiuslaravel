<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengumumanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id ?? null,
            'judul' => $this->resource->judul ?? null,
            'isi' => $this->resource->isi ?? null,
            'target' => $this->resource->target ?? null,
            'published_at' => $this->resource->published_at ?? null,
        ];
    }
}
