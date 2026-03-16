<?php

namespace App\Validator;

use App\Entity\Ticket;
use App\Repository\TicketRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class MaxOpenTicketsValidator extends ConstraintValidator
{
    public function __construct(
        private TicketRepository $ticketRepository,
        private Security $security,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MaxOpenTickets) {
            throw new UnexpectedTypeException($constraint, MaxOpenTickets::class);
        }

        if (!$value instanceof Ticket) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user) {
            return;
        }

        $count = $this->ticketRepository->countOpenTicketsByClient($user);

        if ($count >= $constraint->max) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ limit }}', (string) $constraint->max)
                ->addViolation();
        }
    }
}
