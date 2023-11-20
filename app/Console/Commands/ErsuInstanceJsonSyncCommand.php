<?php

namespace App\Console\Commands;

use Exception;
use Carbon\Carbon;
use App\Models\Zone;
use App\Models\Waste;
use App\Models\Calendar;
use App\Models\UserType;
use App\Models\TrashType;
use App\Models\CalendarItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\WasteCollectionCenter;
use App\Providers\CurlServiceProvider;

class ErsuInstanceJsonSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pap:ersusync {company_id : e.g. 1 (for ersu)} {endpoint : e.g. http://apiersu.netseven.it/} {--Z|zone : Only sync Zone Meta}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync a json file to its specific table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $company_id = $this->argument('company_id');
        $endpoint = $this->argument('endpoint');

        if ($this->option('zone')) {
            $this->syncZoneMeta($company_id, $endpoint);
            return 0;
        } else {
            $this->syncTipiRifiuto($company_id, $endpoint);
            $this->syncUtenzeMeta($company_id, $endpoint);
            $this->syncCentriRaccolta($company_id, $endpoint);
            $this->syncRifiutario($company_id, $endpoint);
            $this->syncZoneMeta($company_id, $endpoint);
            return 0;
        }
    }

    protected function syncTipiRifiuto($company_id, $endpoint)
    {

        // Curl request to get the feature information from external source
        $curl = app(CurlServiceProvider::class);
        $url = $endpoint . '/data/tipi_rifiuto.json';
        $track_obj = $curl->exec($url);
        $response = json_decode($track_obj, true);
        $params = [];

        try {
            foreach ($response as $trash) {
                $params = $this->getTrashParams($trash);
                TrashType::updateOrCreate(
                    [
                        'slug' => $trash['id'],
                        'company_id' => $company_id
                    ],
                    $params
                );
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    protected function syncUtenzeMeta($company_id, $endpoint)
    {

        // Curl request to get the feature information from external source
        $curl = app(CurlServiceProvider::class);
        $url = $endpoint . '/data/utenze_meta.json';
        $track_obj = $curl->exec($url);
        $response = json_decode($track_obj, true);

        try {
            foreach ($response as $usertype => $array) {
                if (array_key_exists('label', $array)) {
                    $params['label']['it'] = $array['label'];
                }
                if (!empty($array['translations'])) {
                    if (array_key_exists('en', $array['translations'])) {
                        $params['label']['en'] = $array['translations']['en']['label'];
                    }
                }
                UserType::updateOrCreate(
                    [
                        'slug' => $usertype,
                        'company_id' => $company_id
                    ],
                    $params
                );
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    protected function syncCentriRaccolta($company_id, $endpoint)
    {
        $curl = app(CurlServiceProvider::class);
        $url = $endpoint . '/data/centri_raccolta.geojson';
        $response = json_decode($curl->exec($url), true);

        try {
            foreach ($response['features'] as $feature) {
                $params = $this->getParamsFromCentriRaccolta($feature);
                $lat = $feature['geometry']['coordinates'][0];
                $lng = $feature['geometry']['coordinates'][1];

                $waste_center = WasteCollectionCenter::updateOrCreate(
                    [
                        'geometry' => DB::select("SELECT ST_GeomFromText('POINT($lat $lng)') as g")[0]->g,
                        'company_id' => $company_id
                    ],
                    $params
                );

                $this->syncUserTypes($feature, $company_id, $waste_center);
                $this->syncTrashTypes($feature, $company_id, $waste_center);
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage() . ' at line: ' . $e->getLine() . "\n";
        }
    }



    private function syncUserTypes($feature, $company_id, $waste_center)
    {
        if (array_key_exists('userTypes', $feature['properties']) && !empty($feature['properties']['userTypes'])) {
            $user_types = [];
            foreach ($feature['properties']['userTypes'] as $value) {
                $userType = UserType::where('company_id', $company_id)
                    ->where('slug', $value)->first();
                if ($userType) {
                    array_push($user_types, $userType->id);
                }
            };
            $waste_center->userTypes()->sync($user_types, false);
        }
    }

    private function syncTrashTypes($feature, $company_id, $waste_center)
    {
        if (array_key_exists('trashTypes', $feature['properties']) && !empty($feature['properties']['trashTypes'])) {
            $trash_types = [];
            foreach ($feature['properties']['trashTypes'] as $value) {
                $trashType = TrashType::where('company_id', $company_id)
                    ->where('slug', $value)->first();

                if ($trashType) {
                    array_push($trash_types, $trashType->id);
                } else {
                    Log::debug('TrashType relation not found: ' . json_encode($value) . ' creating new one');
                    $params = $this->getTrashParams($feature['properties']);
                    $trashType = TrashType::updateOrCreate(
                        [
                            'slug' => $value,
                            'company_id' => $company_id
                        ],
                        $params
                    );
                    array_push($trash_types, $trashType->id);
                }
            };
            $waste_center->trashTypes()->sync($trash_types, false);
        }
    }

    protected function syncRifiutario($company_id, $endpoint)
    {
        // Curl request to get the feature information from external source
        $curl = app(CurlServiceProvider::class);
        $url = $endpoint . '/data/rifiutario.json';
        $track_obj = $curl->exec($url);
        $response = json_decode($track_obj, true);

        try {
            foreach ($response as $waste) {
                $params = [
                    'notes' => [
                        'it' => $waste['notes'] ?? null,
                        'en' => $waste['translations']['en']['notes'] ?? null,
                    ],
                    'where' => [
                        'it' => $waste['where'] ?? null,
                        'en' => $waste['translations']['en']['where'] ?? null,
                    ],
                    'pap' => $waste['pap'] ?? null,
                    'collection_center' => $waste['collection_center'] ?? null,
                    'delivery' => $waste['delivery'] ?? null,
                    'name' => [
                        'it' => $waste['name'],
                        'en' => $waste['translations']['en']['name'] ?? null,
                    ],
                ];

                if (array_key_exists('category', $waste)) {
                    $trashType = TrashType::where('company_id', $company_id)
                        ->where('slug', $waste['category'])
                        ->first();

                    if ($trashType) {
                        $params['trash_type_id'] = $trashType->id;
                    } else {
                        Log::error('TrashType relation not found: ' . json_encode($waste));
                        continue;
                    }
                }

                Waste::updateOrCreate(
                    [
                        'name' => [
                            'it' => $waste['name'],
                        ],
                        'company_id' => $company_id,
                    ],
                    $params
                );
            }
        } catch (Exception $e) {
            Log::error('Caught exception syncRifiutario: ' . $waste['name'] . ' ' .  $e->getMessage());
        }
    }

    protected function syncZoneMeta($company_id, $endpoint)
    {

        // Curl request to get the feature geometry
        $curl = app(CurlServiceProvider::class);
        $url = $endpoint . '/data/zone_confini.geojson';
        $track_obj = $curl->exec($url);
        $response = json_decode($track_obj, true);

        $coordinate_array = [];
        try {
            foreach ($response['features'] as $zone) {
                $coordinate_array[$zone['properties']['id']] = array(
                    "type" => "MultiPolygon",
                    "coordinates" => $zone['geometry']['coordinates']
                );
            }
        } catch (Exception $e) {
            Log::error('Caught exception syncZoneConfini: ' . json_encode($zone) . ' ' .  $e->getMessage());
        }

        // Curl request to get the feature information from external source
        $curl = app(CurlServiceProvider::class);
        $url = $endpoint . '/data/zone_meta.json';
        $track_obj = $curl->exec($url);
        $response = json_decode($track_obj, true);

        try {
            $prompt = $this->confirm('Do you also want to sync calendars for the zones?');
            foreach ($response as $zone) {
                if (array_key_exists('comune', $zone)) {
                    $params['comune'] = $zone['comune'];
                }
                if (array_key_exists('url', $zone)) {
                    $params['url'] = $zone['url'];
                }
                if (array_key_exists($zone['id'], $coordinate_array)) {
                    $params['geometry'] = DB::select("SELECT ST_AsText(ST_GeomFromGeoJSON('" . json_encode($coordinate_array[$zone['id']]) . ",4326')) As wkt")[0]->wkt;
                }
                $params['company_id'] = $company_id;

                $zone_obg = Zone::updateOrCreate(
                    [
                        'comune' => $zone['comune'],
                        'label' =>  $zone['label'],
                        'company_id' => $company_id
                    ],
                    $params
                );

                if (array_key_exists('types', $zone)) {
                    $zones = [];
                    foreach ($zone['types'] as $z) {
                        $userType = UserType::where('company_id', $company_id)
                            ->where('slug', $z)->get();
                        array_push($zones, $userType[0]->id);
                    };
                    $zone_obg->userTypes()->sync($zones, false);
                }
                $this->info('zone: ' . $zone['label'] . PHP_EOL);

                if ($prompt)
                    if (count($zone_obg->userTypes) > 0) {
                        foreach ($zone_obg->userTypes as $userType) {
                            $this->syncCalendario($userType->slug, $endpoint, $userType, $zone, $zone_obg->id, $company_id);
                        }
                    }
            }
        } catch (Exception $e) {
            Log::error('Caught exception syncZoneMeta: ' . json_encode($zone) . ' ' .  $e->getMessage());
        }
    }

    protected function syncCalendario($slug, $endpoint, $userType, $zone, $zoneObgId, $company_id)
    {
        // Curl request to get the feature information from external source
        $curl = app(CurlServiceProvider::class);
        $url = $endpoint . '/data/calendar_' . $slug . '_input.json';
        $obj = $curl->exec($url);
        $response = json_decode($obj, true);
        $calendarItems = [];

        //take only the item from the response with key = zone->id
        $response = array_filter($response, function ($key) use ($zone) {
            return $key == $zone['id'];
        }, ARRAY_FILTER_USE_KEY);

        $calendarName = $zone['label'] . ' (' . $slug . ') ' . $zone['comune'];
        $params = [
            'name' => $calendarName,
            'zone_id' => $zoneObgId,
            'user_type_id' => $userType->id,
            'company_id' => $company_id,

        ];

        $this->info('calendar: ' . $url . ' for zone: ' . $zone['label'] . PHP_EOL);

        foreach ($response as $items) {
            foreach ($items as $calendar) {
                if (array_key_exists('start', $calendar)) {
                    //convert string to date
                    $params['start_date'] =  $this->setStartDate($calendar);
                };
                if (array_key_exists('end', $calendar) || array_key_exists('stop', $calendar)) {
                    //convert string to date
                    $params['stop_date'] =  $this->setStopDate($calendar);
                };
                if (array_key_exists('calendars', $calendar)) {
                    foreach ($calendar['calendars'] as $calendarItem) {
                        $calendarItems[] = $calendarItem;
                    }
                }
                $syncedCalendar = Calendar::factory()->create($params);
                foreach ($calendarItems as $calendarItem) {
                    $this->syncCalendarioItem($calendarItem, $syncedCalendar);
                }
                //reset the calendarItems array
                $calendarItems = [];
            }
        }
    }
    protected function syncCalendarioItem($calendarItem, $syncedCalendar)
    {
        $item = [];
        $trashTypes = [];
        $frequency = 'weekly';
        $servicesByDay = [];

        if (array_key_exists('start', $calendarItem)) {
            $startTime = $calendarItem['start'];
        }
        if (array_key_exists('end', $calendarItem)) {
            $stopTime = $calendarItem['end'];
        }

        foreach ($calendarItem['services'] as $service => $serviceData) {

            //if the ['days'] key does not exist, and instead a ['baseDate'] and ['frequency'] keys are found, 
            if (array_key_exists('baseDate', $serviceData) && array_key_exists('frequency', $serviceData)) {

                $services[] = $service;
                $dateDay = $this->handleBaseDate($serviceData['baseDate']);

                //format $serviceData['baseDate'] to a Carbon date
                $formattedBaseDate = Carbon::createFromFormat('Y-m-d', $serviceData['baseDate'])->format('Y-m-d');

                $item = [
                    'calendar_id' => $syncedCalendar->id,
                    'start_time' => $startTime,
                    'stop_time' => $stopTime,
                    'day_of_week' => $dateDay,
                    'frequency' => $serviceData['frequency'] == 14 ? 'biweekly' : 'weekly',
                    'services' => $services, // initialize the services array
                    'base_date' => $formattedBaseDate
                ];

                //get the trashtypes from the $item['services']
                $trashTypes = $this->getTrashTypes($item['services'], $syncedCalendar);

                $newCalendarItem = CalendarItem::create($item);
                //attach every trashtype in the array to the newcalendaritem
                $newCalendarItem->trashTypes()->sync(collect($trashTypes)->pluck('id')->toArray());
            }
            if (array_key_exists('days', $serviceData)) {
                foreach ($serviceData['days'] as $day) {
                    if ($day == 7) {
                        $day = 0;
                    }
                    if (!isset($servicesByDay[$day])) {
                        $servicesByDay[$day] = [];
                    }
                    $servicesByDay[$day][] = $service;
                }
            }
        }
        foreach ($servicesByDay as $dayOfWeek => $services) {
            //create a new calendar item for each day of the week and assign the services to it

            $item = [
                'calendar_id' => $syncedCalendar->id,
                'start_time' => $startTime,
                'stop_time' => $stopTime,
                'day_of_week' => $dayOfWeek,
                'frequency' => $frequency,
            ];

            $newCalendarItem = CalendarItem::create($item);

            $trashTypes = $this->getTrashTypes($services, $syncedCalendar);

            $newCalendarItem->trashTypes()->sync(collect($trashTypes)->pluck('id')->toArray());
        }

        //reset the servicesByDay array
        $servicesByDay = [];
    }


    protected function setStartDate($calendar)
    {
        $dateString = $calendar['start'];
        $currentYear = Carbon::now()->year;
        $dateStringWithYear = $currentYear . '-' . $dateString;
        $date = Carbon::createFromFormat('Y-m-d', $dateStringWithYear)->format('Y-m-d');
        return $date;
    }

    protected function setStopDate($calendar)
    {
        $dateEndString = $calendar['end'] ?? $calendar['stop'];
        $dateStartString = $calendar['start'];
        $currentYear = Carbon::now()->year;
        //if the start_date month is greater than the stop_date month add 1 year to the stop_date
        if (Carbon::createFromFormat('m-d', $dateStartString)->month > Carbon::createFromFormat('m-d', $dateEndString)->month) {
            $currentYear = $currentYear + 1;
        }
        $dateEndStringWithYear = $currentYear . '-' . $dateEndString;
        $date = Carbon::createFromFormat('Y-m-d', $dateEndStringWithYear)->format('Y-m-d');
        return $date;
    }

    protected function getTrashTypes($services, $syncedCalendar)
    {
        $trashTypes = [];
        foreach ($services as $service) {
            $trashType = TrashType::where('company_id', $syncedCalendar->company_id)
                ->where('slug', $service)->get();
            $trashTypes[] = $trashType[0];
        }
        return $trashTypes;
    }

    protected function handleBaseDate($baseDate)
    {
        $dateDay = Carbon::createFromFormat('Y-m-d', $baseDate)->format('l');
        $dateDay = Carbon::parse($dateDay)->dayOfWeekIso;
        if ($dateDay == 7) {
            $dateDay = 0;
        }

        return $dateDay;
    }

    private function getTrashParams($trash)
    {
        $params = [];

        if (array_key_exists('name', $trash)) {
            $params['name']['it'] = $trash['name'];
        }
        if (array_key_exists('description', $trash)) {
            $params['description']['it'] = $trash['description'];
        }
        if (array_key_exists('howto', $trash)) {
            $params['howto']['it'] = $trash['howto'];
        }
        if (array_key_exists('where', $trash)) {
            $params['where']['it'] = $trash['where'];
        }
        if (array_key_exists('color', $trash)) {
            $params['color'] = $trash['color'];
        }
        if (array_key_exists('allowed', $trash)) {
            $params['allowed']['it'] = $trash['allowed'];
        }
        if (array_key_exists('notallowed', $trash)) {
            $params['notallowed']['it'] = $trash['notallowed'];
        }
        if (!empty($trash['translations'])) {
            if (array_key_exists('name', $trash['translations']['en'])) {
                $params['name']['en'] = $trash['translations']['en']['name'];
            }
            if (array_key_exists('description', $trash['translations']['en'])) {
                $params['description']['en'] = $trash['translations']['en']['description'];
            }
            if (array_key_exists('howto', $trash['translations']['en'])) {
                $params['howto']['en'] = $trash['translations']['en']['howto'];
            }
            if (array_key_exists('where', $trash['translations']['en'])) {
                $params['where']['en'] = $trash['translations']['en']['where'];
            }
            if (array_key_exists('allowed', $trash['translations']['en'])) {
                $params['allowed']['en'] = $trash['translations']['en']['allowed'];
            }
            if (array_key_exists('notallowed', $trash['translations']['en'])) {
                $params['notallowed']['en'] = $trash['translations']['en']['notallowed'];
            }
        }

        return $params;
    }

    private function getParamsFromCentriRaccolta($feature)
    {
        $params = [];

        $properties = $feature['properties'];

        $params['name']['it'] = $properties['name'] ?? null;
        $params['marker-color'] = $properties['marker-color'] ?? null;
        $params['marker-size'] = $properties['marker-size'] ?? null;
        $params['marker-symbol'] = $properties['marker-symbol'] ?? null;
        $params['website'] = $properties['website'] ?? null;
        $params['picture_url'] = $properties['picture_url'] ?? null;
        $params['orario']['it'] = $properties['orario'] ?? null;
        $params['description']['it'] = $properties['description'] ?? null;

        if (!empty($properties['translations'])) {
            $translations = $properties['translations']['en'] ?? null;
            if ($translations) {
                $params['name']['en'] = $translations['name'] ?? null;
                $params['orario']['en'] = $translations['orario'] ?? null;
                $params['description']['en'] = $translations['description'] ?? null;
            }
        }

        return $params;
    }
}
