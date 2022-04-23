<?php

namespace Tests\Feature;

use App\Models\Company;
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

    /** @test     */
    public function api_data_centri_raccolta_has_proper_feature_section()
    {
        $c = Company::factory()->create();
        $z = WasteCollectionCenter::factory()->create(['company_id'=>$c->id]);
        $z = WasteCollectionCenter::factory()->create(['company_id'=>$c->id]);
        $response = $this->get('/api/c/'.$c->id.'/data/centri_raccolta.geojson');

        $response->assertStatus(200);
        $geojson = $response->json();

        $this->assertEquals(2,count($geojson['features']));

        $this->assertArrayHasKey('type',$geojson['features'][0]);
        $this->assertEquals('Feature',$geojson['features'][0]['type']);
        $this->assertArrayHasKey('properties',$geojson['features'][0]);
        $this->assertArrayHasKey('geometry',$geojson['features'][0]);

        $this->assertArrayHasKey('type',$geojson['features'][1]);
        $this->assertEquals('Feature',$geojson['features'][0]['type']);
        $this->assertArrayHasKey('properties',$geojson['features'][1]);
        $this->assertArrayHasKey('geometry',$geojson['features'][1]);

    }


}
