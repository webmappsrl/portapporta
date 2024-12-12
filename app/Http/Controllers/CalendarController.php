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
    protected $logger;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $data = [];
        $this->logger = Log::channel('calendars');

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
        $this->logger->info('start_date: ' . $start_date->format('d/m/Y'));
        $stop_date = Carbon::today()->addDays(13);
        if ($request->stop_date) {
            $stop_date = Carbon::parse($request->stop_date);
        }
        $this->logger->info('stop_date: ' . $stop_date->format('d/m/Y'));
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
        $this->logger->info('numero giorni da esaminare: ' . count($diff_in_date_from_start_stop));
        $calendarIndex = 0;
        $this->logger->info('calendario "' . $calendars[$calendarIndex]->name . '" start: ' . $calendars[$calendarIndex]->start_date->format('d/m/Y') . ' stop: ' . $calendars[$calendarIndex]->stop_date->format('d/m/Y'));
        foreach ($diff_in_date_from_start_stop as $currentDay) {
            $currentCalendar = $calendars[$calendarIndex];

            if ($currentDay >= $currentCalendar->start_date && $currentDay <= $currentCalendar->stop_date) { // se il giono è dopo lo start del calendario corrente
                if (
                    in_array($currentDay->dayOfWeek, $currentCalendar->calendarItems->pluck('day_of_week')->toArray())
                ) {
                    $this->logger->info('costruisco giorno di calendario per ' . $currentDay->format('d/m/Y'));
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
                    $this->logger->info('giorno di calendario skippato perche non è presente nessun ritiro ' . $currentDay->format('d/m/Y'));
                }
            }
            if ($currentDay > $currentCalendar->stop_date && count($calendars) - 1 > $calendarIndex) {
                $calendarIndex++;
                $this->logger->info('passaggio a calendario successivo "' . $calendars[$calendarIndex]->name . '" start: ' . $calendars[$calendarIndex]->start_date->format('d/m/Y') . ' stop: ' . $calendars[$calendarIndex]->stop_date->format('d/m/Y'));
            }
        }

        // Everything it's fine: build and send output
        // \Carbon\Carbon::parse('today +2 day')->dayOfWeek

        $this->logger->info('numero giorni di calendario creati: ' . count($data));
        return $this->sendResponse($data, 'Calendar created.');
    }

    public function v1index(Request $request)
    {
        $user = Auth::user();
        $this->logger = Log::channel('calendars');

        $company = Company::find($request->id);
        if (count($company->calendars) == 0) {
            return $this->sendError('Company has no calendars.');
        }
        $start_date = Carbon::today();
        if ($request->start_date) {
            $start_date = Carbon::parse($request->start_date);
        }
        $this->logger->info('start_date: ' . $start_date->format('d/m/Y'));
        $stop_date = Carbon::today()->addDays(13);
        if ($request->stop_date) {
            $stop_date = Carbon::parse($request->stop_date);
        }
        $this->logger->info('stop_date: ' . $stop_date->format('d/m/Y'));
        if($start_date >= $stop_date) {
            return $this->sendError('The dates are not valid.');
        }
        $addresses = Address::where('user_id', $user->id)->get();
        $res = [];
        foreach ($addresses as $address) {
            $data = [];
            $currentDayIndex = 0;

            // ritorna i calendari compresi dallo start allo stop ordinati temporalmente
            $calendars = Calendar::where('zone_id', $address->zone_id)
                ->where('user_type_id', $address->user_type_id)
                ->whereDate('start_date', '<=', $start_date)
                ->orderBy('start_date', 'asc')
                ->get();
            if (is_null($calendars) || count($calendars) === 0) {
                continue;
            }
            $diff_in_date_from_start_stop = CarbonPeriod::create($start_date, $stop_date)->toArray(); // tutti i giorni dallo start allo stop
            $this->logger->info('numero giorni da esaminare: ' . count($diff_in_date_from_start_stop));
            $calendarIndex = 0;
            $this->logger->info('calendario "' . $calendars[$calendarIndex]->name . '" start: ' . $calendars[$calendarIndex]->start_date->format('d/m/Y') . ' stop: ' . $calendars[$calendarIndex]->stop_date->format('d/m/Y'));
            while ($currentDayIndex < count($diff_in_date_from_start_stop)) {
                $currentDay = $diff_in_date_from_start_stop[$currentDayIndex];
                $currentCalendar = $calendars[$calendarIndex];

                if ($currentDay >= $currentCalendar->start_date && $currentDay <= $currentCalendar->stop_date) { // se il giorno è dopo lo start del calendario corrente
                    if (in_array($currentDay->dayOfWeek, $currentCalendar->calendarItems->pluck('day_of_week')->toArray())) {
                        $this->logger->info('costruisco giorno di calendario per ' . $currentDay->format('d/m/Y'));
                        foreach ($currentCalendar->calendarItems->where('day_of_week', $currentDay->dayOfWeek) as $item) {
                            $p = [];
                            $p['trash_types'] = collect($item->trashTypes->toArray())->map(function ($trashType) {
                                if (isset($trashType["allowed"]) && $trashType["allowed"] !== null) {
                                    $trashType["allowed"] = $trashType["allowed"]["it"];
                                } else {
                                    $trashType["allowed"] = "";
                                }
                                if (isset($trashType["notallowed"]) && $trashType["notallowed"] !== null) {
                                    $trashType["notallowed"] = $trashType["notallowed"]["it"];
                                } else {
                                    $trashType["notallowed"] = "";
                                }
                                return $trashType;
                            });
                            $p['frequency'] = $item->frequency;
                            $p['start_time'] = str_replace('0:00', '0', $item->start_time);
                            $p['stop_time'] = str_replace('0:00', '0', $item->stop_time);
                            if ($item->frequency == 'biweekly') {
                                $baseDate = Carbon::parse($item->base_date);
                                $diffInWeeks = $baseDate->diffInWeeks($currentDay);

                                if ($diffInWeeks % 2 == 0) {
                                    $p['base_date'] = $item->base_date;
                                    $data[$currentDay->format('Y-m-d')][] = $p;
                                }
                            } else {
                                $data[$currentDay->format('Y-m-d')][] = $p;
                            }
                        }
                    } else {
                        $this->logger->info('giorno di calendario skippato perché non è presente nessun ritiro ' . $currentDay->format('d/m/Y'));
                    }
                }

                if ($currentDay > $currentCalendar->stop_date && count($calendars) - 1 > $calendarIndex) {
                    $calendarIndex++;
                    $this->logger->info('passaggio a calendario successivo "' . $calendars[$calendarIndex]->name . '" start: ' . $calendars[$calendarIndex]->start_date->format('d/m/Y') . ' stop: ' . $calendars[$calendarIndex]->stop_date->format('d/m/Y'));
                } else {
                    $currentDayIndex++;
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
