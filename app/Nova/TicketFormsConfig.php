<?php

namespace App\Nova;

use App\Nova\Concerns\HasTicketFormsConfigFields;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class TicketFormsConfig extends Resource
{
    use HasTicketFormsConfigFields;

    public static $model = \App\Models\Company::class;

    public static $title = 'name';

    public static $search = ['name'];

    public static function label(): string
    {
        return __('Ticket forms config');
    }

    public static function singularLabel(): string
    {
        return __('Ticket forms config');
    }

    public function fields(NovaRequest $request): array
    {
        return [
            ...$this->ticketFormsConfigPanels(),
        ];
    }

    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->where('id', $request->user()->companyWhereAdmin->id);
    }

    public static function availableForNavigation(Request $request): bool
    {
        return $request->user()?->hasRole('company_admin') === true;
    }

    public static function authorizedToCreate(Request $request): bool
    {
        return false;
    }

    public function authorizedToView(Request $request): bool
    {
        return (int) $request->user()?->companyWhereAdmin?->id === (int) $this->resource->id;
    }

    public function authorizedToUpdate(Request $request): bool
    {
        return (int) $request->user()?->companyWhereAdmin?->id === (int) $this->resource->id;
    }

    public function authorizedToDelete(Request $request): bool
    {
        return false;
    }

    public function cards(NovaRequest $request): array
    {
        return [];
    }

    public function filters(NovaRequest $request): array
    {
        return [];
    }

    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    public function actions(NovaRequest $request): array
    {
        return [];
    }
}
