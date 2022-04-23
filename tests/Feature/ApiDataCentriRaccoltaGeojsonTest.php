<?php

namespace Tests\Feature;

use App\Models\WasteCollectionCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiDataCentriRaccoltaGeojsonTest extends TestCase
{
    // https://apiersu.netseven.it/data/centri_raccolta.geojson

    use RefreshDatabase;

    /** @test     */
    public function api_data_centri_raccolta_returns_200()
    {
        $z = WasteCollectionCenter::factory()->create();
        $response = $this->get('/api/c/'.$z->company->id.'/data/centri_raccolta.geojson');

        $response->assertStatus(200);
    }

        /** @test     */
        public function api_data_centri_raccolta_is_geojson()
        {
            $z = WasteCollectionCenter::factory()->create();
            $response = $this->get('/api/c/'.$z->company->id.'/data/centri_raccolta.geojson');
    
            $response->assertStatus(200);
            $geojson = $response->json();
    
            $this->assertArrayHasKey('type',$geojson);
            $this->assertArrayHasKey('features',$geojson);
    
            $this->assertEquals('FeatureCollection',$geojson['type']);

        }
    
    


}
