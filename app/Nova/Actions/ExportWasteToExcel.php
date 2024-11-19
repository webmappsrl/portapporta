<?php

namespace App\Nova\Actions;

use App\Models\Waste;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Facades\Excel;

class ExportWasteToExcel extends Action
{
    use InteractsWithQueue, Queueable;

    public function name()
    {
        return __('Export to Excel');
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
        $fileName = 'wastes.xlsx';

        $user = Auth::user();
        $wastes = new Waste();

        $wastes->exportToExcel(
            $fileName,
            [
                'id',
                'pap',
                'delivery',
                'collection_center',
                'trashType.name',
                'name',
                'where',
                'notes',
            ],
            [
                'ID',
                __('PAP'),
                __('Consegna'),
                __('Raccolta'),
                __('Tipo di rifiuto'),
                __('Nome'),
                __('Indirizzo'),
                __('Note'),
            ],
            function() use ($user) {
                return Waste::where('company_id', $user->companyWhereAdmin->id)->with('trashType');
            }
        );

        $downloadUrl = Storage::url($fileName);
        File::delete($downloadUrl);

        return ActionResponse::openInNewTab($downloadUrl);
    }

}
