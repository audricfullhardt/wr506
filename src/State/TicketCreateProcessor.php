<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Ticket;
use App\Entity\User;
use App\Event\TicketCreatedEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TicketCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Ticket) {
            $user = $this->security->getUser();
            if ($user instanceof User) {
                $data->setClient($user);
            }
        }

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        if ($data instanceof Ticket) {
            $this->eventDispatcher->dispatch(new TicketCreatedEvent($data));
        }

        return $result;
    }
}
