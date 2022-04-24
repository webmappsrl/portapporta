<?php

namespace Tests\Feature;

use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiDataZoneMetaJsonTest extends TestCase
{
    // https://apiersu.netseven.it/data/zone_meta.json

    use RefreshDatabase;

    /** @test     */
    public function zone_meta_returns_200()
    {
        $w = Zone::factory()->create();
        $response = $this->get('/api/c/'.$w->company->id.'/data/zone_meta.json');

        $response->assertStatus(200);
    }

}
