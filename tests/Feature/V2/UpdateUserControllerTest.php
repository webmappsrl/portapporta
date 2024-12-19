<?php
namespace Tests\Feature\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;   
use Tests\Feature\V2\FeatureTestV2UtilsFunctions;   

class UpdateUserControllerTest extends TestCase
{
    use DatabaseTransactions, FeatureTestV2UtilsFunctions;
    private $user;
    private $zone;
    private $address;
    private $fieldsToCheckForAddress;
    const API_PREFIX = '/api/v2';

    public function setUp(): void
    {
        parent::setUp();
        $this->zone = $this->createZone();
        $this->user = $this->createUser($this->zone);
        $this->address = $this->createAddress($this->user, $this->zone);

        $this->fieldsToCheckForAddress = $this->createFieldsToCheckForAddress($this->address);
    }

    /** @test */
    public function testGetUserAccess()
    {
        Sanctum::actingAs($this->user);

        $response = $this->get(self::API_PREFIX . "/user");
        $response->assertStatus(200);
        
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('addresses.0', fn (AssertableJson $json) =>
                $this->assertAddressData($json, $this->fieldsToCheckForAddress)
            )
            ->etc()
        );
    }

    /** @test */
    public function testGetUserReturnsCorrectData()
    {
        Sanctum::actingAs($this->user);

        $response = $this->get(self::API_PREFIX . "/user");
        $response->assertStatus(200);

        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('id', $this->user->id)
                 ->etc()
        );
    }

    /** @test */
    public function testDeleteUser()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->get(self::API_PREFIX . "/delete");
        $response->assertStatus(200);
        
        // Verify user was deleted from database
        $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
    }

    /** @test */
    public function testUpdateUser()
    {
        Sanctum::actingAs($this->user);

        $changes = [    
            'name' => 'Mario Mario',
            'email' => 'mario.mario@example.com',
        ];

        $response = $this->post(self::API_PREFIX . "/user", $changes);
        $response->assertStatus(200);

        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('data.user.name', $changes['name'])
                 ->where('data.user.email', $changes['email'])
                 ->etc()
        );

        // Verify changes were persisted to database
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => $changes['name'],
            'email' => $changes['email']
        ]);
        
    }

}
