<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CompanyConfigApiTest extends TestCase
{
    use RefreshDatabase;
    /**
     *@test
     *
     * @return void
     */
    public function it_returns_status_200()
    {
        // SETUP DB
        $c = Company::factory()->create();
        $user = User::factory()->create(
            ['email' => 'test@webmapp.it', 'app_company_id' => $c->id]
        );
        //access the api as the admin user
        $response = $this->actingAs($user)->get('/api/c/1/config.json');

        //check if the response is ok
        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_returns_the_company_config()
    {

        // SETUP DB
        $company = Company::factory()->create();
        $user = User::factory()->create(
            ['email' => 'test@webmapp.it', 'app_company_id' => $company->id]
        );

        //access the api as the admin user
        $response = $this->actingAs($user)->getJson('/api/c/' . $company->id . '/config.json');

        //decode the response
        $response = json_decode($response->getContent(), true);

        //check if the response contains the company config

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('resources', $response);
        if (!empty($company->icon)) {
            $this->assertArrayHasKey('icon', $response['resources']);
        }
        if (!empty($company->splash)) {
            $this->assertArrayHasKey('splash', $response['resources']);
        }
        if (!empty($company->font)) {
            $this->assertArrayHasKey('font', $response['resources']);
        }
        if (!empty($company->header_image)) {
            $this->assertArrayHasKey('header_image', $response['resources']);
        }
        if (!empty($company->footer_image)) {
            $this->assertArrayHasKey('footer_image', $response['resources']);
        }
        if (!empty($company->css_variables)) {
            $this->assertArrayHasKey('css_variables', $response['resources']);
        }
        if (!empty($company->app_icon)) {
            $this->assertArrayHasKey('app_icon', $response['resources']);
        }
        if (!empty($company->min_zoom)) {
            $this->assertArrayHasKey('min_zoom', $response['resources']);
        }
        if (!empty($company->max_zoom)) {
            $this->assertArrayHasKey('max_zoom', $response['resources']);
        }
        if (!empty($company->default_zoom)) {
            $this->assertArrayHasKey('default_zoom', $response['resources']);
        }
        if (!empty($company->push_notification_plist_url)) {
            $this->assertArrayHasKey('push_notification_plist_url', $response['resources']);
        }
        if (!empty($company->push_notification_json_url)) {
            $this->assertArrayHasKey('push_notification_json_url', $response['resources']);
        }
        if (!empty($company->locations)) {
            $this->assertArrayHasKey('location', $response['resources']);
        }
        if (!empty($company->company_page)) {
            $this->assertArrayHasKey('company_page', $response['resources']);
        }
    }
}
