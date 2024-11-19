<?php

namespace App\Models;

use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ModelToExcel implements FromCollection, WithHeadings, WithStyles, WithMapping, ShouldAutoSize
{
    protected $query;
    protected $columns;
    protected $relations;
    public function __construct($query, $columns = [], $relations = [])
    {
        $this->query = $query;
        $this->columns = $columns;
        $this->relations = $relations;
    }

    /**
     * Ottieni i dati da esportare.
     */
    public function collection()
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
                $result[$column] = $item[$column];
            }

            foreach ($this->relations as $relation => $attribute) {
                $relatedValue = data_get($item, "$relation.$attribute", null);
                $result["$relation.$attribute"] = $relatedValue;
            }

            return $result;
        });
    }

    public function map($row): array
    {
        return collect($row)->map(function ($value) {
            if (is_bool($value)) {
                return $value ? 'Si' : 'No';
            }
            return $value;
        })->toArray();
    }

    public function styles($sheet)
    {
        return [
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
    }

    /**
     * Ottieni le intestazioni delle colonne.
     */
    public function headings(): array
    {
        if ($this->columns === []) {
            $table = $this->query->getModel()->getTable();
            return Schema::getColumnListing($table);
        }

        return collect($this->columns)->map(function ($value, $key) {
            return is_numeric($key) ? $value : $value;
        })->toArray();
    }
}
