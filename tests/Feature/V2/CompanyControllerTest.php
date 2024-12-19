<?php

namespace Tests\Feature\V2;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Company;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\V2\FeatureTestV2UtilsFunctions;
class CompanyControllerTest extends TestCase
{
    use DatabaseTransactions, FeatureTestV2UtilsFunctions;
    private $company;
    private $anotherCompany;
    private const API_PREFIX = '/api/v2/c/';
    const responseMessages = [
        'formJsonRetrieved' => 'Json of the registration form fields.',
        'formJsonNotFound' => 'Company not found.',
        'formJsonEmpty' => 'Json of the registration form fields.',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create(
            [
                'form_json' => json_encode(['name' => 'John', 'age' => 30])
            ]
        );
        $this->anotherCompany = Company::factory()->create();
    }

    /** @test */
    public function testFormJson()  
    {
        $response = $this->get(self::API_PREFIX . $this->company->id . '/form_json');
        $this->assertSuccessResponse(
            $response,
            self::responseMessages['formJsonRetrieved'],
            200
        );
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('data', fn (AssertableJson $json) =>
                $json->where('name', 'John')
                     ->where('age', 30)
            )
            ->etc()
        );
    }

    /** @test */
    public function testFormJsonWithInvalidId()
    {
        $this->assertErrorResponse(
            $this->get(self::API_PREFIX . '0/form_json'),
            self::responseMessages['formJsonNotFound'],
            404
        );
    }

    /** @test */
    public function testFormJsonWithEmptyFormJson()
    {
        $this->assertSuccessResponse(
            $this->get(self::API_PREFIX . $this->anotherCompany->id . '/form_json'),
            self::responseMessages['formJsonEmpty'],
            200
        );
    }
}
