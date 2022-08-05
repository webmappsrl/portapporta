<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketCreated extends Mailable
{
    use Queueable, SerializesModels;

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
    public function __construct(Ticket $ticket,$company)
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
        return $this->from('noreply@webmapp.it', "Nuovo Ticket $company_name")
                ->subject("PortAPPorta - $company_name: $trash_type")
                ->view('emails.tickets.created');
    }
}
