<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketCreated extends Mailable
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
    public function build()
    {
        $company_name =  $this->company->name;
        $trash_type = $this->ticket->ticket_type;
        $trash_id = $this->ticket->id;
        $ticket_emails = ['noreply@webmapp.it'];
        if (!empty($this->company->ticket_email) && is_array(explode(',', $this->company->ticket_email)) && count(explode(',', $this->company->ticket_email)) > 0) {
            $ticket_emails = explode(',', $this->company->ticket_email)[0];
        }

        return $this->from($ticket_emails, "Nuovo Ticket $company_name - ($trash_id)")
                ->subject("PortAPPorta - $company_name: $trash_type")
                ->view('emails.tickets.created');
    }
}
