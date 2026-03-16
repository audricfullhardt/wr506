<?php

namespace App\Event;

use App\Entity\Ticket;

class TicketCreatedEvent
{
    public function __construct(
        public readonly Ticket $ticket,
    ) {}
}
