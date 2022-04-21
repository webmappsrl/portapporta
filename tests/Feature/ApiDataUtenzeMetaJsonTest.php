<?php

namespace Tests\Feature;

use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Nova\Console\RenamesStubs;
use Tests\TestCase;

class ApiDataUtenzeMetaJsonTest extends TestCase
{

    // https://apiersu.netseven.it/data/utenze_meta.json

    use RefreshDatabase;

        /**
     * 
     *
     * @return void
     * @test
     */
    public function when_visit_utenze_meta_json_return_200()
    {
        $ut = UserType::factory()->create();
        $response = $this->get('/api/c/'.$ut->company->id.'/data/utenze_meta.json');

        $response->assertStatus(200);
    }



}
