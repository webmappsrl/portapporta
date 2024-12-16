<?php

namespace App\Mail;

class TicketDeleted extends BaseTicket
{

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build($view = 'emails.tickets.deleted')
    {
        parent::build($view);
    }
}
