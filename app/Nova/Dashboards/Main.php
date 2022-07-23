<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\NewTicketsValueMetric;
use App\Nova\Metrics\NewUsersValueMetric;
use Laravel\Nova\Dashboards\Main as Dashboard;

class Main extends Dashboard
{

    public function name() {
        return 'PortAPPorta';
    }

    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        return [
            new NewUsersValueMetric,
            new NewTicketsValueMetric,
        ];
    }
}
