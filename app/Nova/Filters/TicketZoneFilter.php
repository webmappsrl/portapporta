<?php

namespace App\Nova\Filters;

use App\Models\Zone;
use App\Models\Address;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class TicketZoneFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    public function name()
    {
        return __('by ticket zone');
    }

    /**
     * Apply the filter to the given query.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        $zone = Zone::where('label', $value)->first();
        $addresses = Address::where('zone_id', $zone->id)->get();
        if (count($addresses) > 0) {
            foreach ($addresses as $address) {
                $addressIds[] = $address->id;
            }

            return $query->whereIn('address_id', $addressIds);
        }
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        $zones = Zone::where('company_id', $request->user()->companyWhereAdmin->id)->get();

        $options = [];
        foreach ($zones as $zone) {
            if (count($zone->addresses) < 1)
                continue;
            $options[$zone->id] = $zone->label;
        }
        $collect = collect($options)->sort();
        $options = $collect->toArray();

        return $options;
    }
}
