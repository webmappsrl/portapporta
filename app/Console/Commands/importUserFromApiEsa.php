<?php

namespace App\Console\Commands;

use Exception;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
//  

/**
 * import esa:
 * php artisan import:user http://apiesa.netseven.it/users.json
 */
class importUserFromApiEsa extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:users {url}';

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
            $users = json_decode(file_get_contents($url), true);
        } catch (Exception $e) {
            Log::error("missing url or malformed json");
            return 0;
        }
        foreach ($users as $email => $user) {
            $userDB = User::where('email', $email)->first();
            if (!is_null($userDB)) {
                $msg = $email . ": SKIPPED already exist.";
                array_push($skyppedUsers,  $msg);
            } else {
                try {
                    $usr = User::create([
                        'name' => $email,
                        'email' => $email,
                        'password' => bcrypt($user['code']),
                    ]);
                    $usr->email_verified_at = \Carbon\Carbon::parse($user['created_at'])->format('Y-m-d h:m:s');
                    $usr->save();
                    Log::info("user with " . $email . " ADDED.");
                } catch (Exception $e) {
                    array_push($skyppedUsers, "user with email: " . $email . "ERROR " . $e);
                }
            }
        }
        if (count($skyppedUsers) > 0) {
            Log::info("the following user are skipped:");
            foreach ($skyppedUsers as $skyppedUser) {
                Log::info($skyppedUser);
            }
        }
        return 0;
    }
}
