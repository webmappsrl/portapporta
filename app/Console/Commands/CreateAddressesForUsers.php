<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Address;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Providers\CurlServiceProvider;

class CreateAddressesForUsers extends Command
{
    protected $signature = 'addresses:create';

    protected $description = 'Create addresses for all users using their location';

    public function handle()
    {
        $users = User::all();

        foreach ($users as $user) {
            if (isset($user->location)) {
                $address = $this->getAddressFromNominatim($user->location);
                Address::create([
                    'user_id' => $user->id,
                    'zone_id' => $user->zone_id,
                    'user_type_id' => $user->user_type_id,
                    'address' => $address,
                    'location' => $user->location,
                ]);
            }
        }

        $this->info('Addresses created for all users successfully.');
    }

    public function getAddressFromNominatim($location)
    {
        $res = '';
        $g = json_decode(DB::select("SELECT st_asgeojson('$location') as g")[0]->g);
        $x = $g->coordinates[0];
        $y = $g->coordinates[1];
        $url = "https://nominatim.openstreetmap.org/reverse?lat=$y&lon=$x&format=json";
        $response = $this->curlRequest($url);
        if ($response) {
            if (array_key_exists('display_name', $response)) {
                $res = $response['display_name'];
            }
        }

        return $res;
    }
    public function curlRequest($url)
    {
        $curl = app(CurlServiceProvider::class);
        Log::info('Excecuting CURL service provider with: ' . $url);
        try {
            $obj = $curl->exec($url);
            Log::info('CURL executed with success.');
            return json_decode($obj, true);
        } catch (Exception $e) {
            Log::info('Error Excecuting CURL: ' . $e);
        }
    }
}
