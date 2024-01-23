<?php

namespace App\Nova;

use App\Models\TrashType;
use Wm\MapPoint\MapPoint;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Textarea;
use Illuminate\Support\Facades\DB;
use App\Nova\Actions\TicketMarkNotRead;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Nova\Actions\TicketMarkAsReadAction;
use Laravel\Nova\Query\Search\SearchableRelation;

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
    public static function label()
    {
        return __('Ticket');
    }
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
        $fields[] = Text::make(__('Ticket Type'), 'ticket_type', function ($res) {
            // Controlla se esiste una traduzione per il valore di $res
            $translated = __($this->ticket_type);
            // Se la traduzione è diversa dalla chiave originale, usa quella tradotta
            return $translated;
        })->sortable()->readonly();
        $fields[] = DateTime::make(__('Created At'), 'created_at')->sortable()->readonly();
        $fields[] = Text::make('Ticket ID', 'id')->readonly();
        $fields[] = Text::make(__('Name'), function () {
            return $this->checkName($this->user->name);
        })->readonly()->onlyOnDetail();
        $fields[] = Text::make('Email', function () {
            return $this->user->email;
        })->readonly();
        $fields[] = Text::make(__('Phone'), function () {
            return $this->phone;
        })->onlyOnDetail()->readonly();
        $fields[] = Boolean::make(__('Read'), 'is_read')->sortable()->filterable()->onlyOnIndex();
        if (isset($this->address)) {
            $fields[] = Text::make(__('Zone'), function () {
                return $this->address->zone->label;
            })->onlyOnDetail()->readonly();
            $fields[] = Text::make(__('Address'), function () {
                return $this->address->address;
            })->onlyOnDetail()->readonly();
            $fields[] = Text::make(__('House Number'), function () {
                return $this->address->house_number;
            })->onlyOnDetail()->readonly();
            $fields[] = Text::make(__('Coordinate'), function () {
                $loc = $this->address->location;
                $g = json_decode(DB::select("SELECT st_asgeojson('$loc') as g")[0]->g);
                $x = $g->coordinates[0];
                $y = $g->coordinates[1];
                return "lat:$y lon:$x";
            })->onlyOnDetail()->readonly();
            $fields[] = MapPoint::make(__('Location'), 'location', function () {
                return $this->address->location;
            })->withMeta([
                'defaultZoom' => 13
            ])->onlyOnDetail()->readonly();
        } else {
            if (isset($this->location_address) && !empty($this->location_address)) {
                $fields[] = Text::make(__('Address'), function () {
                    return $this->location_address;
                })->onlyOnDetail()->readonly();
            }
            if (isset($this->geometry)) {
                $fields[] = Text::make(__('Coordinate'), function () {
                    $loc = $this->geometry;
                    $g = json_decode(DB::select("SELECT st_asgeojson('$loc') as g")[0]->g);
                    $x = $g->coordinates[0];
                    $y = $g->coordinates[1];
                    return "lat:$y lon:$x";
                })->onlyOnDetail()->readonly();
                $fields[] = MapPoint::make(__('Location'), 'location', function () {
                    return $this->geometry;
                })->withMeta([
                    'defaultZoom' => 13
                ])->onlyOnDetail()->readonly();
            }
        }
    }

    private function _reportFields(&$fields)
    {
        $fields[] = Text::make(__('Report date'), 'missed_withdraw_date')->onlyOnDetail()->readonly();
        $fields[] = Text::make(__('Trash Type'), 'trash_type', function () { // TODO: use belongsTo
            $trashType = TrashType::find($this->trash_type_id);
            return $trashType->name;
        })->onlyOnDetail()->readonly();
        $fields[] = Textarea::make(__('Note'), 'note')->alwaysShow()->onlyOnDetail();
        $fields[] = Text::make(__('Image'), 'image', function () {
            return '<img src="' . $this->image . '" />';
        })->asHtml()->onlyOnDetail()->readonly();
        return $fields;
    }

    private function _reservationFields(&$fields)
    {
        $fields[] = Text::make(__('Trash Type'), 'trash_type', function () {
            $trashType = TrashType::find($this->trash_type_id);
            return $trashType->name;
        })->onlyOnDetail()->readonly();
        $fields[] = Textarea::make(__('Note'), 'note')->alwaysShow()->onlyOnDetail();
        $fields[] = Text::make(__('Image'), 'image', function () {
            return '<img src="' . $this->image . '" />';
        })->asHtml()->onlyOnDetail()->readonly();
    }

    private function _abandonmentFields(&$fields)
    {
        $fields[] = Textarea::make(__('Note'), 'note')->alwaysShow()->onlyOnDetail();
        $fields[] = Text::make(__('Image'), 'image', function () {
            return '<img src="' . $this->image . '" />';
        })->asHtml()->onlyOnDetail()->readonly();
    }

    private function _infoFields(&$fields)
    {
        $fields[] = Textarea::make(__('Note'), 'note')->alwaysShow()->onlyOnDetail()->readonly();
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
        return [
            (new TicketMarkAsReadAction())
                ->confirmText('Are you sure you want to mark this ticket as read?')
                ->confirmButtonText('Mark as read')
                ->cancelButtonText("Don't mark as read")
                ->showInline()
                ->canSee(function ($request) {
                    return $request->user()->hasRole('company_admin');
                }),
            (new TicketMarkNotRead())
                ->confirmText('Are you sure you want to mark this ticket as not read?')
                ->confirmButtonText('Mark as not read')
                ->cancelButtonText("Don't mark as not read")
                ->showInline()
                ->canSee(function ($request) {
                    return $request->user()->hasRole('company_admin');
                }),
            (new \App\Nova\Actions\TicketAnswerViaMail())
                ->confirmText('Are you sure you want to send this answer to the user?')
                ->confirmButtonText('Send')
                ->cancelButtonText("Don't send")
                ->showInline()
                ->canSee(function ($request) {
                    return $request->user()->hasRole('company_admin');
                }),

        ];
    }


    private function checkName($string)
    {
        if (filter_var($string, FILTER_VALIDATE_EMAIL)) {
            // Se la stringa è un indirizzo email valido, restituisci una stringa vuota
            return '';
        } else {
            // Altrimenti, restituisci la stringa originale
            return $string;
        }
    }
}
