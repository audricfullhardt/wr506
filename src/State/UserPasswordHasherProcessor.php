<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordHasherProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof User && $data->getPassword()) {
            if (!str_starts_with($data->getPassword(), '$2y$')) {
                $data->setPassword(
                    $this->passwordHasher->hashPassword($data, $data->getPassword())
                );
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
