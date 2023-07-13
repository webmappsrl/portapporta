<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (is_null($user->zone)) {
            return $this->sendError('User has no zones.');
        }

        if (is_null($user->userType)) {
            return $this->sendError('User has no user types.');
        }

        $company = Company::find($request->id);
        if (count($company->calendars) == 0) {
            return $this->sendError('Company has no calendars.');
        }

        // Find calendar
        $calendar = Calendar::where('zone_id', $user->zone_id)
            ->where('user_type_id', $user->user_type_id)
            ->whereDate('start_date', '<=', Carbon::today())
            ->whereDate('stop_date', '>=', Carbon::today())
            ->first();
        if (is_null($calendar)) {
            return $this->sendError('No calendar matching.');
        }

        // Everything it's fine: build and send output
        // \Carbon\Carbon::parse('today +2 day')->dayOfWeek
        $data = [];
        for ($i = 0; $i < 14; $i++) {
            $date = Carbon::parse("today + $i days");
            if (
                $date <= $calendar->stop_date &&
                in_array($date->dayOfWeek, $calendar->calendarItems->pluck('day_of_week')->toArray())
            ) {
                foreach ($calendar->calendarItems->where('day_of_week', $date->dayOfWeek) as $item) {
                    $p = [];
                    $p['trash_types'] = $item->trashTypes->pluck('id')->toArray();
                    $p['frequency'] = $item->frequency;
                    $p['start_time'] = str_replace('0:00', '0', $item->start_time);
                    $p['stop_time'] = str_replace('0:00', '0', $item->stop_time);
                    if ($item->frequency == 'biweekly') {
                        $p['base_date'] = $item->base_date;
                    }
                    $data[$date->format('Y-m-d')][] = $p;
                }
            }
        }
        return $this->sendResponse($data, 'Calendar created.');
    }
}
