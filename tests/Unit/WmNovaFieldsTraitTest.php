<?php

namespace Tests\Unit;

use App\Models\User;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class WmNovaFieldsTraitTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User();
    }

    /** @test */
    public function filterOnlyFeSchemaRemovesFrontendOnlyFields(): void
    {
        $schema = [
            ['name' => 'fiscal_code', 'label' => 'Codice fiscale', 'type' => 'text'],
            ['name' => 'hidden_fe', 'label' => 'Hidden', 'type' => 'text', 'only_fe' => true],
            ['name' => 'visible', 'label' => 'Visible', 'type' => 'text', 'only_fe' => false],
        ];

        $filtered = $this->user->filterOnlyFeSchema($schema);

        $this->assertCount(2, $filtered);
        $this->assertSame('fiscal_code', $filtered[0]['name']);
        $this->assertSame('visible', $filtered[1]['name']);
    }

    /** @test */
    public function filterFormSchemaExcludingTypesRemovesPasswordAndGroup(): void
    {
        $schema = [
            ['name' => 'fiscal_code', 'label' => 'Codice fiscale', 'type' => 'text'],
            ['name' => 'secret', 'label' => 'Secret', 'type' => 'password'],
            ['name' => 'address_group', 'label' => 'Indirizzo', 'type' => 'group'],
        ];

        $filtered = $this->user->filterFormSchemaExcludingTypes($schema);

        $this->assertCount(1, $filtered);
        $this->assertSame('fiscal_code', $filtered[0]['name']);
    }

    /** @test */
    public function resolveFormFieldValuePrefersLabelThenNameThenDefault(): void
    {
        $fieldByLabel = ['name' => 'fiscal_code', 'label' => 'Codice fiscale', 'type' => 'text'];
        $fieldByName = ['name' => 'user_code', 'label' => 'Codice utente', 'type' => 'text'];
        $fieldDefault = ['name' => 'missing', 'label' => 'Missing', 'type' => 'text', 'value' => 'default'];

        $formData = [
            'Codice fiscale' => 'CF123',
            'user_code' => 'UC456',
        ];

        $this->assertSame('CF123', $this->user->resolveFormFieldValue($fieldByLabel, $formData));
        $this->assertSame('UC456', $this->user->resolveFormFieldValue($fieldByName, $formData));
        $this->assertSame('default', $this->user->resolveFormFieldValue($fieldDefault, $formData));
    }

    /** @test */
    public function jsonFormReadOnlyFieldsBuildsReadOnlyDetailFieldsAndSkipsPassword(): void
    {
        $schema = [
            ['name' => 'fiscal_code', 'label' => 'Codice fiscale', 'type' => 'text'],
            ['name' => 'age', 'label' => 'Eta', 'type' => 'number'],
            ['name' => 'secret', 'label' => 'Secret', 'type' => 'password'],
        ];
        $formData = [
            'Codice fiscale' => 'CF123',
            'Eta' => 42,
        ];

        $fields = $this->user->jsonFormReadOnlyFields($schema, $formData);
        $request = NovaRequest::create('/nova-api/users/1', 'GET');

        $this->assertCount(2, $fields);
        $this->assertInstanceOf(Text::class, $fields[0]);
        $this->assertInstanceOf(Number::class, $fields[1]);
        $this->assertTrue($fields[0]->isReadonly($request));
        $this->assertTrue($fields[1]->isReadonly($request));
    }
}
