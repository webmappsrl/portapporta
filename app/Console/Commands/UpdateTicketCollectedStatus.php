<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateTicketStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:update-collected';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status of tickets collected for more than one day to done';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $daysAgo = Carbon::now();
        for ($i = 0; $i < 3; $i++) {
            $daysAgo->subDay();
            if ($daysAgo->isWeekend()) {
                $i--;
            }
        }

        $tickets = Ticket::where('status', 'collected')
            ->where('updated_at', '<', $daysAgo)
            ->get();

        foreach ($tickets as $ticket) {
            $ticket->status = 'done';
            $ticket->saveQuietly();
        }

        $this->info("Tickets updated successfully.");

        return 0;
    }
}