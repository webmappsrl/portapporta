<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Zone;
use Illuminate\Testing\Fluent\AssertableJson;
use App\Models\Address;

class LoginControllerTest extends TestCase
{
    use DatabaseTransactions;
    private $user;
    private $company;
    private $anotherCompany;
    private $address;
    private $zone;
    const API_PREFIX = '/api/v2/';

    public function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->anotherCompany = Company::factory()->create();
        $this->user = User::factory()->create([
            'app_company_id' => $this->company->id,
            'password' => bcrypt('password')
        ]);
        $this->zone = Zone::factory()->create();
        $this->address = Address::factory()->create([
            'user_id' => $this->user->id,
            'address' => 'Via Roma 1',
            'zone_id' => $this->zone->id,
            'location' => 'POINT(10 45)',
        ]);
    }

    public function testLoginIsSuccessful()
    {
        $this->post(self::API_PREFIX . 'login', [
            'email' => $this->user->email,
            'password' => 'password', 
            'app_company_id' => $this->company->id
        ])
        ->assertStatus(200)
        ->assertJson(function (AssertableJson $json) {
            $json->where('success', true)
                ->has('data', function (AssertableJson $json) {
                    $json->has('token')
                        ->where('name', $this->user->name)
                        ->has('email_verified_at')
                        ->has('user', function ($json) {
                            $this->assertUserData($json)
                            ->etc();
                    });
                })
                ->where('message', 'User login successfully.');
        });
    }

    public function testLoginWithWrongEmail()
    {
        $this->post(self::API_PREFIX . 'login', [
            'email' => 'wrong@test.com',
            'password' => 'password', 
            'app_company_id' => $this->company->id
        ])
        ->assertStatus(400)
        ->assertJson(function (AssertableJson $json) {
            $json->where('success', false)
                ->where('message', 'Le credenziali inserite non sono corrette.');
        });
    }

    public function testLoginWithWrongAppCompanyId()
    {
        $this->post(self::API_PREFIX . 'login', [
            'email' => $this->user->email,
            'password' => 'password', 
            'app_company_id' => $this->anotherCompany->id
        ])
        ->assertStatus(400)
        ->assertJson(function (AssertableJson $json) {
            $json->where('success', false)
                ->where('message', 'Non puoi accedere a questa app. Sei registrato all\'app: ' . $this->company->name . '.');
        });
    }

    public function testLoginWithWrongAppCompanyIdButSuperAdmin()
    {
        $this->user->assignRole('super_admin');
        $this->post(self::API_PREFIX . 'login', [
            'email' => $this->user->email,
            'password' => 'password', 
            'app_company_id' => $this->anotherCompany->id
        ])
        ->assertStatus(200)
        ->assertJson(function (AssertableJson $json) {
            $json->where('success', true)
                ->has('data', function (AssertableJson $json) {
                    $json->has('token')
                        ->where('name', $this->user->name)
                        ->has('email_verified_at')
                        ->has('user', function ($json) {
                            $this->assertUserData($json)
                            ->etc();
                        });
                })
                ->where('message', 'User login successfully.');
        });
    }


    // Funzioni di utility per i test


    private function assertUserData(AssertableJson $json) : AssertableJson
    {
        return $json
            ->where('id', $this->user->id)
            ->where('name', $this->user->name)
            ->where('email', $this->user->email)
            ->where('app_company_id', $this->user->app_company_id)
            ->where('zone_id', $this->user->zone_id)
            ->has('addresses.0', function ($json) {
                $this->assertAddressData($json);
            });
    }

    private function assertAddressData(AssertableJson $json) : AssertableJson
    {
        return $json
            ->where('address', $this->address->address)
            ->has('location', function (AssertableJson $json) {
                $json->where('0', 10)
                    ->where('1', 45);
            })
            ->etc();
    }
}
