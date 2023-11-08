<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GeojsonableGetGeometryTest extends TestCase
{
    /** @test */
    public function no_geometry_empty_array()
    {
        $f = new FeatureMock();
        $geojson = json_decode($f->getGeojsonGeometry(), true);
        $this->assertIsArray($geojson);
        $this->assertEquals(0, count($geojson));
    }

    /**
    "geometry": {
        "type": "MultiPolygon",
        "coordinates": [ [ [
            [10,45],
            [11,45],
            [11,46],
            [12,47],
            [10,45]
        ] ] ]
    }
     * @test */
    public function when_geometry_is_simple_multipolygon_it_returns_simple_multipolygon_geojson()
    {
        $f = new FeatureMock();
        $f->geometry = DB::select("(SELECT ST_GeomFromText('MULTIPOLYGON(((10 45, 11 45, 11 46, 12 47, 10 45)))')as g)")[0]->g;

        $geometry = json_decode($f->getGeojsonGeometry(), true);

        $this->assertArrayHasKey('type', $geometry);
        $this->assertEquals('MultiPolygon', $geometry['type']);

        $this->assertArrayHasKey('coordinates', $geometry);
        $this->assertIsArray($geometry['coordinates']);
        $this->assertIsArray($geometry['coordinates'][0]);
        $this->assertIsArray($geometry['coordinates'][0][0]);
        $this->assertEquals(5, count($geometry['coordinates'][0][0]));
        $this->assertEquals(2, count($geometry['coordinates'][0][0][0]));

        $this->assertEquals(10, $geometry['coordinates'][0][0][0][0]);
        $this->assertEquals(45, $geometry['coordinates'][0][0][0][1]);

        $this->assertEquals(11, $geometry['coordinates'][0][0][1][0]);
        $this->assertEquals(45, $geometry['coordinates'][0][0][1][1]);

        $this->assertEquals(11, $geometry['coordinates'][0][0][2][0]);
        $this->assertEquals(46, $geometry['coordinates'][0][0][2][1]);

        $this->assertEquals(12, $geometry['coordinates'][0][0][3][0]);
        $this->assertEquals(47, $geometry['coordinates'][0][0][3][1]);

        $this->assertEquals(10, $geometry['coordinates'][0][0][4][0]);
        $this->assertEquals(45, $geometry['coordinates'][0][0][4][1]);
    }

    /**
    "geometry": {
      "type": "Point",
        "coordinates": [
          10,
          45
        ]
    }
     * @test */
    public function when_geometry_is_point_it_returns_point_geojson()
    {
        $f = new FeatureMock();
        $f->geometry = DB::select("(SELECT ST_GeomFromText('POINT(10 45)')as g)")[0]->g;

        $geometry = json_decode($f->getGeojsonGeometry(), true);

        $this->assertArrayHasKey('type', $geometry);
        $this->assertEquals('Point', $geometry['type']);

        $this->assertArrayHasKey('coordinates', $geometry);
        $this->assertIsArray($geometry['coordinates']);
        $this->assertEquals(2, count($geometry['coordinates']));

        $this->assertEquals(10, $geometry['coordinates'][0]);
        $this->assertEquals(45, $geometry['coordinates'][1]);
    }
}

use App\Traits\GeojsonableTrait;

class FeatureMock
{
    use GeojsonableTrait;
    public $geometry;
}
