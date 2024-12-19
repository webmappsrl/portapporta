<?php

namespace Tests\Feature\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\UserType;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\V2\FeatureTestV2UtilsFunctions;

class AddressControllerTest extends TestCase
{
    use DatabaseTransactions, FeatureTestV2UtilsFunctions;

    private $user;
    private $fakeUser;
    private $zone;
    private $userType;
    private $address;
    private $fakeAddress;
    private $newFieldsForAddress;
    private $fieldsToCheckForAddress;
    const prefixForTheAPI = '/api/v2/address';
    const responseMessages = [
        'addressCorreclyRetrieved' => 'calendar types',
        'addressCorrectlyCreated' => 'address correctly created',
        'addressCorrectlyUpdated' => 'address correctly updated',
        'addressCorrectlyDeleted' => 'address correctly deleted',
        'theAddressIsNotPropertyOfTheUser' => 'address is not propery of this user',
        'theAddressIsNotAvalaiableOnDb' => 'address no avalaiable on db',
        'userHasNoAddresses' => 'calendar types',
        'missedRequiredFields' => 'missed required fields',
        'invalidLocation' => '',
        'unauthorized' => '',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->userType = $this->createUserType();
        $this->zone = $this->createZone();
        $this->user = $this->createUser($this->zone);
        $this->address = $this->createAddress($this->user, $this->zone);
        $this->fakeUser = $this->createUser($this->zone);
        $this->fakeAddress = $this->createAddress($this->fakeUser, $this->zone);
        $this->newFieldsForAddress = [
            'user_id' => $this->user->id,
            'zone_id' => $this->zone->id,
            'user_type_id' => $this->userType->id,
            'address' => '221b Baker Street',
            'location' => [15, 50],
            'city' => 'London',
            'house_number' => '221b'
        ];
        $this->zone->userTypes()->attach($this->userType->id);
        $this->fieldsToCheckForAddress = $this->createFieldsToCheckForAddress($this->address);
    }

    /** @test */
    public function testIndex()
    {
        Sanctum::actingAs($this->user);
        $response = $this->get(self::prefixForTheAPI . '/index');
        $this->assertSuccessResponse(
            $response, 
            self::responseMessages['userHasNoAddresses']
        );
        $response->assertJson(fn ($json) =>
            $json->has('data.zones.0', fn ($json) =>
                $this->assertZoneData($json, $this->zone)
            )
            ->etc()
        );
    }

    /** @test */
    public function testIndexWithoutAuthenticatedUser(){
        $this->assertErrorResponse(
            $this->get(self::prefixForTheAPI . '/index'), 
            self::responseMessages['unauthorized'],
            403
        );
    }

    /** @test */
    public function testIndexWithUserWithoutAddresses(){
        Sanctum::actingAs($this->user);
        $this->assertSuccessResponse(
            $this->get(self::prefixForTheAPI . '/index'),
            self::responseMessages['userHasNoAddresses']
        );
    }

    /** @test */
    public function testCreate(){
        Sanctum::actingAs($this->user);
        $request = $this->post(self::prefixForTheAPI . '/create', $this->newFieldsForAddress);
        $this->assertSuccessResponse(
            $request, 
            self::responseMessages['addressCorrectlyCreated']
        );
        $this->newFieldsForAddress['id'] = $request->json()['data']['address']['id'];
        $this->assertThatAddressIsInDatabase($this->newFieldsForAddress['id'], $this->newFieldsForAddress);
    }

    /** @test */
    public function testCreateWithoutAuthenticatedUser(){
        $this->assertErrorResponse(
            $this->post(self::prefixForTheAPI . '/create', $this->newFieldsForAddress), 
            self::responseMessages['unauthorized'],
            403
        );
    }

    /** @test */
    public function testCreateWithoutRequiredFields(){
        $this->newFieldsForAddress['address'] = null;
        $this->newFieldsForAddress['city'] = null;
        $this->newFieldsForAddress['location'] = null;
        Sanctum::actingAs($this->user);
        $this->assertErrorResponse(
            $this->post(self::prefixForTheAPI . '/create', $this->newFieldsForAddress), 
            self::responseMessages['missedRequiredFields'],
            400
        );
    }

    /** @test */
    public function testCreateWithInvalidLocation(){
        Sanctum::actingAs($this->user);
        $this->newFieldsForAddress['location'] = 'invalid';
        $this->assertErrorResponse(
            $this->post(self::prefixForTheAPI . '/create', $this->newFieldsForAddress), 
            self::responseMessages['invalidLocation'],
            400
        );

    }

    /** @test */
    public function testUpdate(){
        Sanctum::actingAs($this->user);
        $this->newFieldsForAddress['id'] = $this->address->id;
        $this->assertSuccessResponse(
            $this->post(self::prefixForTheAPI . '/update', $this->newFieldsForAddress), 
            self::responseMessages['addressCorrectlyUpdated']
        );
        $this->assertThatAddressIsInDatabase($this->newFieldsForAddress['id'] , $this->newFieldsForAddress);
    }

    /** @test */
    public function testUpdateWithoutAuthenticatedUser(){
        $this->assertErrorResponse(
            $this->post(self::prefixForTheAPI . '/update', $this->newFieldsForAddress), 
            self::responseMessages['unauthorized'],
            403
        );
    }

    /** @test */
    public function testUpdateWithNonexistentAddress(){ 
        Sanctum::actingAs($this->user);
        $this->newFieldsForAddress['id'] = 0;
        $this->assertErrorResponse(
            $this->post(self::prefixForTheAPI . '/update', $this->newFieldsForAddress), 
            self::responseMessages['theAddressIsNotAvalaiableOnDb'], 
            400
        );
    }

    /** @test */
    public function testUpdateWithAddressNotPropertyOfTheUser(){
        Sanctum::actingAs($this->user);
        $this->newFieldsForAddress['id'] = $this->fakeAddress->id;
        $this->assertErrorResponse(
            $this->post(self::prefixForTheAPI . '/update', $this->newFieldsForAddress), 
            self::responseMessages['theAddressIsNotPropertyOfTheUser'], 
            400
        );
    }
    
    /** @test */
    public function testDelete(){
        Sanctum::actingAs($this->user);
        $response = $this->get(self::prefixForTheAPI . '/delete/' . $this->address->id);
        $this->assertSuccessResponse(
            $response, 
            self::responseMessages['addressCorrectlyDeleted']
        );
        $response->assertJson(fn ($json) =>
            $json->has('data.address', fn ($json) =>
                $this->assertAddressData($json, $this->fieldsToCheckForAddress)
            )
            ->etc()
        );
        $this->assertDatabaseMissing('addresses', [
            'id' => $this->address->id
        ]);
    }

    /** @test */
    public function testDeleteWithoutAuthenticatedUser(){
        $this->assertErrorResponse(
            $this->get(self::prefixForTheAPI . '/delete/' . $this->address->id), 
            self::responseMessages['unauthorized'],
            403
        );
    }

    /** @test */
    public function testDeleteWithNonexistentAddress(){
        Sanctum::actingAs($this->user);

        $this->assertErrorResponse(
            $this->get(self::prefixForTheAPI . '/delete/' . 0), 
            self::responseMessages['theAddressIsNotAvalaiableOnDb'],
            400
        );
    }

    /** @test */
    public function testDeleteWithAddressNotPropertyOfTheUser(){
        Sanctum::actingAs($this->user);

        $this->assertErrorResponse(
            $this->get(self::prefixForTheAPI . '/delete/' . $this->fakeAddress->id), 
            self::responseMessages['theAddressIsNotPropertyOfTheUser'],
            400
        );
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

    private function assertZoneData($json, $zone){
        $json->where('id', $zone->id)
            ->where('comune', $zone->comune)
            ->where('label', $zone->label)
            ->where('import_id', $zone->import_id)
            ->has('avalaible_user_types.0', fn ($json) =>
                $this->assertUserTypeData($json, $this->userType)
            )
            ->has('addresses.0', fn ($json) =>
                $this->assertAddressData($json, $this->fieldsToCheckForAddress)
            )
            ->etc();
    }

    private function assertUserTypeData(AssertableJson $json, UserType $userType): AssertableJson
    {
        return $json->where('id', $userType->id)
                     ->where('label', $userType->getTranslations('label'));
    }
}
