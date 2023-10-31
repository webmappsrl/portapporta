<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Calendar;
use App\Models\Company;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Zone;
use App\Models\UserType;

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
        $data = [];

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
        $start_date = Carbon::today();
        if ($request->start_date) {
            $start_date = Carbon::parse($request->start_date);
        }
        Log::info('start_date: ' . $start_date->format('d/m/Y'));
        $stop_date = Carbon::today()->addDays(13);
        if ($request->stop_date) {
            $stop_date = Carbon::parse($request->stop_date);
        }
        Log::info('stop_date: ' . $stop_date->format('d/m/Y'));
        // ritorna i calendari compresi dallo start allo stop ordinati temporalmente
        $calendars = Calendar::where('zone_id', $user->zone_id)
            ->where('user_type_id', $user->user_type_id)
            ->whereDate('start_date', '<=', $start_date)
            ->whereDate('stop_date', '>=', $stop_date)
            ->get();
        if (is_null($calendars) || count($calendars) === 0) {
            return $this->sendError('No calendars matching.');
        }
        $diff_in_date_from_start_stop = CarbonPeriod::create($start_date, $stop_date)->toArray(); // tutti i giorni dallo start allo stop
        Log::info('numero giorni da esaminare: ' . count($diff_in_date_from_start_stop));
        $calendarIndex = 0;
        Log::info('calendario "' . $calendars[$calendarIndex]->name . '" start: ' . $calendars[$calendarIndex]->start_date->format('d/m/Y') . ' stop: ' . $calendars[$calendarIndex]->stop_date->format('d/m/Y'));
        foreach ($diff_in_date_from_start_stop as $currentDay) {
            $currentCalendar = $calendars[$calendarIndex];

            if ($currentDay >= $currentCalendar->start_date && $currentDay <= $currentCalendar->stop_date) { // se il giono è dopo lo start del calendario corrente
                if (
                    in_array($currentDay->dayOfWeek, $currentCalendar->calendarItems->pluck('day_of_week')->toArray())
                ) {
                    Log::info('costruisco giorno di calendario per ' . $currentDay->format('d/m/Y'));
                    foreach ($currentCalendar->calendarItems->where('day_of_week', $currentDay->dayOfWeek) as $item) {
                        $p = [];
                        $p['trash_types'] = $item->trashTypes->pluck('id')->toArray();
                        $p['frequency'] = $item->frequency;
                        $p['start_time'] = str_replace('0:00', '0', $item->start_time);
                        $p['stop_time'] = str_replace('0:00', '0', $item->stop_time);
                        if ($item->frequency == 'biweekly') {
                            $p['base_date'] = $item->base_date;
                        }
                        $data[$currentDay->format('Y-m-d')][] = $p;
                    }
                } else {
                    Log::info('giorno di calendario skippato perche non è presente nessun ritiro ' . $currentDay->format('d/m/Y'));
                }
            }
            if ($currentDay > $currentCalendar->stop_date && count($calendars) - 1 > $calendarIndex) {
                $calendarIndex++;
                Log::info('passaggio a calendario successivo "' . $calendars[$calendarIndex]->name . '" start: ' . $calendars[$calendarIndex]->start_date->format('d/m/Y') . ' stop: ' . $calendars[$calendarIndex]->stop_date->format('d/m/Y'));
            }
        }

        // Everything it's fine: build and send output
        // \Carbon\Carbon::parse('today +2 day')->dayOfWeek

        Log::info('numero giorni di calendario creati: ' . count($data));
        return $this->sendResponse($data, 'Calendar created.');
    }

    public function v1index(Request $request)
    {
        $user = Auth::user();

        $company = Company::find($request->id);
        if (count($company->calendars) == 0) {
            return $this->sendError('Company has no calendars.');
        }
        $start_date = Carbon::today();
        if ($request->start_date) {
            $start_date = Carbon::parse($request->start_date);
        }
        Log::info('start_date: ' . $start_date->format('d/m/Y'));
        $stop_date = Carbon::today()->addDays(13);
        if ($request->stop_date) {
            $stop_date = Carbon::parse($request->stop_date);
        }
        Log::info('stop_date: ' . $stop_date->format('d/m/Y'));
        $addresses = Address::where('user_id', $user->id)->get();
        $res = [];
        foreach ($addresses as $address) {
            $data = [];

            // ritorna i calendari compresi dallo start allo stop ordinati temporalmente
            $calendars = Calendar::where('zone_id', $address->zone_id)
                ->where('user_type_id', $address->user_type_id)
                ->whereDate('start_date', '<=', $start_date)
                ->whereDate('stop_date', '>=', $stop_date)
                ->get();
            if (is_null($calendars) || count($calendars) === 0) {
                break;
            }
            $diff_in_date_from_start_stop = CarbonPeriod::create($start_date, $stop_date)->toArray(); // tutti i giorni dallo start allo stop
            Log::info('numero giorni da esaminare: ' . count($diff_in_date_from_start_stop));
            $calendarIndex = 0;
            Log::info('calendario "' . $calendars[$calendarIndex]->name . '" start: ' . $calendars[$calendarIndex]->start_date->format('d/m/Y') . ' stop: ' . $calendars[$calendarIndex]->stop_date->format('d/m/Y'));
            foreach ($diff_in_date_from_start_stop as $currentDay) {
                $currentCalendar = $calendars[$calendarIndex];

                if ($currentDay >= $currentCalendar->start_date && $currentDay <= $currentCalendar->stop_date) { // se il giono è dopo lo start del calendario corrente
                    if (
                        in_array($currentDay->dayOfWeek, $currentCalendar->calendarItems->pluck('day_of_week')->toArray())
                    ) {
                        Log::info('costruisco giorno di calendario per ' . $currentDay->format('d/m/Y'));
                        foreach ($currentCalendar->calendarItems->where('day_of_week', $currentDay->dayOfWeek) as $item) {
                            $p = [];
                            $p['trash_types'] = collect($item->trashTypes->toArray())->map(function ($trashType) {
                                $trashType["allowed"] = $trashType["allowed"]["it"];
                                $trashType["notallowed"] = $trashType["notallowed"]["it"];
                                return $trashType;
                            });
                            $p['frequency'] = $item->frequency;
                            $p['start_time'] = str_replace('0:00', '0', $item->start_time);
                            $p['stop_time'] = str_replace('0:00', '0', $item->stop_time);
                            if ($item->frequency == 'biweekly') {
                                $p['base_date'] = $item->base_date;
                            }
                            $data[$currentDay->format('Y-m-d')][] = $p;
                        }
                    } else {
                        Log::info('giorno di calendario skippato perche non è presente nessun ritiro ' . $currentDay->format('d/m/Y'));
                    }
                }
                if ($currentDay > $currentCalendar->stop_date && count($calendars) - 1 > $calendarIndex) {
                    $calendarIndex++;
                    Log::info('passaggio a calendario successivo "' . $calendars[$calendarIndex]->name . '" start: ' . $calendars[$calendarIndex]->start_date->format('d/m/Y') . ' stop: ' . $calendars[$calendarIndex]->stop_date->format('d/m/Y'));
                }
            }
            $elem['address'] = $address;
            $elem['address']['zone'] = Zone::find($address['zone_id']);
            $elem['address']['user_type'] = UserType::find($address['user_type_id']);
            $elem['calendar'] = $data;

            array_push($res, $elem);
        }

        return $this->sendResponse($res, 'Calendar created.');
    }
}
