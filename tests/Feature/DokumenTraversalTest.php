<?php

namespace Tests\Feature;

use Tests\TestCase;

class DokumenTraversalTest extends TestCase
{
    public function test_dokumen_valid_tampil(): void
    {
        $this->get(route('dokumen.show', 'ARCHITECTURE'))->assertOk();
        $this->get(route('dokumen.show', 'CHANGELOG'))->assertOk();
    }

    public function test_traversal_ditolak_404(): void
    {
        foreach (['..', '.env', '../.env', 'composer', 'SECURITY.md'] as $coba) {
            $this->get('/dokumentasi/'.$coba)->assertNotFound();
        }
    }
}
