<?php

namespace App\Enums;

use Maatwebsite\Excel\Excel;

enum ExportFormat: string
{
    case XLSX = Excel::XLSX;
    case CSV = Excel::CSV;
    case ODS = Excel::ODS;
    case XLS = Excel::XLS;
    case HTML = Excel::HTML;
    case DOMPDF = Excel::DOMPDF;

    public function label(): string
    {
        return match ($this) {
            self::XLSX => 'Excel (XLSX)',
            self::CSV => 'CSV',
            self::ODS => 'ODS',
            self::XLS => 'Excel 97-2003 (XLS)',
            self::HTML => 'HTML Document',
            self::DOMPDF => 'PDF (Dompdf)',
        };
    }

    public function extension(): string
    {
        return match ($this) {
            self::XLSX => 'xlsx',
            self::CSV => 'csv',
            self::ODS => 'ods',
            self::XLS => 'xls',
            self::HTML => 'html',
            self::DOMPDF => 'pdf',
        };
    }

    public static function toArray(): array
    {
        return array_reduce(self::cases(), function ($carry, ExportFormat $format) {
            $carry[$format->value] = $format->label();
            return $carry;
        }, []);
    }
}
