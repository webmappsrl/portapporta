<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use App\Models\Zone;
use App\Models\UserType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
class AddressControllerTest extends TestCase
{
    use DatabaseTransactions;

    private $user;
    private $fakeUser;
    private $zone;
    private $userType;
    private $address;
    private $fakeAddress;
    private $newAddress;
    const prefixForTheAPI = '/api/v2/address';
    


    protected function setUp(): void
    {
        parent::setUp();
        
        // Create basic test data   
        $this->userType = UserType::factory()->create();
        $this->zone = Zone::factory()->create();
        $this->user = User::factory()->create([
            'zone_id' => $this->zone->id
            ]
        );
        $this->address = Address::factory()->create([
            'user_id' => $this->user->id,
            'zone_id' => $this->zone->id,
            'address' => '123 Test Street',
            'location' => 'POINT(10 45)',
        ]);

        $this->fakeUser = User::factory()->create();
        $this->fakeAddress = Address::factory()->create([
            'user_id' => $this->fakeUser->id,
            'zone_id' => $this->zone->id,
            'address' => '123 Test Street',
            'location' => 'POINT(10 45)',
        ]);

        $this->newAddress = [
            'user_id' => $this->user->id,
            'zone_id' => $this->zone->id,
            'user_type_id' => $this->userType->id,
            'address' => '221b Baker Street',
            'location' => [15, 50],
            'city' => 'London',
            'house_number' => '221b'
        ];

        // Create relationship between zone and userType
        $this->zone->userTypes()->attach($this->userType->id);


        
    }

    /** @test */
    public function testIndex()
    {
        Sanctum::actingAs($this->user);

        $this->get(self::prefixForTheAPI . '/index')
            ->assertStatus(200)
            ->assertJson(fn ($json) =>
                $json->has('message')
                ->where('message', 'calendar types')
                ->has('success')
                ->has('data.zones.0', fn ($json) =>
                    $json->where('id', $this->zone->id)
                        ->where('comune', $this->zone->comune)
                        ->where('label', $this->zone->label)
                        ->where('import_id', $this->zone->import_id)
                        ->has('avalaible_user_types.0', fn ($json) =>
                            $this->assertUserType($json)
                        )
                        ->has('addresses.0', fn ($json) =>
                            $this->assertAddress($json)
                        )
                )
            );
    }


    public function testIndexWithoutAuthenticatedUser(){
        $this->get(self::prefixForTheAPI . '/index')
            ->assertStatus(403);
    }

    public function testIndexWithUserWithoutAddresses(){
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->get(self::prefixForTheAPI . '/index')
            ->assertStatus(200)
            ->assertJson(fn ($json) =>
                $json->has('message')
                    ->where('message', 'calendar types')
                    ->has('success')
                    ->has('data')
                    ->where('data.zones', [])
            );
    }

    /** @test */
    public function testCreate(){

        Sanctum::actingAs($this->user);

        $request = $this->post(self::prefixForTheAPI . '/create', $this->newAddress)
            ->assertStatus(200)
            ->assertJson(fn ($json) =>
                $json->has('message')
                ->where('message', 'address correctly created')
                ->has('success')
                ->has('data.address')
            );
        $this->newAddress['id'] = $request->json()['data']['address']['id'];
        $this->assertThatAddressIsInDatabase($this->newAddress['id'], $this->newAddress);
    }

    public function testCreateWithoutAuthenticatedUser(){
        $this->post(self::prefixForTheAPI . '/create', $this->newAddress)
            ->assertStatus(403);
    }

    public function testCreateWithoutRequiredFields(){
        Sanctum::actingAs($this->user);

        $this->newAddress['address'] = null;
        $this->newAddress['city'] = null;
        $this->newAddress['location'] = null;

        $this->post(self::prefixForTheAPI . '/create', $this->newAddress)
            ->assertStatus(400)
            ->assertJson(fn ($json) =>
                $json->has('message')
                ->where('message', 'missed required fields')
                ->where('success', false)
            );
    }
    public function testCreateWithInvalidLocation(){
        Sanctum::actingAs($this->user);

        $this->newAddress['location'] = 'invalid';

        $request = $this->post(self::prefixForTheAPI . '/create', $this->newAddress)
            ->assertStatus(400)
            ->assertJson(fn ($json) =>
                $json->has('message') // Questo messaggio contiene un'eccezione non gestita
                    ->where('success', false)
            );

    }

    /** @test */
    public function testUpdate(){
        Sanctum::actingAs($this->user);

        $this->newAddress['id'] = $this->address->id;

        $this->post(self::prefixForTheAPI . '/update', $this->newAddress)
        ->assertStatus(200)
        ->assertJson(fn ($json) =>
            $json->has('message')
                ->where('message', 'address correctly updated')
                ->has('success')
                ->has('data.address')
            );

        $this->assertThatAddressIsInDatabase($this->newAddress['id'] , $this->newAddress);
    }

    public function testUpdateWithoutAuthenticatedUser(){
        $this->post(self::prefixForTheAPI . '/update', $this->newAddress)
            ->assertStatus(403);
    }

    public function testUpdateWithNonexistentAddress(){ 
        Sanctum::actingAs($this->user);

        $this->newAddress['id'] = 0;

        $this->post(self::prefixForTheAPI . '/update', $this->newAddress)
            ->assertStatus(400)
            ->assertJson(fn ($json) =>
                $json->has('message')
                ->where('message', 'address no avalaiable on db')
                ->where('success', false)
            );
    }

    public function testUpdateWithAddressNotPropertyOfTheUser(){
        Sanctum::actingAs($this->user);

        $this->newAddress['id'] = $this->fakeAddress->id;

        $this->post(self::prefixForTheAPI . '/update', $this->newAddress)
            ->assertStatus(400)
            ->assertJson(fn ($json) =>
                $json->has('message')
                ->where('message', 'address is not propery of this user')
                ->where('success', false)
            );
    }
    
    /** @test */
    public function testDelete(){
        Sanctum::actingAs($this->user);
        
        $this->get(self::prefixForTheAPI . '/delete/' . $this->address->id)
            ->assertStatus(200)
            ->assertJson(fn ($json) =>
                $json->has('message')
                ->where('message', 'address correctly deleted')
                ->has('success')
                ->has('data.address', fn ($json) =>
                    $this->assertAddress($json),
                )
            );
        
        $this->assertDatabaseMissing('addresses', [
            'id' => $this->address->id
        ]);
    }


    public function testDeleteWithoutAuthenticatedUser(){
        $this->get(self::prefixForTheAPI . '/delete/' . $this->address->id)
            ->assertStatus(403);
    }


    public function testDeleteWithNonexistentAddress(){
        Sanctum::actingAs($this->user);

        $this->get(self::prefixForTheAPI . '/delete/' . 0)
            ->assertStatus(400)
            ->assertJson(fn ($json) =>
                $json->has('message')
                ->where('message', 'address no avalaiable on db')
                ->where('success', false)
            );
    }

    public function testDeleteWithAddressNotPropertyOfTheUser(){
        Sanctum::actingAs($this->user);

        
        $this->get(self::prefixForTheAPI . '/delete/' . $this->fakeAddress->id)
            ->assertStatus(400)
            ->assertJson(fn ($json) =>
                $json->has('message')
                ->where('message', 'address is not propery of this user')
                ->where('success', false)
            );
    }


    // Utility functions


    private function assertUserType($userTypeJson)
    {
        $userTypeJson->where('id', $this->userType->id)
                     ->where('label', $this->userType->getTranslations('label'));
    }
    
    private function assertAddress($addressJson)
    {
        $addressJson->where('id', $this->address->id)
                   ->where('zone_id', $this->zone->id)
                   ->where('user_type_id', $this->address->user_type_id)
                   ->where('address', '123 Test Street')
                   ->has('location')
                   ->where('city', $this->address->city)
                   ->where('house_number', $this->address->house_number)
                   ->etc();
    }


    private function assertThatAddressIsInDatabase($addressId, $fieldsToCheck){
        $this->assertDatabaseHas('addresses', [
            'id' => $addressId,
            'address' => $fieldsToCheck['address'],
            'city' => $fieldsToCheck['city'], 
            'house_number' => $fieldsToCheck['house_number'],
            'zone_id' => $fieldsToCheck['zone_id'],
            'user_type_id' => $fieldsToCheck['user_type_id']
        ]);
    }
}
