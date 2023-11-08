<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\TrashType;
use App\Models\UserType;
use App\Models\WasteCollectionCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class ApiDataCentriRaccoltaGeojsonTest extends TestCase
{
    // REF https://apiersu.netseven.it/data/centri_raccolta.geojson

    use DatabaseTransactions;
    use WithoutMiddleware;
    /** @test     */
    public function api_data_centri_raccolta_returns_200()
    {
        $z = WasteCollectionCenter::factory()->create();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $z->company->id . '/waste_collection_centers.geojson');

        $response->assertStatus(200);
    }

    /** @test     */
    public function api_data_centri_raccolta_is_geojson()
    {
        $z = WasteCollectionCenter::factory()->create();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $z->company->id . '/waste_collection_centers.geojson');

        $response->assertStatus(200);
        $geojson = $response->json();

        $this->assertArrayHasKey('type', $geojson);
        $this->assertArrayHasKey('features', $geojson);

        $this->assertEquals('FeatureCollection', $geojson['type']);
    }

    /** @test     */
    public function api_data_centri_raccolta_has_proper_feature_section()
    {
        $c = Company::factory()->create();
        WasteCollectionCenter::factory()->create(['company_id' => $c->id]);
        WasteCollectionCenter::factory()->create(['company_id' => $c->id]);
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $c->id . '/waste_collection_centers.geojson');

        $response->assertStatus(200);
        $geojson = $response->json();

        $this->assertEquals(2, count($geojson['features']));

        $this->assertArrayHasKey('type', $geojson['features'][0]);
        $this->assertEquals('Feature', $geojson['features'][0]['type']);
        $this->assertArrayHasKey('properties', $geojson['features'][0]);
        $this->assertArrayHasKey('geometry', $geojson['features'][0]);

        $this->assertArrayHasKey('type', $geojson['features'][1]);
        $this->assertEquals('Feature', $geojson['features'][0]['type']);
        $this->assertArrayHasKey('properties', $geojson['features'][1]);
        $this->assertArrayHasKey('geometry', $geojson['features'][1]);
    }

    /** @test     */
    public function api_data_centri_raccolta_has_proper_properties_feature_section()
    {
        $z = WasteCollectionCenter::factory()->create();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $z->company->id . '/waste_collection_centers.geojson');

        $response->assertStatus(200);
        $geojson = $response->json();

        $properties = $geojson['features'][0]['properties'];

        // Not translatable
        $fields = ['website', 'picture_url'];
        foreach ($fields as $field) {
            $this->assertArrayHasKey($field, $properties);
            // $this->assertEquals($z->$field, $properties[$field]); TODO fix
        }
        $this->assertArrayHasKey('marker-color', $properties);
        // $this->assertEquals($z->marker_color, $properties['marker-color']); TODO fix
        $this->assertArrayHasKey('marker-size', $properties);
        $this->assertEquals($z->marker_size, $properties['marker-size']);

        // Translatable
        $fields = ['name', 'orario', 'description'];
        foreach ($fields as $field) {
            // IT
            $this->assertArrayHasKey($field, $properties);
            $this->assertEquals($z->getTranslation($field, 'it'), $properties[$field]);
            // EN    
            $this->assertArrayHasKey($field, $properties['translations']['en']);
            $this->assertEquals($z->getTranslation($field, 'en'), $properties['translations']['en'][$field]);
        }
    }

    /** @test     */
    public function api_data_centri_raccolta_has_proper_geometry_feature_section()
    {
        $z = WasteCollectionCenter::factory()->create();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $z->company->id . '/waste_collection_centers.geojson');

        $response->assertStatus(200);
        $geojson = $response->json();

        $geometry = $geojson['features'][0]['geometry'];

        $this->assertArrayHasKey('type', $geometry);
        $this->assertEquals('Point', $geometry['type']);

        $this->assertArrayHasKey('coordinates', $geometry);
        $this->assertEquals(2, count($geometry['coordinates']));
    }

    /** @test     */
    public function api_data_centri_raccolta_has_proper_user_types_section()
    {
        $c = Company::factory()->create();
        $wcc = WasteCollectionCenter::factory()->create(['company_id' => $c->id]);
        $ut1 = UserType::factory()->create(['company_id' => $c->id]);
        $ut2 = UserType::factory()->create(['company_id' => $c->id]);
        $wcc->userTypes()->attach([$ut1->id, $ut2->id]);
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $c->id . '/waste_collection_centers.geojson');

        $response->assertStatus(200);
        $geojson = $response->json();
        $properties = $geojson['features'][0]['properties'];

        $this->assertArrayHasKey('user_types', $properties);

        foreach ($wcc->userTypes->pluck('id')->toArray() as $ut) {
            $this->assertContains($ut, $properties['user_types']);
        }
    }

    /** @test     */
    public function api_data_centri_raccolta_has_proper_trash_types_section()
    {
        $c = Company::factory()->create();
        $wcc = WasteCollectionCenter::factory()->create(['company_id' => $c->id]);
        $tt1 = TrashType::factory()->create(['company_id' => $c->id]);
        $tt2 = TrashType::factory()->create(['company_id' => $c->id]);
        $wcc->trashTypes()->attach([$tt1->id, $tt2->id]);
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get('/api/c/' . $c->id . '/waste_collection_centers.geojson');

        $response->assertStatus(200);
        $geojson = $response->json();
        $properties = $geojson['features'][0]['properties'];

        $this->assertArrayHasKey('trash_types', $properties);

        foreach ($wcc->trashTypes->pluck('id')->toArray() as $ut) {
            $this->assertContains($ut, $properties['trash_types']);
        }
    }
}
