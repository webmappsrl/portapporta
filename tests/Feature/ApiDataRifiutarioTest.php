<?php

namespace Tests\Feature;

use App\Models\Waste;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiDataRifiutarioTest extends TestCase
{
    // https://apiersu.netseven.it/data/rifiutario.json

    use RefreshDatabase;

    /** @test     */
    public function rifiutario_returns_200()
    {
        $w = Waste::factory()->create();
        $response = $this->get('/api/c/'.$w->company->id.'/data/rifiutario.json');

        $response->assertStatus(200);
    }
}
