<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

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
        $response = $this->get('/api/c/'.$c->id.'/info.json');

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
        $response = $this->get('/api/c/'.$c->id.'/info.json');

        $j = $response->json();

        $this->assertIsArray($j);
        $this->assertArrayNotHasKey('data',$j);
        $this->assertArrayHasKey('configTs',$j);
        $this->assertArrayHasKey('configJson',$j);
        $this->assertArrayHasKey('config.xml',$j);
        $this->assertIsArray($j['config.xml']);
        $this->assertArrayHasKey('id',$j['config.xml']);
        $this->assertArrayHasKey('description',$j['config.xml']);
        $this->assertArrayHasKey('name',$j['config.xml']);
        $this->assertArrayHasKey('version',$j['config.xml']);
        $this->assertIsArray($j['resources']);
        $this->assertArrayHasKey('icon',$j['resources']);
        $this->assertArrayHasKey('splash',$j['resources']);
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
        $response = $this->get('/api/c/'.$c->id.'/info.json');

        $j = $response->json();

        $this->assertEquals(url('/storage/'.$c->configTs),$j['configTs']);
        $this->assertEquals(url('/storage/'.$c->configJson),$j['configJson']);
        $this->assertEquals(url('/storage/'.$c->icon),$j['resources']['icon']);
        $this->assertEquals(url('/storage/'.$c->splash),$j['resources']['splash']);

        $this->assertEquals($c->configXMLID,$j['config.xml']['id']);
        $this->assertEquals($c->description,$j['config.xml']['description']);
        $this->assertEquals($c->name,$j['config.xml']['name']);
        $this->assertEquals($c->version,$j['config.xml']['version']);
    }
}
