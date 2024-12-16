<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Address;
use Laravel\Sanctum\Sanctum;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Zone;

class UpdateUserControllerTest extends TestCase
{

    use DatabaseTransactions;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    /**
     * Test that an authenticated user can access their own user data
     * and that it includes their address information
     */
    public function testGetUserAccess()
    {
        $zone = Zone::factory()->create();
        $user = User::factory()->create();
        $user->zone_id = $zone->id;
        $user->save();
        Sanctum::actingAs(
            $user,
            ['*']
        );
        $address = Address::factory()->create([
            'user_id' => $user->id,
            'zone_id' => $zone->id,
            'address' => '123 Test Street',
            'location' => 'POINT(10 45)',
        ]);
        $response = $this->get("/api/v2/user");
        $response->assertStatus(200);
        
        // Assert the JSON structure and content
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('addresses.0', fn (AssertableJson $json) =>
                $json->where('address', '123 Test Street')
                     ->where('id', $address->id)
                     ->etc()
            )
            ->etc()
        );
    }

    /**
     * Test that getting a user returns the correct user data
     */
    public function testGetUserReturnsCorrectData()
    {
        $user = User::factory()->create();
        Sanctum::actingAs(
            $user,
            ['*']
        );

        $response = $this->get("/api/v2/user");
        $response->assertStatus(200);

        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('id', $user->id)
                 ->etc()
        );
    }

    /**
     * Test that a user can successfully delete their own account
     * and the record is removed from the database
     */
    public function testDeleteUser()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);
        
        $response = $this->get("/api/v2/delete");
        $response->assertStatus(200);
        
        // Verify user was deleted from database
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function testUpdateUser()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $changes = [    
            'name' => 'Mario Mario',
            'email' => 'mario.mario@example.com',
        ];

        $response = $this->post("/api/v2/user", $changes);
        $response->assertStatus(200);

        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('success', true)
                 ->where('message', 'name,email: changed successfully.')
                 ->where('data.user.name', $changes['name'])
                 ->where('data.user.email', $changes['email'])
                 ->etc()
        );

        // Verify changes were persisted to database
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $changes['name'],
            'email' => $changes['email']
        ]);
        
    }

}
