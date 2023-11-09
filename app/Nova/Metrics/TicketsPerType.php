<?php

namespace App\Nova\Metrics;

use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Http\Requests\NovaRequest;

class TicketsPerType extends Partition
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {

        if ($request->user()->hasRole('company_admin')) {
            $companyId = $request->user()->admin_company_id;

            $tickets = Ticket::query()
                ->select('ticket_type')
                ->where('company_id', $companyId);
        }

        $types = ['reservation', 'info', 'abandonment', 'report'];

        if (isset($tickets)) {
            //if there are tickets return the partition showing tickets divided by type
            return $this->count($request, $tickets, 'ticket_type')
                ->label(function ($value) use ($types) {
                    switch ($value) {
                        case $types[0]:
                            return 'Prenotazione';
                        case $types[1]:
                            return 'Informazione';
                        case $types[2]:
                            return 'Abbandono';
                        case $types[3]:
                            return 'Segnalazione';
                        default:
                            return $value;
                    }
                });
        }
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     *
     * @return \DateTimeInterface|\DateInterval|float|int|null
     */
    public function cacheFor()
    {
        // return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'tickets-per-type';
    }
}
