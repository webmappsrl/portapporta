<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\TicketsPerType;
use App\Nova\Metrics\NewUsersValueMetric;
use App\Nova\Metrics\NewTicketsValueMetric;
use Laravel\Nova\Dashboards\Main as Dashboard;

class Main extends Dashboard
{

    public function name()
    {
        return 'PortAPPorta';
    }

    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        if (auth()->user()->hasRole('company_admin')) {
            return [
                new NewUsersValueMetric,
                new NewTicketsValueMetric,
                new TicketsPerType,
            ];
        }
        return [
            new NewUsersValueMetric,
            new NewTicketsValueMetric,
        ];
    }
}
