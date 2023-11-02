<?php

namespace App\Console\Commands;

use Exception;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Providers\CurlServiceProvider;
//  

/**
 * ersu api = apiersu.netseven.it/users.json id 1
 * asmiu api = apiasmiu.webmapp.it/users.json id 2
 * rea api = apirea.webmapp.it/users.json id 3
 * esa api = apiesa.netseven.it/users.json id 4
 */
class importUserFromApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:users {url} {company_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users by url';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $skyppedUsers = [];

        try {
            $url = $this->argument('url');
            $curl = app(CurlServiceProvider::class);
            $obj = $curl->exec($url);
            $response = json_decode($obj, true);
        } catch (Exception $e) {
            Log::error("missing url or malformed json");
            return 0;
        }
        try {
            $this->info("importing users from " . $url);
            $userProgressBar = $this->output->createProgressBar(count($response));
            foreach ($response as $email => $user) {
                $userDB = User::where('email', $email)->first();
                if (!is_null($userDB)) {
                    $msg = $email . ": SKIPPED already exist.";
                    array_push($skyppedUsers, $msg);
                    $userProgressBar->advance();
                } else {
                    try {
                        $usr = User::create([
                            'name' => $email,
                            'email' => $email,
                            'password' => bcrypt($user['code']),
                            'app_company_id' => $this->argument('company_id'),
                        ]);
                        $usr->email_verified_at = \Carbon\Carbon::parse($user['created_at'])->format('Y-m-d h:m:s');
                        $usr->save();
                        $userProgressBar->advance();
                    } catch (Exception $e) {
                        array_push($skyppedUsers, "user with email: " . $email . "ERROR " . $e);
                    }
                }
            }
        } catch (Exception $e) {
            Log::error("missing url or malformed json");
            return 0;
        }
        $userProgressBar->finish();
        $this->info(PHP_EOL . "import completed");
        return 1;
    }
}