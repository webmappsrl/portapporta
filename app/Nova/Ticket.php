<?php

namespace App\Nova;

use App\Models\TrashType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Query\Search\SearchableRelation;
use Wm\MapPoint\MapPoint;

class Ticket extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Ticket::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * Get the searchable columns for the resource.
     *
     * @return array
     */
    public static function searchableColumns()
    {
        return [
            'ticket_type',
            new SearchableRelation('trashType', 'name'),
            new SearchableRelation('user', 'name'),
            new SearchableRelation('user', 'email')
        ];
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

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        $fields = [];
        $this->_headerFields($fields);
        switch ($this->ticket_type) {
            case 'report':
                $this->_reportFields($fields);
                break;
            case 'reservation':
                $this->_reservationFields($fields);
                break;
            case 'abandonment':
                $this->_abandonmentFields($fields);
                break;
            case 'info':
                $this->_infoFields($fields);
                break;
        }
        return $fields;
    }

    private function _headerFields(&$fields)
    {
        // $fields[] = ID::make()->sortable();
        $fields[] = Text::make('Ticket Type', 'ticket_type')->sortable();
        $fields[] = DateTime::make(__('Created At'), 'created_at')->sortable();
        $fields[] = BelongsTo::make('User');
        $fields[] = Text::make('User Email', function () {
            return $this->user->email;
        })->onlyOnDetail();
    }

    private function _reportFields(&$fields)
    {
        $fields[] = Text::make('Report date', 'missed_withdraw_date')->onlyOnDetail();
        $fields[] = Text::make('Trash Type', 'trash_type', function () { // TODO: use belongsTo
            $trashType = TrashType::find($this->trash_type_id);
            return $trashType->name;
        })->onlyOnDetail();
        $fields[] = Text::make('zone', function () {
            return $this->address->zone->label;
        })->onlyOnDetail();
        $fields[] = Text::make('user address', function () {
            return $this->address->address;
        })->onlyOnDetail();
        $fields[] = MapPoint::make('Location', 'location', function () {
            return $this->address->location;
        })->withMeta([
            'defaultZoom' => 13
        ])->onlyOnDetail();
        $fields[] = Textarea::make('Note', 'note')->alwaysShow()->onlyOnDetail();
        $fields[] = Text::make('Image', 'image', function () {
            return '<img src="' . $this->image . '" />';
        })->asHtml()->onlyOnDetail();
        return $fields;
    }

    private function _reservationFields(&$fields)
    {
        $fields[] = Text::make('Trash Type', 'trash_type', function () {
            $trashType = TrashType::find($this->trash_type_id);
            return $trashType->name;
        })->onlyOnDetail();
        $fields[] = Text::make('address', function () {
            return $this->location_address;
        })->onlyOnDetail();
        $fields[] = MapPoint::make('Location', 'location', function () {
            return $this->geometry;
        })->withMeta([
            'defaultZoom' => 13
        ])->onlyOnDetail();
        $fields[] = Textarea::make('Note', 'note')->alwaysShow()->onlyOnDetail();
        $fields[] = Text::make('Image', 'image', function () {
            return '<img src="' . $this->image . '" />';
        })->asHtml()->onlyOnDetail();
    }

    private function _abandonmentFields(&$fields)
    {
        $fields[] = Text::make('address', function () {
            return $this->location_address;
        })->onlyOnDetail();
        $fields[] = MapPoint::make('Location', 'location', function () {
            return $this->geometry;
        })->withMeta([
            'defaultZoom' => 13
        ])->onlyOnDetail();
        $fields[] = Textarea::make('Note', 'note')->alwaysShow()->onlyOnDetail();
        $fields[] = Text::make('Image', 'image', function () {
            return '<img src="' . $this->image . '" />';
        })->asHtml()->onlyOnDetail();
    }

    private function _infoFields(&$fields)
    {
        $fields[] = Textarea::make('Note', 'note')->alwaysShow()->onlyOnDetail();
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
            new \App\Nova\Filters\TicketZoneFilter(),
            new \App\Nova\Filters\TicketTypeFilter(),
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
        return [];
    }
}
