<?php

namespace App\Nova;

use App\Enums\ExportFormat;
use App\Nova\Actions\ExportTo;
use App\Nova\Filters\WasteCollectionCenterFilter;
use App\Nova\Filters\WasteDeliveryFilter;
use App\Nova\Filters\WastePap;
use App\Nova\Filters\WasteTrashTypeFilter;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Query\Search\SearchableRelation;
use Illuminate\Support\Facades\Auth;

class Waste extends Resource
{

    public static function label()
    {
        return __('Wastes');
    }

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Waste::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * Get the searchable columns for the resource.
     *
     * @return array
     */
    public static function searchableColumns()
    {
        return [
            'id',
            'name',
            new SearchableRelation('trashType', 'name'),
        ];
    }
    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        $allTrashTypeId = array();


        $selectedTrashTypeId = $this->model()->trash_type_id;
        return [
            ID::make()->sortable(),
            Boolean::make(__('PAP'), 'pap'),
            Boolean::make(__('Delivery'), 'delivery'), //TODO: Prenotabile ritiro ingombrante da app aggiungere helper
            Boolean::make(__('Collection Center'), 'collection_center'),
            BelongsTo::make(__('Trash Type'), 'trashType', TrashType::class),
            NovaTabTranslatable::make([
                Text::make(__('name'), 'name')->sortable(),
                Textarea::make(__('where'), 'where'),
                Textarea::make(__('notes'), 'notes')
            ]),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [
            new WastePap,
            new WasteDeliveryFilter,
            new WasteCollectionCenterFilter,
            new WasteTrashTypeFilter,
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        $user = Auth::user();
        $waste = Waste::where('company_id', $user->companyWhereAdmin->id)->with('trashType');

        return [
            (new ExportTo($waste, $this->getExportColumns(), $this->getExportRelations(), 'wastes'))
                ->onlyOnIndex()
                ->standalone()
        ];
    }

    /**
     * Get the columns for export
     *
     * @return array
     */
    private function getExportColumns(): array
    {
        return [
            'id' => 'ID',
            'pap' => 'PAP',
            'delivery' => 'Delivery',
            'collection_center' => 'Collection Center',
            'trashType.name' => 'Trash Type',
            'name' => 'Name',
            'where' => 'Where',
            'notes' => 'Notes'
        ];
    }

    /**
     * Get the relations for export
     *
     * @return array
     */
    private function getExportRelations(): array
    {
        return ['trashType' => 'name'];
    }

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->where('company_id', $request->user()->companyWhereAdmin->id);
    }
}
