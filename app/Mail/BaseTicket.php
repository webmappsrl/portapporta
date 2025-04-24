<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BaseTicket extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * The ticket instance.
     *
     * @var \App\Models\Ticket
     */
    public $ticket;
    public $company;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Ticket $ticket, $company)
    {
        $this->ticket = $ticket;
        $this->company = $company;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build($view)
    {
        $company_name =  $this->company->name;
        $ticket_type = $this->ticket->ticket_type;
        $ticket_id = $this->ticket->id;
        $ticket_emails = config('mail.mailers.smtp.username', 'noreply-portapporta@webmapp.it');
        if (!empty($this->company->ticket_email) && is_array(explode(',', $this->company->ticket_email)) && count(explode(',', $this->company->ticket_email)) > 0) {
            $ticket_emails = explode(',', $this->company->ticket_email)[0];
        }
        $isVip = $this->ticket->user->hasRole('vip');
        $vipSuffix = $isVip ? 'VIP' : '';

        $email = $this->from($ticket_emails, "$vipSuffix Ticket $company_name - ($ticket_id)")
            ->subject("PortAPPorta - $company_name: $ticket_type")
            ->view($view);

        if (!empty($this->ticket->image)) {
            $imageData = explode(';base64,', $this->ticket->image);
            if (count($imageData) == 2) {
                $decodedImage = base64_decode($imageData[1]);
                $email->attachData($decodedImage, 'immagine.jpeg', [
                    'mime' => 'image/jpeg',
                ]);
            }
        }

        return $email;
    }
}
