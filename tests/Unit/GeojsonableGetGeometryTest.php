<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GeojsonableGetGeometryTest extends TestCase
{
    /** @test */
    public function no_geometry_empty_array(){
        $f = new FeatureMock();
        $geojson = json_decode($f->getGeojsonGeometry(),true);
        $this->assertIsArray($geojson);
        $this->assertEquals(0,count($geojson));
    }
}

use App\Traits\GeojsonableTrait;
class FeatureMock {
    use GeojsonableTrait;
    public $geometry;
}