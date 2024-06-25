<?php

namespace App\Mail;

class TicketCreated extends BaseTicket
{

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build($view = 'emails.tickets.created')
    {
        parent::build($view);
    }
}
