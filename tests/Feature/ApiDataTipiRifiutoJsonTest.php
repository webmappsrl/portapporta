<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\TrashType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiDataTipiRifiutoJsonTest extends TestCase
{
        // https://apiersu.netseven.it/data/tipi_rifiuto.json

        use RefreshDatabase;

        /** @test     */
        public function tipi_rifiuto_returns_200()
        {
            $tt = TrashType::factory()->create();
            $response = $this->get('/api/c/'.$tt->company->id.'/data/tipi_rifiuto.json');
    
            $response->assertStatus(200);
        }

        /** @test     */
        public function tipi_rifiuto_has_proper_keys()
        {
            $company = Company::factory()->create();
            $tt1 = TrashType::factory()->create(['company_id'=>$company->id]);
            $tt2 = TrashType::factory()->create(['company_id'=>$company->id]);
            $response = $this->get('/api/c/'.$company->id.'/data/tipi_rifiuto.json');
    
            $response->assertStatus(200);
            $json = $response->json();

            $this->assertArrayHasKey($tt1->slug,$json);
            $this->assertArrayHasKey($tt2->slug,$json);
        }
    
}
