<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;

trait WmNovaFieldsTrait
{
    /**
     * Generate Nova fields based on a JSON schema or a provnameed schema array.
     *
     * @param string|null $name The name of the column where the form JSON is stored. Default is null.
     * @param array|null $formSchema The optional schema for fields.
     * @return array
     * @throws \Exception
     */
    public function jsonForm(string $columnName, array $formSchema = null)
    {
        // Ensure Laravel Nova is installed
        $this->ensureNovaIsInstalled();

        $fields = [];

        if ($columnName && Schema::hasColumn($this->getTable(), $columnName)) {
            // Fetch the JSON data from the column
            $column = $this->$columnName ?? '';
            if (!is_array($column)) {
                $formData = json_decode($column, true) ?? [];
            } else {
                $formData = $column;
            }
            if (is_null($formSchema) || empty($formSchema)) {
                // If no form schema is provided, use the form data directly
                foreach ($formData as $key => $value) {
                    // Create a dummy schema based on existing form data
                    $fieldSchema = [
                        'name' => $key,
                        'type' => is_numeric($value) ? 'number' : 'text',
                        'value' => $value
                    ];
                    $novaField = $this->createFieldFromSchema($fieldSchema, $columnName);
                    if ($novaField) {
                        $fields[] = $novaField;
                    }
                }
            } else {
                // Initialize the fields with data from the JSON column
                foreach ($formSchema as $fieldSchema) {
                    $fieldSchema['value'] = $this->resolveFormFieldValue($fieldSchema, $formData);
                    $novaField = $this->createFieldFromSchema($fieldSchema, $columnName);
                    if ($novaField) {
                        $fields[] = $novaField;
                    }
                }
            }
        } elseif ($formSchema) {
            // Use the provnameed form schema
            foreach ($formSchema as $fieldSchema) {
                $novaField = $this->createFieldFromSchema($fieldSchema);
                if ($novaField) {
                    $fields[] = $novaField;
                }
            }
        } else {
            throw new \Exception('Either form JSON column name or form schema must be provnameed. Please check your database or
provnamee a form schema.');
        }

        return $fields;
    }

    /**
     * Create a Nova field based on the field schema.
     *
     * @param array $fieldSchema
     * @param string|null $columnName
     * @return \Laravel\Nova\Fields\Field|null
     */
    protected function createFieldFromSchema(array $fieldSchema, $columnName = null)
    {
        // Ensure Laravel Nova is installed
        $this->ensureNovaIsInstalled();

        $key = $fieldSchema['name'] ?? null;
        $value = $fieldSchema['value'] ?? null;
        $fieldType = $fieldSchema['type'] ?? 'text';
        $label = $fieldSchema['label'] ?? ucwords(str_replace('_', ' ', $key));
        $rules = [];
        $formData = is_array($this->$columnName) ? $this->$columnName : json_decode($this->$columnName, true);

        if (isset($fieldSchema['rules'])) {
            foreach ($fieldSchema['rules'] as $rule) {
                if ($rule['name'] === 'required') {
                    $rules[] = 'required';
                } elseif ($rule['name'] === 'email') {
                    $rules[] = 'email';
                } elseif ($rule['name'] === 'minLength' && isset($rule['value'])) {
                    $rules[] = 'min:' . $rule['value'];
                }
            }
        }

        $field = null;


        if ($fieldType === 'number') {
            $field = \Laravel\Nova\Fields\Number::make(__($label), "$columnName->$key")
                ->rules($rules);
        } elseif ($fieldType === 'password') {
            $field = \Laravel\Nova\Fields\Password::make(__($label), "$columnName->$key")
                ->rules($rules);
        } else {
            $field = \Laravel\Nova\Fields\Text::make(__($label), "$columnName->$key")
                ->rules($rules);
        }

        return $field;
    }

    /**
     * Remove from a form_json schema all fields flagged as `only_fe` (frontend only).
     *
     * @param array $formSchema
     * @return array
     */
    public function filterOnlyFeSchema(array $formSchema): array
    {
        return array_values(array_filter($formSchema, function ($field) {
            return !isset($field['only_fe']) || !$field['only_fe'];
        }));
    }

    /**
     * Remove from a form_json schema fields whose type is in the excluded list.
     *
     * @param array $formSchema
     * @param array $excludedTypes
     * @return array
     */
    public function filterFormSchemaExcludingTypes(array $formSchema, array $excludedTypes = ['password', 'group']): array
    {
        return array_values(array_filter($formSchema, function ($field) use ($excludedTypes) {
            $type = $field['type'] ?? 'text';
            return !in_array($type, $excludedTypes, true);
        }));
    }

    /**
     * Resolve the value of a single form field against form_data, mirroring the lookup
     * used by jsonForm: try by label first, then fall back to name, then to the schema's default.
     *
     * @param array $fieldSchema
     * @param array $formData
     * @return mixed
     */
    public function resolveFormFieldValue(array $fieldSchema, array $formData)
    {
        $label = $fieldSchema['label'] ?? null;
        $name = $fieldSchema['name'] ?? null;

        if ($label !== null && array_key_exists($label, $formData)) {
            return $formData[$label];
        }
        if ($name !== null && array_key_exists($name, $formData)) {
            return $formData[$name];
        }
        return $fieldSchema['value'] ?? null;
    }

    /**
     * Build read-only Nova fields (onlyOnDetail) from a form_json schema and an explicit
     * form_data array. Unlike jsonForm, values are bound via a closure and not tied to a
     * column on the current model, so this can be used from a resource whose underlying
     * model does not own the form_data column (e.g. Ticket -> user.form_data).
     *
     * @param array $formSchema Already-filtered schema (see filterOnlyFeSchema).
     * @param array $formData
     * @return array
     */
    public function jsonFormReadOnlyFields(array $formSchema, array $formData): array
    {
        $this->ensureNovaIsInstalled();

        $fields = [];
        foreach ($formSchema as $fieldSchema) {
            $name = $fieldSchema['name'] ?? null;
            $label = $fieldSchema['label'] ?? ($name ? ucwords(str_replace('_', ' ', $name)) : null);
            if ($label === null) {
                continue;
            }
            $value = $this->resolveFormFieldValue($fieldSchema, $formData);
            $fieldType = $fieldSchema['type'] ?? 'text';

            // Never expose password values in a read-only detail view.
            if ($fieldType === 'password') {
                continue;
            }

            if ($fieldType === 'number') {
                $field = \Laravel\Nova\Fields\Number::make(__($label), $name ?? $label, function () use ($value) {
                    return $value;
                });
            } else {
                $field = \Laravel\Nova\Fields\Text::make(__($label), $name ?? $label, function () use ($value) {
                    return $value;
                });
            }

            $fields[] = $field->onlyOnDetail()->readonly();
        }

        return $fields;
    }

    /**
     * Ensure Laravel Nova is installed in the project.
     *
     * @throws \Exception
     */
    protected function ensureNovaIsInstalled()
    {
        if (!class_exists('Laravel\Nova\Fields\Field')) {
            throw new \Exception('Laravel Nova is not installed. Please install Laravel Nova to use this feature.');
        }
    }
}
