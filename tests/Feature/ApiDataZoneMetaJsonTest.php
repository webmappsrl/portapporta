<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\UserType;
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

    /** @test    */
    public function zone_meta_has_proper_structure()
    {
        // Prepare
        $c = Company::factory()->create();
        $zs = Zone::factory(10)->create(['company_id'=>$c->id]);
        foreach ($zs as $z) {
            $uts =  UserType::factory(3)->create(['company_id'=>$c->id]);
            $z->userTypes()->attach($uts->pluck('id')->toArray());    
        }

        // Fire
        $response = $this->get('/api/c/'.$c->id.'/data/zone_meta.json');
        $json = $response->json();

        // Check
        $this->assertIsArray($json);
        $this->assertEquals(10,count($json));
        $item = $json[0];
        $this->assertArrayHasKey('id',$item);
        $this->assertArrayHasKey('comune',$item);
        $this->assertArrayHasKey('label',$item);
        $this->assertArrayHasKey('url',$item);
        $this->assertArrayHasKey('types',$item);

    }

    /** @test    */
    public function zone_meta_has_proper_content()
    {
        // Prepare
        $c = Company::factory()->create();
        $z = Zone::factory()->create(['company_id'=>$c->id]);
        $uts =  UserType::factory(3)->create(['company_id'=>$c->id]);
        $z->userTypes()->attach($uts->pluck('id')->toArray());    

        // Fire
        $response = $this->get('/api/c/'.$c->id.'/data/zone_meta.json');
        $json = $response->json();

        // Check
        $item = $json[0];
        $this->assertEquals($z->id,$item['id']);
        $this->assertEquals($z->label,$item['label']);
        $this->assertEquals($z->comune,$item['comune']);
        $this->assertEquals($z->url,$item['url']);

        $this->assertIsArray($item['types']);
        $this->assertEquals(3,count($item['types']));
        foreach($uts as $ut) {
            $this->assertContains($ut->slug,$item['types']);
        }

    }

}
