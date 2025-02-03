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
    const DEFAULT_DATE_RANGE = 13;
    const DATE_FORMAT_FOR_LOG = 'd/m/Y';
    const DATE_FORMAT_FOR_RESPONSE = 'Y-m-d';

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
        $this->logger->info('start_date: ' . $start_date->format(self::DATE_FORMAT_FOR_LOG));
        $stop_date = Carbon::today()->addDays(13);
        if ($request->stop_date) {
            $stop_date = Carbon::parse($request->stop_date);
        }
        $this->logger->info('stop_date: ' . $stop_date->format(self::DATE_FORMAT_FOR_LOG));
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
        $this->logger->info('calendario "' . $calendars[$calendarIndex]->name . '" start: ' . $calendars[$calendarIndex]->start_date->format(self::DATE_FORMAT_FOR_LOG) . ' stop: ' . $calendars[$calendarIndex]->stop_date->format(self::DATE_FORMAT_FOR_LOG));
        foreach ($diff_in_date_from_start_stop as $currentDay) {
            $currentCalendar = $calendars[$calendarIndex];

            if ($currentDay >= $currentCalendar->start_date && $currentDay <= $currentCalendar->stop_date) { // se il giono è dopo lo start del calendario corrente
                if (
                    in_array($currentDay->dayOfWeek, $currentCalendar->calendarItems->pluck('day_of_week')->toArray())
                ) {
                    $this->logger->info('costruisco giorno di calendario per ' . $currentDay->format(self::DATE_FORMAT_FOR_LOG));
                    foreach ($currentCalendar->calendarItems->where('day_of_week', $currentDay->dayOfWeek) as $item) {
                        $p = [];
                        $p['trash_types'] = $item->trashTypes->pluck('id')->toArray();
                        $p['frequency'] = $item->frequency;
                        $p['start_time'] = str_replace('0:00', '0', $item->start_time);
                        $p['stop_time'] = str_replace('0:00', '0', $item->stop_time);
                        if ($item->frequency == 'biweekly') {
                            $p['base_date'] = $item->base_date;
                        }
                        $data[$currentDay->format('self::DATE_FORMAT_FOR_LOG')][] = $p;
                    }
                } else {
                    $this->logger->info('giorno di calendario skippato perche non è presente nessun ritiro ' . $currentDay->format(self::DATE_FORMAT_FOR_LOG));
                }
            }
            if ($currentDay > $currentCalendar->stop_date && count($calendars) - 1 > $calendarIndex) {
                $calendarIndex++;
                $this->logger->info('passaggio a calendario successivo "' . $calendars[$calendarIndex]->name . '" start: ' . $calendars[$calendarIndex]->start_date->format(self::DATE_FORMAT_FOR_LOG) . ' stop: ' . $calendars[$calendarIndex]->stop_date->format(self::DATE_FORMAT_FOR_LOG));
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
        if (!$company || $company->calendars->isEmpty()) {
            return $this->sendError('Company has no calendars.');
        }
        $start_date = $this->getStartDate($request);
        $this->logger->info('start_date: ' . $start_date->format(self::DATE_FORMAT_FOR_LOG));

        $stop_date = $this->getStopDate($request);
        $this->logger->info('stop_date: ' . $stop_date->format(self::DATE_FORMAT_FOR_LOG));

        if($start_date >= $stop_date) {
            return $this->sendError('The dates are not valid.');
        }
        $addresses = Address::where('user_id', $user->id)->get();
        $res = [];

        foreach ($addresses as $address) {
            $data = [];
            $calendars = Calendar::where('zone_id', $address->zone_id)
                ->where('user_type_id', $address->user_type_id)
                ->whereDate('start_date', '<=', $start_date)
                ->orderBy('start_date', 'asc')
                ->get();

            if ($calendars->isEmpty()) {
                continue;
            }
            foreach ($calendars as $calendar) {
                $calendarData = $this->createCalendar($calendar, $start_date, $stop_date);
                $data= array_merge_recursive($data, $calendarData);
            }

            $elem['address'] = $address;
            $elem['address']['zone'] = Zone::find($address['zone_id']);
            $elem['address']['user_type'] = UserType::find($address['user_type_id']);
            $elem['calendar'] = $data;

            array_push($res, $elem);
        }

        return $this->sendResponse($res, 'Calendar created.');
    }

    /**
     * Retrieves calendars linked to a specific zone within a company.
     *
     * @param Request $request
     * @param int $company_id
     * @param int $zone_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function V1IndexByZone(Request $request)
    {
        $this->logger = Log::channel('calendars'); 
        $company_id = $request->id;
        $zone_id = $request->zone_id;
        $this->logger->info('V1IndexByZone called for Company ID: ' . $company_id . ', Zone ID: ' . $zone_id);

        try {
            // Fetch the company
            $company = Company::find($company_id);
            if (!$company) {
                $this->logger->error('Company not found: ID ' . $company_id);
                return $this->sendError('Company not found.');
            }

            // Check if the company has calendars
            if ($company->calendars->isEmpty()) {
                $this->logger->warning('Company has no calendars: ID ' . $company_id);
                return $this->sendError('Company has no calendars.');
            }

            // Fetch the zone
            $zone = Zone::find($zone_id);
            if (!$zone) {
                $this->logger->error('Zone not found: ID ' . $zone_id);
                return $this->sendError('Zone not found.');
            }
            // Setup start_date
            $start_date = $this->getStartDate($request);
            $this->logger->info('start_date: ' . $start_date->format(self::DATE_FORMAT_FOR_LOG));

            // Setup stop_date
            $stop_date = $this->getStopDate($request);
            $this->logger->info('stop_date: ' . $stop_date->format());

            // Validate dates
            if ($start_date->greaterThanOrEqualTo($stop_date)) {
                $this->logger->error('Invalid date range: start_date >= stop_date');
                return $this->sendError('The dates are not valid.');
            }

            // Retrieve relevant calendars for the zone
            $calendars = Calendar::with(['calendarItems.trashTypes'])
                ->where('company_id', $company_id)
                ->where('zone_id', $zone_id)
                ->whereDate('start_date', '<=', $start_date)
                ->orderBy('start_date', 'asc')
                ->get();

            if ($calendars->isEmpty()) {
                $this->logger->warning('No calendars found for Zone ID: ' . $zone_id . ' in Company ID: ' . $company_id);
                return $this->sendError('No calendars found for the specified zone.');
            }

            $data = [];

            foreach ($calendars as $calendar) {
                $calendarData = $this->createCalendar($calendar, $start_date, $stop_date);
                $data = array_merge_recursive($data, $calendarData);
            }

            // Prepare zone details
            $elem['zone'] = $zone;
            $elem['company'] = $company;
            $elem['calendar'] = $data;

            $this->logger->info('Calendars successfully retrieved for Zone ID: ' . $zone_id . ' in Company ID: ' . $company_id);

            return $this->sendResponse($elem, 'Calendars retrieved successfully.');
        } catch (\Exception $e) {
            $this->logger->error('Exception in V1IndexByZone: ' . $e->getMessage());
            return $this->sendError('An unexpected error occurred.');
        }
    }

    private function getStartDate(Request $request){
        $start_date = $request->start_date;
        if(!$start_date){
            $start_date = Carbon::today();
        }
        return Carbon::parse($start_date);
    }

    private function getStopDate(Request $request){
        $stop_date = $request->stop_date;
        if(!$stop_date){
            $stop_date = Carbon::today()->addDays(self::DEFAULT_DATE_RANGE);
        }
        return Carbon::parse($stop_date);
    }

    private function createCalendar($calendar, $start_date, $stop_date)
    {
        $data = [];
        $diff_in_date_from_start_stop = CarbonPeriod::create($start_date, $stop_date)->toArray();
        $this->logger->info('Numero giorni da esaminare: ' . count($diff_in_date_from_start_stop));

        $calendarIndex = 0;
        $currentDayIndex = 0;
        $calendars = [$calendar]; // Assuming we're dealing with a single calendar

        $this->logger->info('Calendario "' . $calendars[$calendarIndex]->name . '" start: ' . $calendars[$calendarIndex]->start_date->format(self::DATE_FORMAT_FOR_LOG) . ' stop: ' . $calendars[$calendarIndex]->stop_date->format(self::DATE_FORMAT_FOR_LOG));

        while ($currentDayIndex < count($diff_in_date_from_start_stop)) {
            $currentDay = $diff_in_date_from_start_stop[$currentDayIndex];
            $currentCalendar = $calendars[$calendarIndex];

            if ($currentDay >= $currentCalendar->start_date && $currentDay <= $currentCalendar->stop_date) {
                if (in_array($currentDay->dayOfWeek, $currentCalendar->calendarItems->pluck('day_of_week')->toArray())) {
                    $this->logger->info('Costruisco giorno di calendario per ' . $currentDay->format(self::DATE_FORMAT_FOR_LOG));
                    foreach ($currentCalendar->calendarItems->where('day_of_week', $currentDay->dayOfWeek) as $item) {
                        $p = [];
                        $p['trash_types'] = collect($item->trashTypes->toArray())->map(function ($trashType) {
                            $trashType["allowed"] = $trashType["allowed"]["it"] ?? "";
                            $trashType["notallowed"] = $trashType["notallowed"]["it"] ?? "";
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
                                $data[$currentDay->format(self::DATE_FORMAT_FOR_RESPONSE)][] = $p;
                            }
                        } else {
                            $data[$currentDay->format(self::DATE_FORMAT_FOR_RESPONSE)][] = $p;
                        }
                    }
                } else {
                    $this->logger->info('Giorno di calendario skippato perché non è presente nessun ritiro ' . $currentDay->format(self::DATE_FORMAT_FOR_LOG));
                }
            }

            if ($currentDay > $currentCalendar->stop_date && count($calendars) - 1 > $calendarIndex) {
                $calendarIndex++;
                $this->logger->info('Passaggio a calendario successivo "' . $calendars[$calendarIndex]->name . '" start: ' . $calendars[$calendarIndex]->start_date->format(self::DATE_FORMAT_FOR_LOG) . ' stop: ' . $calendars[$calendarIndex]->stop_date->format(self::DATE_FORMAT_FOR_LOG));
            } else {
                $currentDayIndex++;
            }
        }

        return $data;
    }

}
