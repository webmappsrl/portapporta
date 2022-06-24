<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class exportEmailUsersFromApiEsa extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:users
{url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create email list users by url';


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $url = $this->argument('url');
            $file = $url . '_emails.txt';
            $users = json_decode(file_get_contents($url), true);
            $emails = [];
            foreach ($users as $email => $user) {
                array_push($emails, $email);
            }
            Storage::disk('local')->put($file,  implode(",", $emails));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return 0;
        }
    }
}
