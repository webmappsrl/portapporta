<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\TrashType;
use App\Models\Waste;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiDataRifiutarioTest extends TestCase
{
    // REF: https://apiersu.netseven.it/data/rifiutario.json

    use RefreshDatabase;

    /** @test     */
    public function rifiutario_returns_200()
    {
        $w = Waste::factory()->create();
        $response = $this->get('/api/c/'.$w->company->id.'/wastes.json');

        $response->assertStatus(200);
    }

    /** @test     */
    public function rifiutario_has_proper_structure()
    {
        $c = Company::factory()->create();
        Waste::factory(10)->create(['company_id'=>$c->id]);
        $response = $this->get('/api/c/'.$c->id.'/wastes.json');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(10,count($json));
    }

    /** @test     */
    public function rifiutario_has_proper_item_keys()
    {
        $c = Company::factory()->create();
        Waste::factory(10)->create(['company_id'=>$c->id]);
        $response = $this->get('/api/c/'.$c->id.'/wastes.json');

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

    /** @test     */
    public function rifiutario_has_proper_item_content()
    {
        $c = Company::factory()->create();
        $w = Waste::factory()->create(['company_id'=>$c->id]);
        $response = $this->get('/api/c/'.$c->id.'/wastes.json');

        $response->assertStatus(200);
        $json = $response->json();
        $item = $json[0];

        $this->assertEquals($w->getTranslation('name','it'),$item['name']);
        $this->assertEquals($w->getTranslation('where','it'),$item['where']);
        $this->assertEquals($w->getTranslation('notes','it'),$item['notes']);
        $this->assertEquals($w->getTranslation('name','en'),$item['translations']['en']['name']);
        $this->assertEquals($w->getTranslation('where','en'),$item['translations']['en']['where']);
        $this->assertEquals($w->getTranslation('notes','en'),$item['translations']['en']['notes']);
        $this->assertEquals($w->pap,$item['pap']);
        $this->assertEquals($w->delivery,$item['delivery']);
        $this->assertEquals($w->collection_center,$item['collection_center']);
    }
    /** @test     */
    public function rifiutario_has_proper_item_category_section()
    {
        $c = Company::factory()->create();
        $tt = TrashType::factory()->create(['company_id'=>$c->id]);
        $w = Waste::factory()->create(['company_id'=>$c->id,'trash_type_id'=>$tt->id]);

        $response = $this->get('/api/c/'.$c->id.'/wastes.json');

        $response->assertStatus(200);
        $json = $response->json();
        $item = $json[0];

        $this->assertArrayHasKey('trash_type_id',$item);
        $this->assertEquals($tt->id,$item['trash_type_id']);

    }
}
