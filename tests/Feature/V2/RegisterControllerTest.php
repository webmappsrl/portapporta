<?php

namespace Tests\Feature\V2;

use App\Models\Address;
use App\Models\Company;
use App\Models\User;
use App\Models\UserType;
use App\Models\Zone;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use DatabaseTransactions;

    private static string $REGISTER_ENDPOINT = '/api/v2/register';

    private function assertErrorResponse($response, $expectedMessage)
    {
        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                 ])
                 ->assertJsonPath('message', fn ($message) =>
                     str_contains($message, $expectedMessage)
                 );
    }

    public function testRegisterNameFieldRequired()
    {
        $response = $this->post(self::$REGISTER_ENDPOINT, [
            'email' => 'team@webmapp.it',
            'password' => 'webmapp'
        ]);
        $this->assertErrorResponse($response, 'The name field is required');
    }

    public function testRegisterAppCompanyIdFieldRequired()
    {
        $response = $this->post(self::$REGISTER_ENDPOINT, [
            'email' => 'team@webmapp.it',
            'password' => 'webmapp',
            'name' => 'myName'
        ]);
        $this->assertErrorResponse($response, 'The app company id field is required');
    }

    public function testRegisterPasswordMustBeAtLeast8()
    {
        $response = $this->post(self::$REGISTER_ENDPOINT, [
            'email' => 'team@webmapp.it',
            'password' => 'webmapp',
            'name' => 'myName',
            'app_company_id' => 10
        ]);
        $this->assertErrorResponse($response, 'The password must be at least 8 characters');
    }

    public function testRegisterPasswordConfirmationDoesNotMatchNoField()
    {
        $response = $this->post(self::$REGISTER_ENDPOINT, [
            'email' => 'team@webmapp.it',
            'password' => 'webmappwebmapp',
            'password_confirmation' => 'ppambewppambew',
            'name' => 'myName',
            'app_company_id' => 10
        ]);
        $this->assertErrorResponse($response, 'The password confirmation does not match');
    }

    public function testRegisterDuplicateEmail()
    {
        $existingUser = User::factory()->create(['email' => 'team@webmapp.it']);

        $response = $this->post(self::$REGISTER_ENDPOINT, [
            'email' => 'team@webmapp.it',
            'password' => 'webmappwebmapp',
            'password_confirmation' => 'webmappwebmapp',
            'name' => 'myName',
            'app_company_id' => 10
        ]);

        $this->assertErrorResponse($response, 'The email has already been taken');
    }

    public function testRegisterSuccess()
    {
        $z = Zone::factory()->create();
        $u = UserType::factory()->create();
        $c = Company::factory()->create();

        $userData = [
            'form_data' => ['phone_number' => '3333333333', 'fiscal_code' => 'PPPPPP1P11P111P'],
            'app_company_id' => $c->id,
        ];
        $addressData = [
            'zone_id' => $z->id,
            'user_type_id' => $u->id,
            'address' => 'via da qua',
            'location' => [10, 45],
        ];

        $response = $this->post(self::$REGISTER_ENDPOINT, [
            'email' => 'team@webmapp.it',
            'password' => 'webmappwebmapp',
            'password_confirmation' => 'webmappwebmapp',
            'name' => 'myName',
            ...$userData,
            ...$addressData
        ]);

        $response->assertOk()
                 ->assertJson(fn (AssertableJson $json) =>
                     $json->where('success', true)
                          ->has('message')
                          ->has('data', fn (AssertableJson $json) =>
                              $json->has('name')
                                   ->has('token')
                          )

                 );

        $user = User::where('email', 'team@webmapp.it')->first();
        $this->assertNotNull($user);
        foreach ($userData as $key => $value) {
            $this->assertEquals($value, $user->{$key});
        }

        $address = Address::where([
            'user_id' => $user->id,
            'zone_id' => $z->id
        ])->first();
        foreach ($addressData as $key => $value) {
            if ($key === 'location') {
                $expectedLocation = DB::select("SELECT ST_GeomFromText('POINT(" . $value[0] . " " . $value[1] . ")', 4326) as g")[0]->g;
                $this->assertEquals($expectedLocation, $address->{$key});
            } else {
                $this->assertEquals($value, $address->{$key});
            }
        }
    }
}
