<?php

namespace App\Console\Commands;

use App\Models\TrashType;
use App\Models\UserType;
use App\Models\WasteCollectionCenter;
use App\Providers\CurlServiceProvider;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InstanceJsonSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pap:sync {company_id : e.g. 4 (for esa)} {endpoint : e.g. https://apiesa.webmapp.it/}';

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

        
        $this->syncTipiRifiuto($company_id,$endpoint);
        $this->syncUtenzeMeta($company_id,$endpoint);
        $this->syncCentriRaccolta($company_id,$endpoint);
    }
    
    protected function syncTipiRifiuto($company_id,$endpoint){

        // Curl request to get the feature information from external source
        $curl = app(CurlServiceProvider::class);
        $url = $endpoint . '/data/tipi_rifiuto.json';
        $track_obj = $curl->exec($url);
        $response = json_decode($track_obj,true);


        try {
            foreach ($response as $trash) {
                if (array_key_exists('name',$trash)) {
                    $params['name']['it'] = $trash['name'];
                }
                if (array_key_exists('description',$trash)) {
                    $params['description']['it'] = $trash['description'];
                }
                if (array_key_exists('howto',$trash)) {
                    $params['howto']['it'] = $trash['howto'];
                }
                if (array_key_exists('where',$trash)) {
                    $params['where']['it'] = $trash['where'];
                }
                if (array_key_exists('color',$trash)) {
                    $params['color']['it'] = $trash['color'];
                }
                if (array_key_exists('allowed',$trash)) {
                    $params['allowed']['it'] = $trash['allowed'];
                }
                if (array_key_exists('notallowed',$trash)) {
                    $params['notallowed']['it'] = $trash['notallowed'];
                }
                if(!empty($trash['translations'])) {
                    if (array_key_exists('name',$trash['translations'])) { 
                        $params['name']['en'] = $trash['translations']['en']['name']; 
                    }
                    if (array_key_exists('description',$trash['translations'])) { 
                        $params['description']['en'] = $trash['translations']['en']['description'];
                    }
                    if (array_key_exists('howto',$trash['translations'])) { 
                        $params['howto']['en'] = $trash['translations']['en']['howto']; 
                    }
                    if (array_key_exists('where',$trash['translations'])) { 
                        $params['where']['en'] = $trash['translations']['en']['where']; 
                    }
                    if (array_key_exists('color',$trash['translations'])) { 
                        $params['color']['en'] = $trash['translations']['en']['color']; 
                    }
                    if (array_key_exists('allowed',$trash['translations'])) { 
                        $params['allowed']['en'] = $trash['translations']['en']['allowed']; 
                    }
                    if (array_key_exists('notallowed',$trash['translations'])) { 
                        $params['notallowed']['en'] = $trash['translations']['en']['notallowed']; 
                    }
                }
                TrashType::updateOrCreate(
                    [
                        'slug' => $trash['id'],
                        'company_id' => $company_id
                    ],
                    $params);
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    protected function syncUtenzeMeta($company_id,$endpoint){

        // Curl request to get the feature information from external source
        $curl = app(CurlServiceProvider::class);
        $url = $endpoint . '/data/utenze_meta.json';
        $track_obj = $curl->exec($url);
        $response = json_decode($track_obj,true);

        try {
            foreach ($response as $usertype => $array) {
                if (array_key_exists('label',$array)) {
                    $params['label']['it'] = $array['label'];
                }
                if(!empty($array['translations'])) { 
                    if (array_key_exists('en',$array['translations'])) { 
                        $params['label']['en'] = $array['translations']['en']['label']; 
                    }
                }
                UserType::updateOrCreate(
                    [
                        'slug' => $usertype,
                        'company_id' => $company_id
                    ],
                    $params);
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    protected function syncCentriRaccolta($company_id,$endpoint){

        // Curl request to get the feature information from external source
        $curl = app(CurlServiceProvider::class);
        $url = $endpoint . '/data/centri_raccolta.geojson';
        $track_obj = $curl->exec($url);
        $response = json_decode($track_obj,true);

        try {
            foreach ($response['features'] as $feature ) {
                if (array_key_exists('name',$feature['properties'])) {
                    $params['name']['it'] = $feature['properties']['name'];
                }
                if (array_key_exists('marker-color',$feature['properties'])) {
                    $params['marker-color'] = $feature['properties']['marker-color'];
                }
                if (array_key_exists('marker-size',$feature['properties'])) {
                    $params['marker-size'] = $feature['properties']['marker-size'];
                }
                if (array_key_exists('marker-symbol',$feature['properties'])) {
                    $params['marker-symbol'] = $feature['properties']['marker-symbol'];
                }
                if (array_key_exists('website',$feature['properties'])) {
                    $params['website'] = $feature['properties']['website'];
                }
                if (array_key_exists('picture_url',$feature['properties'])) {
                    $params['picture_url'] = $feature['properties']['picture_url'];
                }
                if (array_key_exists('orario',$feature['properties'])) {
                    $params['orario']['it'] = $feature['properties']['orario'];
                }
                if (array_key_exists('description',$feature['properties'])) {
                    $params['description']['it'] = $feature['properties']['description'];
                }
                
                if(!empty($feature['properties']['translations'])) { 
                    if (array_key_exists('en',$feature['properties']['translations'])) { 
                        $params['name']['en'] = $feature['properties']['translations']['en']['name']; 
                        $params['orario']['en'] = $feature['properties']['translations']['en']['orario']; 
                        $params['description']['en'] = $feature['properties']['translations']['en']['description']; 
                    }
                }

                $lat = $feature['geometry']['coordinates'][0];
                $lng = $feature['geometry']['coordinates'][1];

                $waste_center = WasteCollectionCenter::updateOrCreate(
                    [
                        'geometry' => DB::select("SELECT ST_GeomFromText('POINT($lat $lng)') as g")[0]->g,
                        'company_id' => $company_id
                    ],
                    $params);
                
                // Relational Table: user_type_waste_collection_center 
                if (array_key_exists('userTypes',$feature['properties'])) {
                    $user_types = [];
                    foreach ($feature['properties']['userTypes'] as $value) {
                        $userType = UserType::where('company_id',$company_id)
                                                ->where('slug',$value)->get();
                        array_push($user_types,$userType[0]->id);
                    };
                    $waste_center->userTypes()->sync($user_types, false);
                }
                
                // Relational Table: trash_type_waste_collection_center 
                if (array_key_exists('trashTypes',$feature['properties'])) {
                    $trash_types = [];
                    foreach ($feature['properties']['trashTypes'] as $value) {
                        $trashType = TrashType::where('company_id',$company_id)
                                                ->where('slug',$value)->get();
                        array_push($trash_types,$trashType[0]->id);
                    };
                    $waste_center->trashTypes()->sync($trash_types, false);
                }
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }
}
