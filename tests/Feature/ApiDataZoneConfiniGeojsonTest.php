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
    public function api_data_zone_confini_return_200()
    {
        $z = Zone::factory()->create();
        $response = $this->get('/api/c/'.$z->company->id.'/data/zone_confini.geojson');

        $response->assertStatus(200);
    }

    /** @test     */
    public function api_data_zone_confini_is_geojson()
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
    /** @test     */
    public function api_data_zone_confini_has_proper_properties_feature_section()
    {
        $z = Zone::factory()->create();
        $response = $this->get('/api/c/'.$z->company->id.'/data/zone_confini.geojson');

        $response->assertStatus(200);
        $geojson = $response->json();

        $properties = $geojson['features'][0]['properties'];

        $this->assertArrayHasKey('id',$properties);
        $this->assertEquals($z->id,$properties['id']);

        $this->assertArrayHasKey('COMUNE',$properties);
        $this->assertEquals($z->comune,$properties['COMUNE']);

    }
    /** @test     */
    public function api_data_zone_confini_has_proper_geometry_feature_section()
    {
        $z = Zone::factory()->create();
        $response = $this->get('/api/c/'.$z->company->id.'/data/zone_confini.geojson');

        $response->assertStatus(200);
        $geojson = $response->json();

        $geometry = $geojson['features'][0]['geometry'];

        $this->assertArrayHasKey('type',$geometry);
        $this->assertEquals('MultiPolygon',$geometry['type']);

        $this->assertArrayHasKey('coordinates',$geometry);
        $this->assertIsArray($geometry['coordinates']);
        $this->assertIsArray($geometry['coordinates'][0]);
        $this->assertIsArray($geometry['coordinates'][0][0]);
        $this->assertEquals(5,count($geometry['coordinates'][0][0]));
        $this->assertEquals(2,count($geometry['coordinates'][0][0][0]));

    }

}
