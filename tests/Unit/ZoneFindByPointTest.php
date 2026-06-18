<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ZoneFindByPointTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
    }

    private function makeZoneWithPolygon(array $coords, Company $company = null): Zone
    {
        $c = $company ?? $this->company;
        $wkt = 'MULTIPOLYGON(((' . implode(', ', array_map(fn($p) => "{$p[0]} {$p[1]}", $coords)) . ')))';
        $geometry = DB::selectOne("SELECT ST_GeomFromText(?, 4326) AS g", [$wkt])->g;

        return Zone::create([
            'company_id' => $c->id,
            'comune'     => 'Test',
            'label'      => 'Test Zone',
            'url'        => 'http://test.example',
            'geometry'   => $geometry,
        ]);
    }

    private function pointGeomSrid0(float $lon, float $lat): string
    {
        return DB::selectOne("SELECT ST_GeomFromText('POINT($lon $lat)') AS g")->g;
    }

    private function pointGeomSrid4326(float $lon, float $lat): string
    {
        return DB::selectOne("SELECT ST_GeomFromText('POINT($lon $lat)', 4326) AS g")->g;
    }

    /** @test */
    public function returns_zone_when_point_is_inside(): void
    {
        $zone = $this->makeZoneWithPolygon([
            [10.0, 45.0], [11.0, 45.0], [11.0, 46.0], [10.0, 46.0], [10.0, 45.0],
        ]);

        $result = Zone::findByPoint($this->pointGeomSrid4326(10.5, 45.5), $this->company->id);

        $this->assertNotNull($result);
        $this->assertEquals($zone->id, $result->id);
    }

    /** @test */
    public function returns_null_when_point_is_outside_all_zones(): void
    {
        $this->makeZoneWithPolygon([
            [10.0, 45.0], [11.0, 45.0], [11.0, 46.0], [10.0, 46.0], [10.0, 45.0],
        ]);

        $result = Zone::findByPoint($this->pointGeomSrid4326(0.0, 0.0), $this->company->id);

        $this->assertNull($result);
    }

    /** @test */
    public function returns_null_when_company_id_does_not_match(): void
    {
        $this->makeZoneWithPolygon([
            [10.0, 45.0], [11.0, 45.0], [11.0, 46.0], [10.0, 46.0], [10.0, 45.0],
        ]);
        $otherCompany = Company::factory()->create();

        $result = Zone::findByPoint($this->pointGeomSrid4326(10.5, 45.5), $otherCompany->id);

        $this->assertNull($result);
    }

    /** @test */
    public function returns_smallest_zone_when_zones_overlap(): void
    {
        // Large zone covering 10-11, 45-46
        $this->makeZoneWithPolygon([
            [10.0, 45.0], [11.0, 45.0], [11.0, 46.0], [10.0, 46.0], [10.0, 45.0],
        ]);
        // Small zone covering only 10.4-10.6, 45.4-45.6 (subset of large)
        $smallZone = $this->makeZoneWithPolygon([
            [10.4, 45.4], [10.6, 45.4], [10.6, 45.6], [10.4, 45.6], [10.4, 45.4],
        ]);

        $result = Zone::findByPoint($this->pointGeomSrid4326(10.5, 45.5), $this->company->id);

        $this->assertNotNull($result);
        $this->assertEquals($smallZone->id, $result->id);
    }

    /** @test */
    public function accepts_srid_0_geometry_pre_save(): void
    {
        $zone = $this->makeZoneWithPolygon([
            [10.0, 45.0], [11.0, 45.0], [11.0, 46.0], [10.0, 46.0], [10.0, 45.0],
        ]);

        // Simulates $ticket->geometry before save (ST_GeomFromText without SRID → SRID=0)
        $geomSrid0 = $this->pointGeomSrid0(10.5, 45.5);

        $result = Zone::findByPoint($geomSrid0, $this->company->id);

        $this->assertNotNull($result);
        $this->assertEquals($zone->id, $result->id);
    }

    /** @test */
    public function accepts_srid_4326_geometry_post_save(): void
    {
        $zone = $this->makeZoneWithPolygon([
            [10.0, 45.0], [11.0, 45.0], [11.0, 46.0], [10.0, 46.0], [10.0, 45.0],
        ]);

        $geomSrid4326 = $this->pointGeomSrid4326(10.5, 45.5);

        $result = Zone::findByPoint($geomSrid4326, $this->company->id);

        $this->assertNotNull($result);
        $this->assertEquals($zone->id, $result->id);
    }
}
