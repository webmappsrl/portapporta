<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserType;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateUserTest extends TestCase
{
    use RefreshDatabase;
    public function test_change_password_ok()
    {
        $lat = 10;
        $lon = 45;
        $user = User::factory()->create([
            'location' => DB::select("SELECT ST_GeomFromText('POINT(" . $lat . " " . $lon . " )') as g")[0]->g,
            'email_verified_at' => now(),
        ]);
        Sanctum::actingAs(
            $user,
            ['*']
        );
        $oldPassword = $user->password;
        $response = $this->post("api/user", [
            'password' => 'provaprovaprova',
            'password_confirmation' => 'provaprovaprova',
        ]);
        $this->assertSame(200, $response->status());
        $responseUser = $response->json()['data']['user'];
        $dbUser = User::find($responseUser['id']);
        $this->assertNotEquals($oldPassword, $dbUser->password);
    }
    public function test_change_password_with_another_fields_fail()
    {
        $lat = 10;
        $lon = 45;
        $user = User::factory()->create([
            'location' => DB::select("SELECT ST_GeomFromText('POINT(" . $lat . " " . $lon . " )') as g")[0]->g,
            'email_verified_at' => now(),
        ]);
        Sanctum::actingAs(
            $user,
            ['*']
        );
        $response = $this->post("api/user", [
            'name' => 'ayeye',
            'password' => 'provaprovaprova',
            'password_confirmation' => 'provaprovaprova',
        ]);
        $this->assertSame(400, $response->status());
    }
    public function test_change_name_ok()
    {
        $lat = 10;
        $lon = 45;
        $user = User::factory()->create([
            'location' => DB::select("SELECT ST_GeomFromText('POINT(" . $lat . " " . $lon . " )') as g")[0]->g,
            'email_verified_at' => now(),
        ]);
        Sanctum::actingAs(
            $user,
            ['*']
        );

        $response = $this->post("api/user", [
            'name' => 'ayeye',
        ]);
        $this->assertSame(200, $response->status());
        $responseUser = $response->json()['data']['user'];
        $dbUser = User::find($responseUser['id']);
        $this->assertEquals($dbUser->name, 'ayeye');
    }

    public function test_change_name_whot_another_fields_fails()
    {
        $lat = 10;
        $lon = 45;
        $user = User::factory()->create([
            'location' => DB::select("SELECT ST_GeomFromText('POINT(" . $lat . " " . $lon . " )') as g")[0]->g,
            'email_verified_at' => now(),
        ]);
        Sanctum::actingAs(
            $user,
            ['*']
        );

        $response = $this->post("api/user", [
            'name' => 'ayeye',
            'passwords' => 'brazov'
        ]);
        $this->assertSame(400, $response->status());
    }


    public function test_change_location_zone_usertype_ok()
    {
        $lat = 10;
        $lon = 45;
        $oldZone = Zone::factory()->create();
        $newZone = Zone::factory()->create();
        $oldUSerType = UserType::factory()->create();
        $newUSerType = UserType::factory()->create();
        $oldLocation = DB::select("SELECT ST_GeomFromText('POINT(" . 10 . " " . 45 . " )') as g")[0]->g;


        $user = User::factory()->create([
            'email_verified_at' => now(),
            'location' => $oldLocation,
            'zone_id' => $oldZone->id,
            'user_type_id' =>  $oldUSerType->id
        ]);
        Sanctum::actingAs(
            $user,
            ['*']
        );

        $response = $this->post("api/user", [
            'location' => [13, 25],
            'zone_id' => $newZone->id,
            'user_type_id' =>  $newUSerType->id
        ]);
        $this->assertSame(200, $response->status());
        $responseUser = $response->json()['data']['user'];
        $dbUser = User::find($responseUser['id']);
        $this->assertNotEquals($dbUser->location, $oldLocation);
        $this->assertNotEquals($dbUser->zone_id, $oldZone->id);
        $this->assertNotEquals($dbUser->user_type_id, $oldUSerType->id);
        $this->assertEquals($dbUser->zone_id, $newZone->id);
        $this->assertEquals($dbUser->user_type_id, $newUSerType->id);
    }
}
