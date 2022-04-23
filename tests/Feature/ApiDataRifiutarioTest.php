<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Waste;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiDataRifiutarioTest extends TestCase
{
    // https://apiersu.netseven.it/data/rifiutario.json

    use RefreshDatabase;

    /** @test     */
    public function rifiutario_returns_200()
    {
        $w = Waste::factory()->create();
        $response = $this->get('/api/c/'.$w->company->id.'/data/rifiutario.json');

        $response->assertStatus(200);
    }

    /** @test     */
    public function rifiutario_has_proper_structure()
    {
        $c = Company::factory()->create();
        Waste::factory(10)->create(['company_id'=>$c->id]);
        $response = $this->get('/api/c/'.$c->id.'/data/rifiutario.json');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(10,count($json));
    }

    /** @test     */
    public function rifiutario_has_proper_item_keys()
    {
        $c = Company::factory()->create();
        Waste::factory(10)->create(['company_id'=>$c->id]);
        $response = $this->get('/api/c/'.$c->id.'/data/rifiutario.json');

        $response->assertStatus(200);
        $json = $response->json();
        $item = $json[0];
        $this->assertArrayHasKey('name',$item);
        $this->assertArrayHasKey('where',$item);
        $this->assertArrayHasKey('notes',$item);
        $this->assertArrayHasKey('pap',$item);
        $this->assertArrayHasKey('collection_center',$item);
        $this->assertArrayHasKey('delivery',$item);
        $this->assertArrayHasKey('translations',$item);
    }
}
