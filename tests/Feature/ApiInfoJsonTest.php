<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class ApiInfoJsonTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 
     *
     * @return void
     * @test
     */
    public function when_visit_info_json_return_200()
    {
        $c = Company::factory()->create();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $c->id . '/config.json');

        $response->assertStatus(200);
    }

    /**
     * 
     *
     * @return void
     * @test
     */
    public function when_visit_info_json_return_json_with_proper_keys()
    {
        $c = Company::factory()->create();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $c->id . '/config.json');

        $j = $response->json();

        $this->assertIsArray($j);
        $this->assertArrayNotHasKey('data', $j);
        $this->assertIsArray($j['resources']);
        $this->assertArrayHasKey('icon', $j['resources']);
        $this->assertArrayHasKey('splash', $j['resources']);
    }

    /**
     * 
     *
     * @return void
     * @test
     */
    public function when_visit_info_json_return_json_with_proper_values()
    {
        $c = Company::factory()->create();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $c->id . '/config.json');

        $j = $response->json();

        $this->assertEquals(url('/storage/' . $c->icon), $j['resources']['icon']);
        $this->assertEquals(url('/storage/' . $c->splash), $j['resources']['splash']);
    }
}
