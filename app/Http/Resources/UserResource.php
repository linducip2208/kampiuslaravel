<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id ?? null,
            'name' => $this->resource->name ?? null,
            'email' => $this->resource->email ?? null,
            'roles' => $this->when(
                method_exists($this->resource, 'getRoleNames'),
                fn () => $this->resource->getRoleNames(),
            ),
            'mahasiswa' => $this->whenLoaded('mahasiswa', fn () => [
                'nim' => $this->resource->mahasiswa?->nim,
                'status' => $this->resource->mahasiswa?->status ?? null,
                'angkatan' => $this->resource->mahasiswa?->angkatan ?? null,
            ]),
            'dosen' => $this->whenLoaded('dosen', fn () => [
                'nidn' => $this->resource->dosen?->nidn,
                'status' => $this->resource->dosen?->status ?? null,
            ]),
        ];
    }
}
