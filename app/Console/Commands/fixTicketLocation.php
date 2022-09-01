<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Ticket;
use App\Providers\CurlServiceProvider;

class fixTicketLocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:ticketLocation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fix ticket location';

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
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $tickets = Ticket::all();
            foreach ($tickets as  $ticket) {
                if (!is_null($ticket->geometry)) {

                    $g = json_decode(DB::select("SELECT st_asgeojson('$ticket->geometry') as g")[0]->g);
                    $x = $g->coordinates[0];
                    $y = $g->coordinates[1];
                    echo $x;
                    echo $y;
                    if ($x > $y) {
                        echo 'need change';
                        $newG = DB::select("SELECT ST_GeomFromText('POINT($y $x)') as g")[0]->g;
                        $ticket->geometry = $newG;
                    }
                    if (is_null($ticket->location_address)) {
                        $g = json_decode(DB::select("SELECT st_asgeojson('$ticket->geometry') as g")[0]->g);
                        $x = $g->coordinates[0];
                        $y = $g->coordinates[1];
                        $url = "https://nominatim.openstreetmap.org/reverse?lat=$y&lon=$x&format=json";
                        $response = $this->curlRequest($url);
                        if ($response) {
                            if (array_key_exists('display_name', $response)) {
                                $ticket->location_address = $response['display_name'];
                            }
                            if (array_key_exists('error', $response)) {
                                $ticket->location_address = $response['error'];
                            }
                        }
                        echo 'update location address';
                    }
                    $ticket->save();
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return 0;
        }
    }
}
