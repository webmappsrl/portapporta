<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

abstract class ExportableModel extends Model
{
    use Exportable;

    public function exportToExcel(
        string $filename = 'export.xlsx',
        array $columns = [],
        array $headerLabels = [],
        callable $queryCallback = null
    )
    {
        $query = $this->newQuery();

        if ($queryCallback) {
            $query = $queryCallback($query);
        }

        // Debug: Mostra i dati che la query sta restituendo
        $q = $query->get()->toArray();
        Log::info('count query: ' . count($q));

        $export = new class($query, $columns, $headerLabels) implements FromQuery, WithMapping, WithHeadings, WithStyles, ShouldAutoSize {
            protected $query;
            protected $columns;
            protected $headerLabels;

            public function __construct($query, array $columns, array $headerLabels = [])
            {
                $this->query = $query;
                $this->columns = $columns;
                $this->headerLabels = $headerLabels ?: $columns; // usa etichette personalizzate se fornite, altrimenti usa i nomi delle colonne
            }

            public function query()
            {
                return $this->query;
            }

            public function headings(): array
            {
                return $this->headerLabels;
            }

            public function map($row): array
            {
                return collect($this->columns)->map(function($column) use ($row) {
                    if (str_contains($column, '.')) {
                        [$relation, $attribute] = explode('.', $column);
                        return $row->{$relation}->{$attribute} ?? 'N/A';
                    }

                    return $row->{$column} ?? 'N/A';
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
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER
                        ]
                    ]
                ];
            }

            /* public function columnWidths(): array
            {
                $widths = [];
                foreach ($this->columns as $index => $column) {
                    $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
                    $widths[$columnLetter] = 30;
                }
                return $widths;
            } */
        };

        return Excel::store($export, $filename, 'public');
    }
}
