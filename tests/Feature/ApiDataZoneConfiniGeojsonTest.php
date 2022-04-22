<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiDataZoneConfiniGeojsonTest extends TestCase
{
    // https://apiersu.netseven.it/data/zone_confini.geojson

    use RefreshDatabase;

    /** @test     */
    public function api_data_confini_return_200()
    {
        $z = Zone::factory()->create();
        $response = $this->get('/api/c/'.$z->company->id.'/data/zone_confini.geojson');

        $response->assertStatus(200);
    }

    /** @test     */
    public function api_data_confini_is_geojson()
    {
        $z = Zone::factory()->create();
        $response = $this->get('/api/c/'.$z->company->id.'/data/zone_confini.geojson');

        $response->assertStatus(200);
        $geojson = $response->json();

        $this->assertArrayHasKey('type',$geojson);
        $this->assertArrayHasKey('name',$geojson);
        $this->assertArrayHasKey('features',$geojson);

        $this->assertEquals('FeatureCollection',$geojson['type']);
        $this->assertEquals('zone_confini',$geojson['name']);
    }

    /** @test     */
    public function api_data_zone_confini_has_proper_feature_section()
    {
        $c = Company::factory()->create();
        $z = Zone::factory()->create(['company_id'=>$c->id]);
        $z = Zone::factory()->create(['company_id'=>$c->id]);
        $response = $this->get('/api/c/'.$c->id.'/data/zone_confini.geojson');

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
