<?php

namespace Tests\Feature\V2;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Tests\Feature\V2\FeatureTestV2UtilsFunctions;

class RegisterControllerTest extends TestCase
{
    use DatabaseTransactions, FeatureTestV2UtilsFunctions;

    private $zone;
    private $userType;
    private $company;
    private $userData;
    private $addressData;
    private $dataNeededForRegistration;

    private const responseMessages = [
        'registerNameFieldRequired' => 'The name field is required',
        'registerAppCompanyIdFieldRequired' => 'The app company id field is required',
        'registerPasswordMustBeAtLeast8' => 'The password must be at least 8 characters',
        'registerPasswordConfirmationDoesNotMatch' => 'The password confirmation does not match',
        'registerDuplicateEmail' => 'The email has already been taken',
        'registerSuccess' => 'User register successfully.'
    ];

    private const REGISTER_ENDPOINT = '/api/v2/register';

    protected function setUp(): void
    {
        parent::setUp();
        $this->zone = $this->createZone();
        $this->userType = $this->createUserType();
        $this->company = $this->createCompany();

        $this->userData = [
            'form_data' => ['phone_number' => '3333333333', 'fiscal_code' => 'PPPPPP1P11P111P'],
            'app_company_id' => $this->company->id,
        ];
        $this->addressData = [
            'zone_id' => $this->zone->id,
            'user_type_id' => $this->userType->id,
            'address' => 'via da qua',
            'location' => [10, 45],
        ];

        $this->dataNeededForRegistration = [
            'email' => 'team@webmapp.it',
            'password' => 'webmappwebmapp',
            'password_confirmation' => 'webmappwebmapp',
            'name' => 'myName',
            ...$this->userData,
            ...$this->addressData
        ];
    }

    /** @test */
    public function testRegisterNameFieldRequired()
    {
        $data = $this->dataNeededForRegistration;
        unset($data['name']);
        $this->assertErrorResponse(
            $this->post(self::REGISTER_ENDPOINT, $data), 
            self::responseMessages['registerNameFieldRequired']
        );
    }

    /** @test */
    public function testRegisterAppCompanyIdFieldRequired()
    {
        $data = $this->dataNeededForRegistration;
        unset($data['app_company_id']);
        $this->assertErrorResponse(
            $this->post(self::REGISTER_ENDPOINT, $data), 
            self::responseMessages['registerAppCompanyIdFieldRequired']
        );
    }

    /** @test */
    public function testRegisterPasswordMustBeAtLeast8()
    {
        $data = $this->dataNeededForRegistration;
        $data['password'] = 'webmapp';
        $data['password_confirmation'] = 'webmapp';
        $this->assertErrorResponse(
            $this->post(self::REGISTER_ENDPOINT, $data), 
            self::responseMessages['registerPasswordMustBeAtLeast8']
        );
    }

    /** @test */
    public function testRegisterPasswordConfirmationDoesNotMatchNoField()
    {
        $data = $this->dataNeededForRegistration;
        $data['password_confirmation'] = 'ppambewppambew';
        $this->assertErrorResponse(
            $this->post(self::REGISTER_ENDPOINT, $data), 
            self::responseMessages['registerPasswordConfirmationDoesNotMatch']
        );
    }

    /** @test */
    public function testRegisterDuplicateEmail()
    {
        $this->createUser($this->zone, $this->company, ['email' => 'team@webmapp.it']);
        $this->assertErrorResponse(
            $this->post(self::REGISTER_ENDPOINT, $this->dataNeededForRegistration), 
            self::responseMessages['registerDuplicateEmail']
        );
    }

    /** @test */
    public function testRegisterSuccess()
    {
        $response = $this->post(self::REGISTER_ENDPOINT, $this->dataNeededForRegistration);

        $this->assertSuccessResponse($response, self::responseMessages['registerSuccess']);
        
        $response->assertJson(fn (AssertableJson $json) =>
                $json->has('data', fn (AssertableJson $json) =>
                    $json->has('name')
                        ->has('token')
                )->etc()
        );

        $user = User::where('email', 'team@webmapp.it')->first();
        $this->assertNotNull($user);
        foreach ($this->userData as $key => $value) {
            $this->assertEquals($value, $user->{$key});
        }

        $address = Address::where([
            'user_id' => $user->id,
            'zone_id' => $this->zone->id
        ])->first();
        foreach ($this->addressData as $key => $value) {
            if ($key === 'location') {
                $expectedLocation = DB::select("SELECT ST_GeomFromText('POINT(" . $value[0] . " " . $value[1] . ")', 4326) as g")[0]->g;
                $this->assertEquals($expectedLocation, $address->{$key});
            } else {
                $this->assertEquals($value, $address->{$key});
            }
        }
    }
}
