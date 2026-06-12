<?php

namespace App\Nova;

use App\Enums\TicketStatus;
use App\Models\TrashType;
use App\Models\Ticket as TicketModel;
use App\Nova\Actions\TicketAnswerViaMail;
use App\Nova\Actions\TicketStatusAction;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Query\Search\SearchableRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\Attachments\PendingAttachment;
use Wm\MapPoint\MapPoint;

class Ticket extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = TicketModel::class;

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
        return $query->where('company_id', $request->user()->companyWhereAdmin->id)
                     ->with(['user']);
    }

    /**
     * Eager load the user (and its company via app_company_id) to avoid N+1 when
     * rendering the dynamic form_data section in the detail view.
     */
    public static function detailQuery(NovaRequest $request, $query)
    {
        return parent::detailQuery($request, $query)->with(['user', 'company', 'address.zone', 'zone']);
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

        // Campo nascosto richiesto da FieldAttachmentController per l'upload Trix nell'azione TicketAnswerViaMail
        $fields[] = Trix::make('Answer', 'answer')
            ->withFiles('public')
            ->attach(function (Request $request) {
                $file = $request->file('attachment');

                $request->validate(['attachment' => ['required', 'file']]);

                $disk = 'public';
                $path = $file->store('/', $disk);
                $url  = '/storage/' . ltrim($path, '/');

                PendingAttachment::create([
                    'draft_id'   => $request->draftId,
                    'attachment' => $path,
                    'disk'       => $disk,
                ]);

                return $url;
            })
            ->hideFromIndex()
            ->hideFromDetail()
            ->hideWhenCreating()
            ->hideWhenUpdating()
            ->fillUsing(fn () => null);

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
        $fields[] = Text::make('Ticket ID', 'id')->readonly();
        $fields[] = Text::make(__('Ticket Type'), 'ticket_type', function ($res) {
            // Controlla se esiste una traduzione per il val{ore di $res
            $vip = '';
            if ($this->user->hasRole('vip')) {
                $vip = '[VIP]';
                $translated =  __($this->ticket_type);
                return  <<<HTML
                <span style="color:red">$vip</span><span>$translated</span>
                HTML;
            } else {
                return __($this->ticket_type);
            }
        })->sortable()->readonly()->asHtml();
        $fields[] = DateTime::make(__('Created At'), 'created_at')->sortable()->readonly();
        if ($this->user) {
            $fields[] = Text::make(__('Name'), function () {
                return $this->checkName($this->user->name);
            })->readonly()->onlyOnDetail();
            $fields[] = BelongsTo::make('User')->readonly();
            $fields[] = Text::make('Email', function () {
                return $this->user->email;
            })->readonly();
            $fields[] = Text::make(__('Phone'), function () {
                return $this->phone;
            })->onlyOnDetail()->readonly();
        }
        $this->_userFormDataFields($fields);
        $fields[] =  Text::make(__('Status'), 'status', function ($res) {
            $translated = __($this->status->value);
            $statusColor = 'orange';
            switch ($this->status) {
                case TicketStatus::New:
                    $statusColor = 'red';
                    break;
                case TicketStatus::Readed:
                    $statusColor = 'orange';
                    break;
                case TicketStatus::Execute:
                    $statusColor = 'yellowgreen';
                    break;
                case TicketStatus::Deleted:
                    $statusColor = 'dodgerblue';
                    break;
                case TicketStatus::Done:
                    $statusColor = 'green';
                    break;
            }
            return  <<<HTML
            <span style="font-weight:bold; padding:1px 4px; background-color:{$statusColor}; color:white; border-radius:5px;">
                $translated
            </span>
            HTML;
        })->readonly()->asHtml();
        // $fields[] = Boolean::make(__('Read'), 'is_read')->sortable()->filterable()->onlyOnIndex();
        $zone = null;
        if (isset($this->address) && isset($this->address->zone)) {
            $zone = $this->address->zone;
        } elseif (isset($this->zone)) {
            $zone = $this->zone;
        }
        if ($zone) {
            $fields[] = Text::make(__('Zone'), function () use ($zone) {
                return $zone->label;
            })->onlyOnDetail()->readonly();
            $fields[] = Text::make(__('Comune'), function () use ($zone) {
                return $zone->comune;
            })->onlyOnDetail()->readonly();
        }
        if (isset($this->address)) {
            $fields[] = Text::make(__('Address'), function () {
                return $this->address->address;
            })->onlyOnDetail()->readonly();
            $fields[] = Text::make(__('House Number'), function () {
                return $this->address->house_number;
            })->onlyOnDetail()->readonly();
            $fields[] = URL::make(__('Coordinate'),  function () {
                $loc = $this->address->location;
                $g = json_decode(DB::select("SELECT st_asgeojson('$loc') as g")[0]->g);
                $x = $g->coordinates[0];
                $y = $g->coordinates[1];
                return "https://www.openstreetmap.org/?mlat=$y&mlon=$x#map=15/$y/$x";
            })->displayUsing(function () {
                $loc = $this->address->location;
                $g = json_decode(DB::select("SELECT st_asgeojson('$loc') as g")[0]->g);
                $y = $g->coordinates[1];
                $x = $g->coordinates[0];
                return "lat:$y lon:$x";
            })->onlyOnDetail();
            $fields[] = MapPoint::make(__('Location'), 'location', function () {
                return $this->address->location;
            })->withMeta([
                'defaultZoom' => 13
            ])->onlyOnDetail()->readonly();
        } else {
            if (!empty($this->location_address)) {
                $locationParts = explode(' — ', $this->location_address, 2);
                $addressPart   = $locationParts[0];
                $cityFallback  = $locationParts[1] ?? '';

                $parts  = explode(', ', $addressPart, 2);
                $via    = $parts[0] ?? '';
                $civico = $parts[1] ?? '';

                if (!empty($cityFallback)) {
                    $fields[] = Text::make(__('Comune'), function () use ($cityFallback) {
                        return $cityFallback;
                    })->onlyOnDetail()->readonly();
                }
                if (!empty($via)) {
                    $fields[] = Text::make(__('Address'), function () use ($via) {
                        return $via;
                    })->onlyOnDetail()->readonly();
                }
                if (!empty($civico)) {
                    $fields[] = Text::make(__('House Number'), function () use ($civico) {
                        return $civico;
                    })->onlyOnDetail()->readonly();
                }
            }
            if (isset($this->geometry)) {
                $fields[] = URL::make(__('Coordinate'),  function () {
                    $loc = $this->geometry;
                    $g = json_decode(DB::select("SELECT st_asgeojson('$loc') as g")[0]->g);
                    $x = $g->coordinates[0];
                    $y = $g->coordinates[1];
                    return "https://www.openstreetmap.org/?mlat=$y&mlon=$x#map=15/$y/$x";
                })->displayUsing(function () {
                    $loc = $this->geometry;
                    $g = json_decode(DB::select("SELECT st_asgeojson('$loc') as g")[0]->g);
                    $x = $g->coordinates[0];
                    $y = $g->coordinates[1];
                    return "lat:$y lon:$x";
                })->onlyOnDetail();
                $fields[] = MapPoint::make(__('Location'), 'location', function () {
                    return $this->geometry;
                })->withMeta([
                    'defaultZoom' => 13
                ])->onlyOnDetail()->readonly();
            }
        }
    }

    /**
     * Append read-only Nova fields built from the user's form_data using the
     * company's form_json schema (excluding password and group field types).
     */
    private function _userFormDataFields(&$fields)
    {
        $user = $this->user;
        if (!$user) {
            return;
        }
        $company = $this->company ?? \App\Models\Company::find($user->app_company_id);
        if (!$company || empty($company->form_json)) {
            return;
        }
        $schema = json_decode($company->form_json, true) ?? [];
        if (empty($schema)) {
            return;
        }
        $filtered = $user->filterFormSchemaExcludingTypes($schema, ['password', 'group']);
        $formData = $user->form_data ?? [];
        foreach ($user->jsonFormReadOnlyFields($filtered, $formData) as $field) {
            $fields[] = $field;
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
            if (isset($trashType)) {
                return $trashType->name;
            } else {
                return 'VIP';
            }
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
            (new TicketStatusAction(TicketStatus::New))
                ->confirmText('Are you sure you want to mark this ticket as new?')
                ->confirmButtonText('Mark as new')
                ->cancelButtonText("Don't mark as new")
                ->showInline()
                ->canSee(function ($request) {
                    return $request->user()->hasRole('company_admin');
                })
                ->canRun(function ($request, $model) {
                    return optional($model->user)->hasRole('vip');
                }),
            (new TicketStatusAction(TicketStatus::Readed))
                ->confirmText('Are you sure you want to mark this ticket as read?')
                ->confirmButtonText('Mark as read')
                ->cancelButtonText("Don't mark as read")
                ->showInline()
                ->canSee(function ($request) {
                    return $request->user()->hasRole('company_admin');
                })
                ->canRun(function ($request, $model) {
                    return true;
                }),
            (new TicketStatusAction(TicketStatus::Execute))
                ->confirmText('Are you sure you want to mark this ticket as execute?')
                ->confirmButtonText('Mark as execute')
                ->cancelButtonText("Don't mark as execute")
                ->showInline()
                ->canSee(function ($request) {
                    return $request->user()->hasRole('company_admin');
                })
                ->canRun(function ($request, $model) {
                    return optional($model->user)->hasRole('vip');
                }),
            (new TicketAnswerViaMail())
                ->confirmText('Are you sure you want to send this answer to the user?')
                ->confirmButtonText('Send')
                ->cancelButtonText("Don't send")
                ->showInline()
                ->canSee(function ($request) {
                    return $request->user()->hasRole('company_admin');
                })
                ->canRun(function ($request) {
                    return $request->user()->hasRole('company_admin');
                }),

        ];
    }


    private function checkName($string): string
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
