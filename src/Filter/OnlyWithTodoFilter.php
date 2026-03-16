<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class OnlyWithTodoFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if ($property !== 'onlyWithTodo') {
            return;
        }

        if ($value !== 'true' && $value !== '1') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $ticketAlias = $queryNameGenerator->generateJoinAlias('tickets');

        $queryBuilder
            ->innerJoin(sprintf('%s.tickets', $alias), $ticketAlias)
            ->andWhere(sprintf('%s.status IN (:activeStatuses)', $ticketAlias))
            ->setParameter('activeStatuses', ['ouvert', 'en_cours'])
            ->distinct();
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'onlyWithTodo' => [
                'property' => null,
                'type' => 'bool',
                'required' => false,
                'description' => 'Filtrer uniquement les catégories ayant des tickets ouverts ou en cours.',
                'openapi' => [
                    'allowEmptyValue' => true,
                ],
            ],
        ];
    }
}
