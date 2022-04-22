<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Nova\Console\RenamesStubs;
use Tests\TestCase;

class ApiDataUtenzeMetaJsonTest extends TestCase
{

    // https://apiersu.netseven.it/data/utenze_meta.json

    use RefreshDatabase;

    /** @test     */
    public function when_visit_utenze_meta_json_return_200()
    {
        $ut = UserType::factory()->create();
        $response = $this->get('/api/c/'.$ut->company->id.'/data/utenze_meta.json');

        $response->assertStatus(200);
    }

    /** @test     */
    public function when_company_has_two_user_type_then_meta_json_has_two_elements() {
        $c = Company::factory()->create();
        $ut1 = UserType::factory()->create(['company_id'=>$c->id]);
        $ut2 = UserType::factory()->create(['company_id'=>$c->id]);
        $response = $this->get('/api/c/'.$c->id.'/data/utenze_meta.json');      
        $json = $response->json();

        var_dump($json);

        $this->assertEquals(2,count($json));
    }

    /** @test     */
    public function when_company_has_two_user_type_then_meta_json_has_proper_keys() {
        $c = Company::factory()->create();
        $ut1 = UserType::factory()->create(['company_id'=>$c->id]);
        $ut2 = UserType::factory()->create(['company_id'=>$c->id]);
        $response = $this->get('/api/c/'.$c->id.'/data/utenze_meta.json');      
        $json = $response->json();

        $this->assertArrayHasKey($ut1->slug,$json);
        $this->assertArrayHasKey($ut2->slug,$json);
        
    }
    /** @test     */
    public function single_item_has_locale_it() {
        $c = Company::factory()->create();
        $ut1 = UserType::factory()->create(['company_id'=>$c->id]);
        $ut2 = UserType::factory()->create(['company_id'=>$c->id]);
        $response = $this->get('/api/c/'.$c->id.'/data/utenze_meta.json');      
        $json = $response->json();

        $this->assertArrayHasKey('locale',$json[$ut1->slug]);
        $this->assertArrayHasKey('locale',$json[$ut2->slug]);

        $this->assertEquals('it',$json[$ut1->slug]['locale']);
        $this->assertEquals('it',$json[$ut2->slug]['locale']);
        
    }

    /** @test     */
    public function single_item_has_proper_label() {
        $c = Company::factory()->create();
        $ut1 = UserType::factory()->create(['company_id'=>$c->id]);
        $ut2 = UserType::factory()->create(['company_id'=>$c->id]);
        $response = $this->get('/api/c/'.$c->id.'/data/utenze_meta.json');      
        $json = $response->json();

        $this->assertArrayHasKey('label',$json[$ut1->slug]);
        $this->assertArrayHasKey('label',$json[$ut2->slug]);

        $this->assertEquals($ut1->getTranslation('label','it'),$json[$ut1->slug]['label']);
        $this->assertEquals($ut2->getTranslation('label','it'),$json[$ut2->slug]['label']);
        
    }

    /** @test     */
    public function single_item_has_proper_translations() {
        $c = Company::factory()->create();
        $ut1 = UserType::factory()->create(['company_id'=>$c->id]);
        $ut2 = UserType::factory()->create(['company_id'=>$c->id]);
        $response = $this->get('/api/c/'.$c->id.'/data/utenze_meta.json');      
        $json = $response->json();

        $this->assertArrayHasKey('translations',$json[$ut1->slug]);
        $this->assertArrayHasKey('translations',$json[$ut2->slug]);

        $this->assertArrayHasKey('en',$json[$ut1->slug]['translations']);
        $this->assertArrayHasKey('en',$json[$ut2->slug]['translations']);

        // CONTENT
        $this->assertEquals($ut1->getTranslation('label','en'),$json[$ut1->slug]['translations']['en']['label']);
        $this->assertEquals($ut2->getTranslation('label','en'),$json[$ut2->slug]['translations']['en']['label']);
        
    }



}
