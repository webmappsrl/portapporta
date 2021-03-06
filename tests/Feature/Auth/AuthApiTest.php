<?php

namespace Tests\Feature\Auth;

use App\Models\UserType;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function testRegisterNameFieldRequired()
    {
        $response = $this->post('/api/register', [
            'email' => 'team@webmapp.it',
            'password' => 'webmapp'
        ]);
        $this->assertSame(400, $response->status());
        $this->assertSame('{"success":false,"message":"The name field is required. (and 7 more errors)"}', $response->content());
    }

    public function testRegisterCompanyIdFieldRequired()
    {
        $response = $this->post('/api/register', [
            'email' => 'team@webmapp.it',
            'password' => 'webmapp',
            'name' => 'myName'
        ]);
        $this->assertSame(400, $response->status());
        $this->assertSame('{"success":false,"message":"The company id field is required. (and 6 more errors)"}', $response->content());
    }


    public function testRegisterPasswordMustBeAtLeast8()
    {
        $response = $this->post('/api/register', [
            'email' => 'team@webmapp.it',
            'password' => 'webmapp',
            'name' => 'myName',
            'company_id' => 10

        ]);
        $this->assertSame(400, $response->status());
        $this->assertSame('{"success":false,"message":"The password must be at least 8 characters. (and 5 more errors)"}', $response->content());
    }
    public function testRegisterPasswordConfirmationDoesNotMatchNoField()
    {
        $response = $this->post('/api/register', [
            'email' => 'team@webmapp.it',
            'password' => 'webmappwebmapp',
            'name' => 'myName',
            'company_id' => 10
        ]);
        $this->assertSame(400, $response->status());
        $this->assertSame('{"success":false,"message":"The password confirmation does not match. (and 4 more errors)"}', $response->content());
    }

    // TODO: remove email send
    public function testRegisterSuccess()
    {
        $z = Zone::factory()->create();
        $u = UserType::factory()->create();

        $response = $this->post('/api/register', [
            'email' => 'team@webmapp.it',
            'password' => 'webmappwebmapp',
            'password_confirmation' => 'webmappwebmapp',
            'name' => 'myName',
            'company_id' => 10,
            'zone_id' => $z->id,
            'user_type_id' => $u->id,
            'location' => [10, 45],
        ]);
        $this->assertSame(200, $response->status());
        $content = json_decode($response->content());
        $this->assertSame(true, $content->success);
        $this->assertNotEmpty($content->data);
    }

    public function testLoginInvalidCredentials()
    {
        $response = $this->post('/api/login', [
            'email' => 'amministrazione@webmapp.it',
            'password' => 'test'
        ]);
        $this->assertSame(400, $response->status());
        $this->assertSame('{"success":false,"message":"The provided credentials are incorrect."}', $response->content());
    }

    public function testLoginSuccess()
    {
        $response = $this->post('/api/login', [
            'email' => 'team@webmapp.it',
            'password' => 'webmappwebmapp',
        ]);
        $this->assertSame(400, $response->status());
        $this->assertSame('{"success":false,"message":"The provided credentials are incorrect."}', $response->content());
    }
}
