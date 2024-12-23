<?php

namespace Tests\Unit\Resources;

use Tests\TestCase;
use App\Models\WasteCollectionCenter;
use App\Http\Resources\CentriRaccoltaResource;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\UserType;
use App\Models\TrashType;

class ResourceCentriRaccoltaTest extends TestCase
{
    use DatabaseTransactions;
    
    private const userTypeId = 1;
    private const trashTypeId = 1;
    private $userType;
    private $trashType;
    private $wasteCollectionCenter;
    private const attributes = [
        'marker_color' => '#FF0000',
        'marker_size' => 'medium',
        'website' => 'https://example.com',
        'picture_url' => 'https://example.com/image.jpg',
        'geometry' => "Point(10 45)"
    ];
    private const translations = [
        'it' => [
            'name' => 'Centro di Raccolta',
            'orario' => '9:00 - 18:00',
            'description' => 'Descrizione del centro'
        ],
        'en' => [
            'name' => 'Collection Center',
            'orario' => '9:00 AM - 6:00 PM',
            'description' => 'Center description'
        ]
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->userType = UserType::factory()->create(['id' => self::userTypeId]);
        $this->trashType = TrashType::factory()->create(['id' => self::trashTypeId]);
        $this->wasteCollectionCenter = WasteCollectionCenter::factory()->create(self::attributes);
    }

    /** @test */
    public function testTransformDataIntoValidFormat()
    {
        // Create a waste collection center with translations
        $this->setTranslations($this->wasteCollectionCenter);
        $this->attachUserTypesAndTrashTypes($this->wasteCollectionCenter);

        $result = $this->createResource($this->wasteCollectionCenter);

        // Assert the basic structure
        $this->assertEquals('FeatureCollection', $result['type']);
        $this->assertIsArray($result['features']);
        $this->assertCount(1, $result['features']);

        // Assert the first feature
        $feature = $result['features'][0];
        $this->assertEquals('Feature', $feature['type']);

        // Assert properties
        $properties = $feature['properties'];
        $this->assertEquals(self::attributes['marker_color'], $properties['marker-color']);
        $this->assertEquals(self::attributes['marker_size'], $properties['marker-size']);
        $this->assertEquals(self::attributes['website'], $properties['website']);
        $this->assertEquals(self::attributes['picture_url'], $properties['picture_url']);
        $this->assertEquals(self::translations['it']['name'], $properties['name']);
        $this->assertEquals(self::translations['it']['orario'], $properties['orario']);
        $this->assertEquals(self::translations['it']['description'], $properties['description']);

        // Assert translations
        $this->assertEquals(self::translations['en']['name'], $properties['translations']['en']['name']);
        $this->assertEquals(self::translations['en']['orario'], $properties['translations']['en']['orario']);
        $this->assertEquals(self::translations['en']['description'], $properties['translations']['en']['description']);

        // Assert user and trash types
        $this->assertEquals([self::userTypeId], $properties['user_types']);
        $this->assertEquals([self::trashTypeId], $properties['trash_types']);

        // Assert geometry
        $this->assertEquals('Point', $feature['geometry']['type']);
        $this->assertEquals([10, 45], $feature['geometry']['coordinates']);
    }

    /** @test */
    public function it_handles_empty_collection_centers()
    {
        $result = $this->createResource(null);

        $this->assertEquals('FeatureCollection', $result['type']);
        $this->assertIsArray($result['features']);
        $this->assertEmpty($result['features']);
    }

    private function setTranslations(WasteCollectionCenter $collectorToAddTranslationsTo): void
    {
        $collectorToAddTranslationsTo->setTranslations('name', [
            'it' => self::translations['it']['name'],
            'en' => self::translations['en']['name']
        ]);
        $collectorToAddTranslationsTo->setTranslations('orario', [
            'it' => self::translations['it']['orario'],
            'en' => self::translations['en']['orario']
        ]);
        $collectorToAddTranslationsTo->setTranslations('description', [
            'it' => self::translations['it']['description'],
            'en' => self::translations['en']['description']
        ]);
    }

    private function attachUserTypesAndTrashTypes(WasteCollectionCenter $wasteCollectionCenter): void
    {
        $wasteCollectionCenter->userTypes()->attach($this->userType->id);
        $wasteCollectionCenter->trashTypes()->attach($this->trashType->id);
    }

    private function createResource(WasteCollectionCenter $wasteCollectionCenter = null):array
    {
        if($wasteCollectionCenter) {
            $resource = new CentriRaccoltaResource((object)['wasteCollectionCenters' => collect([$wasteCollectionCenter])]);
            return $resource->toArray(request());
        }
        $resource = new CentriRaccoltaResource((object)['wasteCollectionCenters' => collect([])]);
        return $resource->toArray(request());
    }
}
