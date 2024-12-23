<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ModelUserTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test that populateFormData correctly populates the form_data attribute.
     *
     * @return void
     */
    public function testPopulateFormData()
    {
        $expectedFormData = [
            'phone_number' => '123-456-7890',
            'fiscal_code'  => 'ABCDEF123456',
            'user_code'    => 'USER123',
        ];
        $user = User::factory()->create([
            ...$expectedFormData,
            'form_data'      => null, // Initially null
        ]);
        $user->populateFormData();
        $user->refresh();
        $this->assertEquals($expectedFormData, $user->form_data);
        $this->assertFalse($user->timestamps);
    }

}
