<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class MaxOpenTickets extends Constraint
{
    public string $message = 'Vous avez déjà {{ limit }} tickets ouverts ou en cours. Veuillez en fermer avant d\'en créer un nouveau.';
    public int $max = 10;

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
