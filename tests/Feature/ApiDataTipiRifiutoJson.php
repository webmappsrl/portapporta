<?php

namespace Tests\Feature;

use App\Models\TrashType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiDataTipiRifiutoJson extends TestCase
{
        // https://apiersu.netseven.it/data/tipi_rifiuto.json

        use RefreshDatabase;

        /** @test     */
        public function when_visit_utenze_meta_json_return_200()
        {
            $tt = TrashType::factory()->create();
            $response = $this->get('/api/c/'.$tt->company->id.'/data/tipi_rifiuto.json');
    
            $response->assertStatus(200);
        }
    
    
}
