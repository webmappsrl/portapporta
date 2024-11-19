<?php

namespace App\Nova\Actions;

use App\Models\Waste;
use App\Models\ModelToExcel;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Maatwebsite\Excel\Facades\Excel;

class ExportToExcel extends Action
{
    use InteractsWithQueue, Queueable;

    public function name()
    {
        return __('Export to Excel');
    }

    public function __construct($exportModels, $columns = [], $relations = [], $fileName = 'export.xlsx')
    {
        $this->exportModels = $exportModels;
        $this->columns = $columns;
        $this->relations = $relations;
        $this->fileName = $fileName;
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
        Excel::store(new ModelToExcel($this->exportModels, $this->columns, $this->relations), $this->fileName, 'public');
        $downloadUrl = Storage::url($this->fileName);
        File::delete($downloadUrl);
        return ActionResponse::openInNewTab($downloadUrl);
    }

}
