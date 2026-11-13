<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastTemplate extends Model
{
    protected $table = 'broadcast_templates';

    protected $fillable = ['nama', 'role_target', 'judul', 'isi'];

    public function render(array $vars = []): array
    {
        $out = ['judul' => $this->judul, 'isi' => $this->isi];
        foreach ($vars as $key => $val) {
            $out['judul'] = str_replace('{'.$key.'}', (string) $val, $out['judul']);
            $out['isi'] = str_replace('{'.$key.'}', (string) $val, $out['isi']);
        }

        return $out;
    }
}
