<?php

namespace App\EventListener;

use App\Event\TicketCreatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: TicketCreatedEvent::class)]
class TicketNotificationListener
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(TicketCreatedEvent $event): void
    {
        $ticket = $event->ticket;

        $this->logger->info('Nouveau ticket créé', [
            'ticket_uuid' => $ticket->getUuid()?->toRfc4122(),
            'title' => $ticket->getTitle(),
            'client' => $ticket->getClient()?->getEmail(),
            'priority' => $ticket->getPriority(),
            'category' => $ticket->getCategory()?->getName(),
        ]);
    }
}
