<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Laravel\Nova\Console\RenamesStubs;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class ApiDataUtenzeMetaJsonTest extends TestCase
{

    // REF: https://apiersu.netseven.it/data/utenze_meta.json

    use RefreshDatabase;
    use WithoutMiddleware;

    /** @test     */
    public function when_visit_utenze_meta_json_return_200()
    {
        $ut = UserType::factory()->create();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $ut->company->id . '/user_types.json');

        $response->assertStatus(200);
    }

    /** @test     */
    public function when_company_has_two_user_type_then_meta_json_has_two_elements()
    {
        $c = Company::factory()->create();
        $ut1 = UserType::factory()->create(['company_id' => $c->id]);
        $ut2 = UserType::factory()->create(['company_id' => $c->id]);
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $c->id . '/user_types.json');
        $json = $response->json();
        $this->assertEquals(2, count($json));
    }

    /** @test     */
    public function when_company_has_two_user_type_then_meta_json_has_proper_keys()
    {
        $c = Company::factory()->create();
        $ut1 = UserType::factory()->create(['company_id' => $c->id]);
        $ut2 = UserType::factory()->create(['company_id' => $c->id]);
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $c->id . '/user_types.json');
        $json = $response->json();

        $slugs = [];
        foreach ($json as $item) {
            $slugs[] = $item['slug'];
        }
        $this->assertTrue(in_array($ut1->slug, $slugs));
        $this->assertTrue(in_array($ut2->slug, $slugs));
    }
    /** @test     */
    public function single_item_has_locale_it()
    {
        $c = Company::factory()->create();
        $ut1 = UserType::factory()->create(['company_id' => $c->id]);
        $ut2 = UserType::factory()->create(['company_id' => $c->id]);
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $c->id . '/user_types.json');
        $json = $response->json();

        foreach ($json as $item) {
            $this->assertEquals('it', $item['locale']);
            $this->assertEquals('it', $item['locale']);
        }
    }

    /** @test     */
    public function single_item_has_proper_label()
    {
        $c = Company::factory()->create();
        $ut1 = UserType::factory()->create(['company_id' => $c->id]);
        $ut2 = UserType::factory()->create(['company_id' => $c->id]);
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $c->id . '/user_types.json');
        $json = $response->json();

        $labels = [];
        foreach ($json as $item) {
            $labels[] = $item['label'];
        }
        $this->assertTrue(in_array($ut1->getTranslation('label', 'it'), $labels));
        $this->assertTrue(in_array($ut2->getTranslation('label', 'it'), $labels));
    }

    /** @test     */
    public function single_item_has_proper_translations()
    {
        $c = Company::factory()->create();
        $ut1 = UserType::factory()->create(['company_id' => $c->id]);
        $ut2 = UserType::factory()->create(['company_id' => $c->id]);
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $c->id . '/user_types.json');
        $json = $response->json();
        $labels = [];
        foreach ($json as $item) {
            $labels[] = $item['translations']['en']['label'];
        }
        $this->assertTrue(in_array($ut1->getTranslation('label', 'en'), $labels));
        $this->assertTrue(in_array($ut2->getTranslation('label', 'en'), $labels));
    }
}
