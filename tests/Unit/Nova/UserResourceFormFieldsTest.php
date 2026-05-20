<?php

namespace Tests\Unit\Nova;

use App\Models\Company;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserResourceFormFieldsTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function jsonFormExcludesOnlyFeFieldsWhenSchemaIsFiltered(): void
    {
        $company = Company::factory()->create([
            'form_json' => json_encode([
                ['name' => 'fiscal_code', 'label' => 'Codice fiscale', 'type' => 'text'],
                ['name' => 'internal', 'label' => 'Solo FE', 'type' => 'text', 'only_fe' => true],
            ]),
        ]);
        $user = User::factory()->create([
            'app_company_id' => $company->id,
            'form_data' => [
                'Codice fiscale' => 'CF123456',
                'Solo FE' => 'hidden-value',
            ],
        ]);

        $schema = json_decode($company->form_json, true);
        $filtered = $user->filterOnlyFeSchema($schema);
        $fields = $user->jsonForm('form_data', $filtered);

        $this->assertCount(1, $fields);
        $this->assertSame('form_data->fiscal_code', $fields[0]->attribute);
    }
}
