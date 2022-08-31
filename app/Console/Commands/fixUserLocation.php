<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class fixUserLocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:userLocation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fix location';


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $users = User::all();
            foreach ($users as  $user) {
                if (!is_null($user->location)) {

                    $g = json_decode(DB::select("SELECT st_asgeojson('$user->location') as g")[0]->g);
                    $x = $g->coordinates[0];
                    $y = $g->coordinates[1];
                    echo $x;
                    echo $y;
                    if ($x > $y) {
                        echo 'need change';
                        $newG = DB::select("SELECT ST_GeomFromText('POINT($y $x)') as g")[0]->g;
                        $user->location = $newG;
                        $user->save();
                    }
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return 0;
        }
    }
}
