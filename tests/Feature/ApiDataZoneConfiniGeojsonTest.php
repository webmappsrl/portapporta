<?php

namespace Tests\Feature;

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

}
