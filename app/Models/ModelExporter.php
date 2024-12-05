<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;


/**
 * Class for exporting Eloquent models to various spreadsheet formats.
 *
 * This class enables easy export of Eloquent model data to Excel, CSV, and other
 * spreadsheet formats, with support for custom columns, relationships, and styling.
 *
 * @implements FromCollection
 * @implements WithHeadings
 * @implements WithStyles
 * @implements WithMapping
 * @implements ShouldAutoSize
 *
 * @example
 * ```php
 * // Using key => value pairs for custom headers
 * $export = new ModelExporter(
 *     User::query(),
 *     ['name' => 'User Name', 'email' => 'Email Address', 'profile.phone' => 'Phone Number'],
 *     ['profile' => 'phone'],
 *     [
 *         1 => ['font' => ['bold' => true]]
 *     ]
 * );
 *
 * // Using array of strings for direct column names as headers
 * $export = new ModelExporter(
 *     User::query(),
 *     ['name', 'email'],
 *     ['profile' => 'phone']
 * );
 * Excel::download($export, 'users.xlsx');
 * ```
 *
 * @link https://docs.laravel-excel.com/ Laravel Excel Documentation
 * @link https://phpspreadsheet.readthedocs.io/en/latest/topics/styling/ PhpSpreadsheet Styling Documentation
 */
class ModelExporter implements FromCollection, WithHeadings, WithStyles, WithMapping, ShouldAutoSize
{
    const DEFAULT_STYLE = [
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
    ];

    /**
     * @var \Illuminate\Database\Eloquent\Builder Query builder for the model
     */
    protected Builder $query;

    /**
     * @var array Columns to export ['column' => 'Header Label']
     */
    protected array $columns;

    /**
     * @var array Relations to include ['relation' => 'attribute']
     */
    protected array $relations;

    /**
     * @var callable Callback for style customization
     */
    protected array $styles;

    /**
     * Creates a new instance of ModelExporter.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Model query builder
     * @param array $columns Columns to export. Can be either:
     *                      - ['column' => 'Header Label'] for custom headers
     *                      - ['column1', 'column2'] to use column names as headers
     * @param array $relations Relations to include ['relation' => 'attribute']
     * @param array $styles custom styling
     */
    public function __construct($query, $columns = [], $relations = [], $styles = self::DEFAULT_STYLE)
    {
        $this->query = $query;
        $this->columns = $columns;
        $this->relations = $relations;
        $this->styles = $styles;
    }

    /**
     * Gets the collection of data to export.
     *
     * If no columns are specified, exports all model fields.
     * Otherwise, exports only the specified columns and requested relations.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        if (empty($this->columns)) {
            $data = $this->query->get();
            return $data;
        }

        $data = $this->query->with(array_keys($this->relations))->get();
        return $data->map(function ($item) {
            $result = [];

            foreach ($this->columns as $key => $value) {
                $column = is_numeric($key) ? $value : $key;
                $result[$column] = data_get($item, $column);
            }

            foreach ($this->relations as $relation => $attribute) {
                $relatedValue = data_get($item, "$relation.$attribute", null);
                $result["$relation.$attribute"] = $relatedValue;
            }

            return $result;
        });
    }

    /**
     * Maps row values before export.
     *
     * Converts boolean values to localized "Yes"/"No".
     *
     * @param mixed $row Row to map
     * @return array
     */
    public function map($row): array
    {
        return collect($row)->map(function ($value) {
            if (is_bool($value)) {
                return $value ? __('Yes') : __('No');
            }
            return $value;
        })->toArray();
    }

    /**
     * Applies styles to the Excel sheet.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return array Array of styles for the sheet
     * @link https://phpspreadsheet.readthedocs.io/en/latest/topics/styling/
     */
    public function styles($sheet): array
    {
        return $this->styles;
    }

    /**
     * Gets the column headers.
     *
     * If no columns are specified, uses the table column names.
     * Otherwise, uses the labels specified in the columns array.
     *
     * @return array
     */
    public function headings(): array
    {
        if ($this->columns === []) {
            $table = $this->query->getModel()->getTable();
            return Schema::getColumnListing($table);
        }

        return collect($this->columns)->values()->map(function ($value) {
            return __($value);
        })->toArray();
    }
}
