<?php
namespace Tests\Feature\V2;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\V2\FeatureTestV2UtilsFunctions;
use Spatie\Permission\Models\Role;
class LoginControllerTest extends TestCase
{
    use DatabaseTransactions, FeatureTestV2UtilsFunctions;
    private $user;
    private $company;
    private $anotherCompany;
    private $address;
    private $zone;
    private $fieldsToCheckForAddress;
    private $fieldsToCheckForUser;
    const API_PREFIX = '/api/v2/';
    const responseMessages = [
        'loginSuccessful' => 'User login successfully.',
        'loginWithWrongEmail' => 'Le credenziali inserite non sono corrette.',
        'loginWithWrongAppCompanyIdButSuperAdmin' => 'User login successfully.',
    ];

    public function setUp(): void
    {
        parent::setUp();
        if(!Role::where('name', 'super_admin')->exists()) {
            Role::create(['name' => 'super_admin']);
        }
        $this->company = $this->createCompany();
        $this->anotherCompany = $this->createCompany();
        $this->zone = $this->createZone();
        $this->user = $this->createUser($this->zone, $this->company);
        $this->address = $this->createAddress($this->user, $this->zone);

        $this->fieldsToCheckForAddress = [
            'id' => $this->address->id,
            'user_id' => $this->user->id,
            'address' => $this->address->address,
            'city' => $this->address->city,
            'location' => $this->address->location,
            'house_number' => $this->address->house_number,
            'zone_id' => $this->zone->id,
            'user_type_id' => $this->address->user_type_id,
        ];
        $this->fieldsToCheckForUser = [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'app_company_id' => $this->user->app_company_id,
            'zone_id' => $this->user->zone_id,
            'addresses' => $this->fieldsToCheckForAddress,
        ];
    }

    /** @test */
    public function testLoginIsSuccessful()
    {
        $response = $this->post(self::API_PREFIX . 'login', [
            'email' => $this->user->email,
            'password' => 'password', 
            'app_company_id' => $this->company->id
        ]);
        $this->assertSuccessResponse($response, self::responseMessages['loginSuccessful']);
        $this->assertThatRequestHasTheCorrectUserAndUserFields($response);
    }

    /** @test */
    public function testLoginWithWrongEmail()
    {
        $this->assertErrorResponse(
            $this->post(self::API_PREFIX . 'login', [
                'email' => 'wrong@test.com',
                'password' => 'password', 
                'app_company_id' => $this->company->id
            ]),
            self::responseMessages['loginWithWrongEmail']
        );
    }   

    /** @test */
    public function testLoginWithWrongAppCompanyId()
    {
        $this->assertErrorResponse(
            $this->post(self::API_PREFIX . 'login', [
                'email' => $this->user->email,
                'password' => 'password', 
                'app_company_id' => $this->anotherCompany->id
            ]),
            'Non puoi accedere a questa app. Sei registrato all\'app: ' . $this->company->name . '.',
        );
    }

    /** @test */
    public function testLoginWithWrongAppCompanyIdButSuperAdmin()
    {
        $this->user->assignRole('super_admin');
        $response = $this->post(self::API_PREFIX . 'login', [
            'email' => $this->user->email,
            'password' => 'password', 
            'app_company_id' => $this->anotherCompany->id
        ]);
        $this->assertSuccessResponse($response, self::responseMessages['loginWithWrongAppCompanyIdButSuperAdmin']);
        $this->assertThatRequestHasTheCorrectUserAndUserFields($response);
    }

    private function assertThatRequestHasTheCorrectUserAndUserFields($response): void
    {
        $response->assertJson(function (AssertableJson $json) {
            $json->has('data', function (AssertableJson $json) {
                $json->where('name', $this->user->name)
                    ->has('user', function ($json) {
                        $this->assertUserData($json, $this->fieldsToCheckForUser)
                        ->etc();
                    })
                ->etc();
            })
            ->etc();
        }); 
    }

    private function assertUserData(AssertableJson $json, array $fieldsToCheckForUser): AssertableJson
    {
        foreach ($fieldsToCheckForUser as $key => $value) {
            if ($key == 'addresses') {
                $json->has('addresses.0', function ($json) use ($value) {
                    $this->assertAddressData($json, $value);
                });
            } else {
                $json->where($key, $value);
            }
        }
        return $json->etc();
    }
}

