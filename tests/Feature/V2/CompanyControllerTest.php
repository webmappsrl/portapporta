<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Company;
use Illuminate\Testing\Fluent\AssertableJson;

class CompanyControllerTest extends TestCase
{
    use DatabaseTransactions;
    private $company;
    private $anotherCompany;
    private const API_PREFIX = '/api/v2/c/';

    public function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create(
            [
                'form_json' => '{"name": "John", "age": 30}'
            ]
        );
        $this->anotherCompany = Company::factory()->create();
    }

    public function testFormJson()  
    {
        $this->get(self::API_PREFIX . $this->company->id . '/form_json')
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('success', true)
                ->where('data', json_decode($this->company->form_json, true))
                ->where('message', 'Json of the registration form fields.')
        );
    }

    public function testFormJsonWithInvalidId()
    {
        $response = $this->get(self::API_PREFIX . '0/form_json');
        $response->assertStatus(500);
    }

    public function testFormJsonWithEmptyFormJson()
    {
        $response = $this->get(self::API_PREFIX . $this->anotherCompany->id . '/form_json');
        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('success', true)
                ->where('data', null)
                ->where('message', 'Json of the registration form fields.')
        );
    }
}
