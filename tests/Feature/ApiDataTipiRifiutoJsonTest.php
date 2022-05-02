<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\TrashType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiDataTipiRifiutoJsonTest extends TestCase
{
        // https://apiersu.netseven.it/data/tipi_rifiuto.json

        use RefreshDatabase;

        /** @test     */
        public function tipi_rifiuto_returns_200()
        {
            $tt = TrashType::factory()->create();
            $response = $this->get('/api/c/'.$tt->company->id.'/data/tipi_rifiuto.json');
    
            $response->assertStatus(200);
        }

        /** @test     */
        public function tipi_rifiuto_has_proper_keys()
        {
            $company = Company::factory()->create();
            $tt1 = TrashType::factory()->create(['company_id'=>$company->id]);
            $tt2 = TrashType::factory()->create(['company_id'=>$company->id]);
            $response = $this->get('/api/c/'.$company->id.'/data/tipi_rifiuto.json');
    
            $response->assertStatus(200);
            $json = $response->json();

            $this->assertArrayHasKey($tt1->slug,$json);
            $this->assertArrayHasKey($tt2->slug,$json);
        }

        /** @test     */
        public function tipi_rifiuto_item_has_proper_keys()
        {
            $company = Company::factory()->create();
            $tt1 = TrashType::factory()->create(['company_id'=>$company->id]);
            $tt2 = TrashType::factory()->create(['company_id'=>$company->id]);
            $response = $this->get('/api/c/'.$company->id.'/data/tipi_rifiuto.json');
    
            $response->assertStatus(200);
            $json = $response->json();

            $json1 = $json[$tt1->slug];

            $this->assertArrayHasKey('name',$json1);
            $this->assertArrayHasKey('description',$json1);
            $this->assertArrayHasKey('where',$json1);
            $this->assertArrayHasKey('color',$json1);
            $this->assertArrayHasKey('howto',$json1);
            $this->assertArrayHasKey('allowed',$json1);
            $this->assertArrayHasKey('notallowed',$json1);
            $this->assertArrayHasKey('translations',$json1);

        }
        /** @test     */
        public function tipi_rifiuto_item_has_proper_translation_keys()
        {
            $company = Company::factory()->create();
            $tt1 = TrashType::factory()->create(['company_id'=>$company->id]);
            $tt2 = TrashType::factory()->create(['company_id'=>$company->id]);
            $response = $this->get('/api/c/'.$company->id.'/data/tipi_rifiuto.json');
    
            $response->assertStatus(200);
            $json = $response->json();

            $json1 = $json[$tt1->slug]['translations']['en'];

            $this->assertArrayHasKey('name',$json1);
            $this->assertArrayHasKey('description',$json1);
            $this->assertArrayHasKey('where',$json1);
            $this->assertArrayHasKey('howto',$json1);
            $this->assertArrayHasKey('allowed',$json1);
            $this->assertArrayHasKey('notallowed',$json1);

        }
        /** @test     */
        public function tipi_rifiuto_item_has_proper_allowed_field()
        {
            $company = Company::factory()->create();
            $tt1 = TrashType::factory()->create(['company_id'=>$company->id]);
            $tt2 = TrashType::factory()->create(['company_id'=>$company->id]);
            $response = $this->get('/api/c/'.$company->id.'/data/tipi_rifiuto.json');
    
            $response->assertStatus(200);
            $json = $response->json();

            $json1 = $json[$tt1->slug];

            $this->assertIsArray($json1['allowed']);
            $this->assertEquals(4,count($json1['allowed']));
            $this->assertIsArray($json1['translations']['en']['allowed']);
            $this->assertEquals(4,count($json1['translations']['en']['allowed']));
        }
    
        /** @test     */
        public function tipi_rifiuto_item_has_proper_notallowed_field()
        {
            $company = Company::factory()->create();
            $tt1 = TrashType::factory()->create(['company_id'=>$company->id]);
            $tt2 = TrashType::factory()->create(['company_id'=>$company->id]);
            $response = $this->get('/api/c/'.$company->id.'/data/tipi_rifiuto.json');
    
            $response->assertStatus(200);
            $json = $response->json();

            $json1 = $json[$tt1->slug];

            $this->assertIsArray($json1['notallowed']);
            $this->assertEquals(4,count($json1['notallowed']));
            $this->assertIsArray($json1['translations']['en']['notallowed']);
            $this->assertEquals(4,count($json1['translations']['en']['notallowed']));
        }
    
        /** @test     */
        public function tipi_rifiuto_item_has_proper_content()
        {
            $company = Company::factory()->create();
            $tt1 = TrashType::factory()->create(['company_id'=>$company->id]);
            $tt2 = TrashType::factory()->create(['company_id'=>$company->id]);
            $response = $this->get('/api/c/'.$company->id.'/data/tipi_rifiuto.json');
    
            $response->assertStatus(200);
            $json = $response->json();

            $json1 = $json[$tt1->slug];

            // Simple not translatable
            $this->assertEquals($tt1->color,$json1['color']);

            // Simple translatable
            $this->assertEquals($tt1->getTranslation('name','it'),$json1['name']);
            $this->assertEquals($tt1->getTranslation('description','it'),$json1['description']);
            $this->assertEquals($tt1->getTranslation('where','it'),$json1['where']);
            $this->assertEquals($tt1->getTranslation('howto','it'),$json1['howto']);

            $this->assertEquals($tt1->getTranslation('name','en'),$json1['translations']['en']['name']);
            $this->assertEquals($tt1->getTranslation('description','en'),$json1['translations']['en']['description']);
            $this->assertEquals($tt1->getTranslation('where','en'),$json1['translations']['en']['where']);
            $this->assertEquals($tt1->getTranslation('howto','en'),$json1['translations']['en']['howto']);

            // Array translatable
            foreach($tt1->getTranslation('allowed','it') as $item) {
                $this->assertContains($item,$json1['allowed']);
            }
            foreach($tt1->getTranslation('allowed','en') as $item) {
                $this->assertContains($item,$json1['translations']['en']['allowed']);
            }
            foreach($tt1->getTranslation('notallowed','it') as $item) {
                $this->assertContains($item,$json1['notallowed']);
            }
            foreach($tt1->getTranslation('notallowed','en') as $item) {
                $this->assertContains($item,$json1['translations']['en']['notallowed']);
            }

        }
    
}