<?php

namespace App\Nova\Actions;

use App\Enums\ExportFormat;
use App\Models\ModelExporter;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;
use Maatwebsite\Excel\Facades\Excel;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * Nova Action to export models to Excel/CSV formats.
 *
 * This action allows exporting a collection of models to various formats
 * defined in the ExportFormat enum (e.g., XLSX, CSV) using the
 * Maatwebsite/Excel library.
 *
 * @package App\Nova\Actions
 *
 * @property array $exportModels    Models to be exported
 * @property array $columns         Columns to include in the export
 * @property array $relations       Relations to load for the export
 * @property string $fileName       Export file name (without extension)
 * @property array $styleCallback  Callback to customize export styling
 * @property string $defaultFormat  Default export format (@see ExportFormat)
 * @see \App\Enums\ExportFormat
 */
class ExportTo extends Action
{
    use InteractsWithQueue, Queueable;

    public function name()
    {
        return __('Export to');
    }

    public function __construct(
        $exportModels,
        $columns = [],
        $relations = [],
        $fileName = 'export',
        $styles = null,
        $defaultFormat = ExportFormat::XLSX->value
    ){
        $this->exportModels = $exportModels;
        $this->columns = $columns;
        $this->relations = $relations;
        $this->fileName = $fileName;
        $this->styles = $styles;
        $this->defaultFormat = $defaultFormat;
    }

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $format = isset($fields->format) ? $fields->format : $this->defaultFormat;
        $fileName = $this->fileName . '.' . ExportFormat::from($format)->extension();
        Excel::store(
            new ModelExporter($this->exportModels, $this->columns, $this->relations, $this->styles),
            $fileName,
            'public',
            $format,
        );
        $downloadUrl = Storage::url($fileName);
        File::delete($downloadUrl);
        return ActionResponse::openInNewTab($downloadUrl);
    }

    public function fields(NovaRequest $request)
    {
        return [
            Select::make(__('Formato'), 'format')
                ->options(ExportFormat::toArray())
                ->default([$this->defaultFormat])
                ->placeholder(__("Seleziona il formato dell'esportazione"))
                ->help(__("Seleziona il formato dell'esportazione"))
        ];
    }

}
