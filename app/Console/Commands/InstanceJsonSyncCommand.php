<?php

namespace App\Console\Commands;

use App\Helpers\InstanceJsonSyncCommandHelper;
use App\Models\TrashType;
use Exception;
use Illuminate\Console\Command;

class InstanceJsonSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pap:sync {company_id} {endpoint : e.g. https://apiesa.webmapp.it/}';

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
    }
    
    protected function syncTipiRifiuto($company_id,$endpoint){
        $tipi_rifiuto_url = $endpoint . '/data/tipi_rifiuto.json';
        $response = json_decode(InstanceJsonSyncCommandHelper::importerCurl($tipi_rifiuto_url),true);
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
        $tipi_rifiuto_url = $endpoint . '/data/tipi_rifiuto2.json';
        $response = json_decode(InstanceJsonSyncCommandHelper::importerCurl($tipi_rifiuto_url),true);
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
}
