<?php

namespace App\Nova\Filters;

use App\Models\Zone;
use App\Models\Address;
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
        $address = Address::where('zone_id', $zone->id)->first();
        if ($address) {
            return $query->where('address_id', $address->id);
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
