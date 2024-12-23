<?php

namespace Tests\Unit\Resources;

use Tests\TestCase;
use App\Models\Waste;
use App\Models\TrashType;
use App\Http\Resources\RifiutarioResource;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ResourceRifiutarioTest extends TestCase
{
    use DatabaseTransactions;

    const trashTypeId = 1;
    const translations = [
        'it' => [
            'name' => 'Bottiglia',
            'where' => 'Contenitore vetro',
            'notes' => 'Note in italiano',
        ],
        'en' => [
            'name' => 'Bottle',
            'where' => 'Glass container',
            'notes' => 'Notes in English',
        ],
    ];
    /** @test */
    public function testCorrectlyTransformsWastesIntoArray()
    {
        $trashType = TrashType::factory()->create(['id' => self::trashTypeId]);

        $waste = Waste::factory()->create([
            'trash_type_id' => $trashType->id,
            'pap' => true,
            'delivery' => false,
            'collection_center' => true,
        ]);

        $waste->setTranslations('name', [
            'it' => self::translations['it']['name'],
            'en' => self::translations['en']['name']
        ]);
        $waste->setTranslations('where', [
            'it' => self::translations['it']['where'],
            'en' => self::translations['en']['where']
        ]);
        $waste->setTranslations('notes', [
            'it' => self::translations['it']['notes'],
            'en' => self::translations['en']['notes']
        ]);
        $waste->save();

        $trashType->refresh();
        
        $result = $this->createResourceAndTurnIntoArray($trashType);

        $this->assertEquals([
            [
                'id' => $waste->id,
                'name' => self::translations['it']['name'],
                'where' => self::translations['it']['where'],
                'notes' => self::translations['it']['notes'],
                'pap' => true,
                'delivery' => false,
                'collection_center' => true,
                'trash_type_id' => self::trashTypeId,
                'translations' => [
                    'en' => [
                        'name' => self::translations['en']['name'],
                        'where' => self::translations['en']['where'],
                        'notes' => self::translations['en']['notes'],
                    ],
                ],
            ]
        ], $result);
    }

    /** @test */
    public function testReturnsEmptyArrayWhenNoWastes()
    {
        $trashType = TrashType::factory()->create();
        
        $result = $this->createResourceAndTurnIntoArray($trashType);

        $this->assertEquals([], $result);
    }

    private function createResourceAndTurnIntoArray(TrashType $trashType): array
    {
        $resource = new RifiutarioResource($trashType);
        return $resource->toArray(request());
    }
}
