<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\ZoneMetaResource;
use App\Models\UserType;
use App\Models\Zone;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ResourceZoneMetaTest extends TestCase
{
    use DatabaseTransactions;

    private $zone;

    const attributes = [
        'id' => 1,
        'comune' => 'Test Comune',
        'label' => 'Test Label',
        'url' => 'test-url',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->zone = Zone::factory()->create(self::attributes);
    }

    /** @test */
    public function testCorrectlyTransformsZoneData()
    {
        // Create a zone with user types

        $userTypes = UserType::factory()->count(2)->create([
            'slug' => 'type-1',
        ]);

        $this->zone->userTypes()->attach($userTypes->pluck('id'));

        // Create a mock resource object with zones relationship
        $result = $this->createResource($this->zone);

        // Assert the structure
        $this->assertEquals([
            [
                ...self::attributes,
                'types' => ['type-1', 'type-1'],
            ]
        ], $result);
    }

    /** @test */
    public function testHandleZonesWithoutUserTypes()
    {
        // Create a mock resource object with zones relationship
        $result = $this->createResource($this->zone);
        // Assert the structure
        $this->assertEquals([
            [
                ...self::attributes,
            ]
        ], $result);
    }

    /** @test */
    public function testReturnEmptyArrayWhenNoZones()
    {
        $result = $this->createResource();
        $this->assertEquals([], $result);
    }

    private function createResource(Zone $zone = null):array
    {
        $resourceObject = new \stdClass();
        $resourceObject->zones = $zone ? collect([$zone]) : collect([]);
        $resource = new ZoneMetaResource($resourceObject);
        return $resource->toArray(request());
    }
}
