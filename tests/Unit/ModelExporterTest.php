<?php

namespace Tests\Unit;

use App\Models\ModelExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;
use Mockery;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ModelExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private Collection $users;
    private Collection $usersWithProfiles;
    private Mockery\MockInterface $queryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->users = collect([
            [
                'id' => 1,
                'name' => 'John',
                'surname' => 'Doe',
                'email' => 'john@example.com',
                'is_active' => true,
                'profile_id' => 11
            ],
            [
                'id' => 2,
                'name' => 'Jane',
                'surname' => 'Smith',
                'email' => 'jane@example.com',
                'is_active' => false,
                'profile_id' => 22
            ],
        ]);

        $profiles = collect([
            (object)['id' => 11, 'phone' => '1234567890'],
            (object)['id' => 22, 'phone' => '0987654321'],
        ]);

        $this->usersWithProfiles = $this->users->map(function ($user) use ($profiles) {
            $user['profile'] = $profiles->firstWhere('id', $user['profile_id']);
            return $user;
        });

        $modelMock = Mockery::mock('Model');
        $modelMock->shouldReceive('getTable')->andReturn('users');

        $this->queryMock = Mockery::mock(Builder::class);
        $this->queryMock->shouldReceive('with')->andReturnSelf();
        $this->queryMock->shouldReceive('get')->andReturn($this->usersWithProfiles);
        $this->queryMock->shouldReceive('getModel')->andReturn($modelMock);

        Schema::shouldReceive('getColumnListing')
            ->with('users')
            ->andReturn(['id', 'name', 'surname', 'email', 'profile_id']);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testCollectionWithNoColumns()
    {
        $exporter = new ModelExporter($this->queryMock);
        $collection = $exporter->collection();
        $this->assertEquals($this->usersWithProfiles, $collection);
    }

    public function testCollectionWithSpecificColumns()
    {
        $columns = ['name', 'email'];
        $exporter = new ModelExporter($this->queryMock, $columns);

        $collection = $exporter->collection();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(2, $collection);
        $this->assertEquals([
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => 'jane@example.com'],
        ], $collection->toArray());
    }

    public function testCollectionWithRelations()
    {
        $relations = ['profile' => 'phone'];
        $columns = ['name', 'profile.phone'];
        $exporter = new ModelExporter($this->queryMock, $columns, $relations);

        $collection = $exporter->collection();

        $this->assertEquals([
            ['name' => 'John', 'profile.phone' => '1234567890'],
            ['name' => 'Jane', 'profile.phone' => '0987654321'],
        ], $collection->toArray());
    }

    public function testHeadingsWithCustomHeaders()
    {
        $columns = ['name' => 'Name', 'profile.phone' => 'Phone'];
        $relations = ['profile' => 'phone'];
        $exporter = new ModelExporter($this->queryMock, $columns, $relations);

        $headings = $exporter->headings();

        $this->assertEquals([
            __('Name'),
            __('Phone')
        ], $headings);
    }

    public function testHeadingsWithoutCustomHeaders()
    {
        $exporter = new ModelExporter($this->queryMock);
        $headings = $exporter->headings();

        $this->assertEquals([
            'id',
            'name',
            'surname',
            'email',
            'profile_id',
        ], $headings);
    }

    public function testDefaultStyle()
    {
        $exporter = new ModelExporter($this->queryMock);
        $styles = $exporter->styles($this->createMock(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::class));

        $this->assertEquals([
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 14,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ]
        ], $styles);
    }

    public function testCustomStyle()
    {
        $customStyles = [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 14,
                ],
            ]
        ];
        $exporter = new ModelExporter($this->queryMock, [], [], $customStyles);
        $styles = $exporter->styles($this->createMock(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::class));

        $this->assertEquals($customStyles, $styles);
    }

    public function testMapWithBooleanValues()
    {
        $exporter = new ModelExporter($this->queryMock);
        $mapped = $exporter->map($this->usersWithProfiles->first());

        $this->assertEquals([
            'id' => 1,
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john@example.com',
            'is_active' => __('Yes'),
            'profile_id' => 11,
            'profile' => (object)['id' => 11, 'phone' => '1234567890']
        ], $mapped);
    }
}
