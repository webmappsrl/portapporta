<?php

namespace App\Jobs;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\FirebaseNotificationsService;


class ProcessTicket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $ticket;
    protected $event;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Ticket $ticket, $event)
    {
        $this->ticket = $ticket;
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = $this->ticket->user;
        if ($user->hasRole('vip')) {
            $address = $this->ticket->address;
            $message = '';
            if (isset($address)) {
                $message = $address->address . ', ' . $address->house_number;
            }
            Log::info("Processing push VIP notifications: {$this->ticket->title}");
            Log::info("app company id: {$this->ticket->company_id}");
            // send push notification to dustyman
            if ($this->event === 'created') {
                Log::info("CREATED");
                try {
                    try {
                        $dustyManUsers = User::whereNotNull('fcm_token')->where('app_company_id', $user->app_company_id)->role('dusty_man')->get();
                    } catch (\Exception $e) {
                        Log::info("error " . json_encode($e));
                    }
                    Log::info("push notification filtering process");
                    $fcmTokens =  $dustyManUsers->pluck('fcm_token')->toArray();
                    Log::info("notification send to users: " . json_encode($dustyManUsers->pluck('name')->toArray()));
                    Log::info("token numbers: " . json_encode($fcmTokens));
                    $title = __('Raccolta VIP: ') . $user->name;
                    try {


                        $res = FirebaseNotificationsService::getService()->sendNotification(
                            [
                                'title' => $title,
                                'body' => $message
                            ],
                            $fcmTokens,
                            [
                                'page_on_click' => '/dusty-man-reports',
                                'ticket_id' => $this->ticket->id
                            ]
                        );
                    } catch (\Exception $e) {
                        Log::info("push error" . $e->getMessage());
                    }
                } catch (\Exception $e) {
                    Log::info("push error" . $e->getMessage());
                }
                //send push notification to vip
            } elseif ($this->event === 'updated') {
                Log::info("UPDATED");
                if ($this->ticket->status === TicketStatus::Execute) {
                    $vipFcmToken = [$user->fcm_token];
                    $title = __('Raccolta VIP eseguita');
                    try {
                        $res = FirebaseNotificationsService::getService()->sendNotification(
                            ['title' => $title, 'body' => $message],
                            $vipFcmToken,
                            [
                                'page_on_click' => '/reports',
                                'ticket_id' => $this->ticket->id
                            ]
                        );
                    } catch (\Exception $e) {
                        Log::info("push error" . $e->getMessage());
                    }
                }
            }
        }
    }
}
