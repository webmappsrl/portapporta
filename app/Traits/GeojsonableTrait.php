<?php
namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Providers\CurlServiceProvider;

trait GeojsonableTrait {

    public function getGeojsonGeometry() {
        if(is_null($this->geometry)) {
            return '[]';
        }
        if(empty($this->geometry)) {
            return '[]';
        }
        return DB::SELECT("SELECT ST_AsGeoJSON('$this->geometry') as g")[0]->g;
    }

    /**
     * It uses the Curl Service Provider class and excecutes a curl.
     * 
     * @param string the complete url.
     * @return array The result of curl. 
     */
    public function curlRequest($url)
    {
        $curl = app(CurlServiceProvider::class);
        Log::info('Excecuting CURL service provider with: '.$url);
        try{
            $obj = $curl->exec($url);
            Log::info('CURL executed with success.');
            return json_decode($obj,true);
        } catch (Exception $e) {
            Log::info('Error Excecuting CURL: '.$e);
        }
    }
}