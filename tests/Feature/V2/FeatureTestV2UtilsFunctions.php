<?php

namespace Tests\Feature\V2;

use App\Models\User;
use App\Models\Company;
use App\Models\Zone;
use App\Models\Address;
use App\Models\UserType;
use Illuminate\Testing\Fluent\AssertableJson;

trait FeatureTestV2UtilsFunctions
{
    protected function createAddress($user, $zone, array $extraAttributes = []){
        if(!isset($extraAttributes['address'])){
            $extraAttributes["address"] = "123 Test Street";
            $extraAttributes["location"] = "POINT(10 45)";
        }
        return Address::factory()->create([
            'user_id' => $user->id,
            'zone_id' => $zone->id,
            ...$extraAttributes
        ]);
    }

    protected function createUser($zone = null, $company = null, array $extraAttributes = []){
        $attributesToCreateTheUserWith = [];
        if($zone){
            $attributesToCreateTheUserWith['zone_id'] = $zone->id;
        }
        if($company){
            $attributesToCreateTheUserWith['app_company_id'] = $company->id;
        }
        $attributesToCreateTheUserWith['password'] = bcrypt('password');
        $attributesToCreateTheUserWith = [
            ...$attributesToCreateTheUserWith,
            ...$extraAttributes
        ];
        return User::factory()->create($attributesToCreateTheUserWith);
    }
    protected function createUserType(){
        return UserType::factory()->create();
    }

    protected function createZone($company = null){
        if(!$company){
            return Zone::factory()->create();
        }
        return Zone::factory()->create(['company_id' => $company->id]);
    }

    protected function createCompany(){
        return Company::factory()->create([
            'ticket_email' => 'test@example.com'
        ]);
    }

    protected function createFieldsToCheckForAddress($address){
        return [
            'id' => $address->id,
            'address' => $address->address,
            'city' => $address->city,
            'house_number' => $address->house_number,
            'zone_id' => $address->zone_id,
            'user_type_id' => $address->user_type_id,
        ];
    }

    protected function assertAddressData(AssertableJson $json, array $fieldsToCheck): AssertableJson
    {
        foreach ($fieldsToCheck as $key => $value) {
            if ($key == 'location') {
                $json->has('location', fn ($json) => $this->assertLocationData($json, $value));
            } else {
                $json->where($key, $value);
            }
        }
        return $json->etc();
    }

    private function assertLocationData(AssertableJson $json, string $location): AssertableJson
    {
        // Extract numbers from POINT(x y) format
        $location = trim(str_replace(['POINT(', ')'], '', $location));
        $location = explode(' ', $location);
        return $json
            ->where('0', (int)$location[0])
            ->where('1', (int)$location[1]);
    }


    protected function assertErrorResponse($response, $expectedMessage = '', $status = 400, $checkData = false): void
    {
        $response->assertStatus($status)
                 ->assertJson(function (AssertableJson $json) use ($expectedMessage, $checkData) {
                     $json->where('success', false);
                        if(empty($expectedMessage)){
                            $json->etc();
                        }else{
                            if($checkData){
                                $json->where('data', function ($data) use ($expectedMessage) {
                                    return str_contains($data, $expectedMessage);
                                });
                            }else{
                                $json->where('message', function ($message) use ($expectedMessage) {
                                    return str_contains($message, $expectedMessage);
                                });
                            }
                        }
                        $json->etc();
                 });
    }
    
    protected function assertSuccessResponse($response, $expectedMessage = '', $status = 200): void
    {
        $response->assertStatus($status)
                ->assertJson(fn (AssertableJson $json) =>
                    $json->where('success', true)
                         ->where('message', $expectedMessage)
                         ->etc()
                );
    }


}
