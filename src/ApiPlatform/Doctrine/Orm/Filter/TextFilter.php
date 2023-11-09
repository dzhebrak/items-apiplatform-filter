<?php declare(strict_types=1);

namespace App\ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use App\ApiPlatform\Doctrine\Common\Filter\TextFilterInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class TextFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if (
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass)
        ) {
            return;
        }

        $values = $this->normalizeValues($value, $property);
        if (null === $values) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field, ] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::INNER_JOIN);
        }

        foreach ($values as $strategy => $textValue) {
            $this->addWhere(
                $queryBuilder,
                $queryNameGenerator,
                $alias,
                $field,
                $strategy,
                $textValue
            );
        }
    }

    protected function addWhere(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $alias, string $field, string $strategy, string $value): void
    {
        $valueParameter = $queryNameGenerator->generateParameterName($field);

        switch ($strategy) {
            case TextFilterInterface::STRATEGY_EXACT:
                $queryBuilder
                    ->andWhere(sprintf('LOWER(%s.%s) = LOWER(:%s)', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value)
                ;
                break;
            case TextFilterInterface::STRATEGY_PARTIAL:
                $queryBuilder
                    ->andWhere(sprintf('LOWER(%s.%s) LIKE LOWER(:%s)', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, '%'.$value.'%')
                ;
                break;
            default:
                throw new InvalidArgumentException(sprintf('Strategy %s does not exist.', $strategy));
        }
    }

    public function getDescription(string $resourceClass): array
    {
        $properties = $this->getProperties();

        if (null === $properties) {
            $properties = array_fill_keys($this->getClassMetadata($resourceClass)->getFieldNames(), null);
        }

        $description = [];

        foreach ($properties as $property => $strategy) {
            if (!$this->isPropertyMapped($property, $resourceClass)) {
                continue;
            }

            $description[(string)$property] = [
                'property'    => $property,
                'type'        => Type::BUILTIN_TYPE_STRING,
                'required'    => false,
                'description' => 'Filter text using an \<exact\> or \<partial\> strategy',
                'strategy'    => TextFilterInterface::STRATEGY_EXACT,
                'openapi'     => [
                    'example'         => 'mick',
                    'allowReserved'   => false,
                    'allowEmptyValue' => true,
                    'explode'         => false,
                ],
            ];
        }

        return $description;
    }

    private function normalizeValues(mixed $values, string $property): ?array
    {
        if (is_string($values)) {
            $values = [TextFilterInterface::STRATEGY_EXACT => $values];
        }

        $strategies = [TextFilterInterface::STRATEGY_EXACT, TextFilterInterface::STRATEGY_PARTIAL];

        foreach ($values as $strategy => $value) {
            if (!\in_array($strategy, $strategies, true) || empty($value)) {
                unset($values[$strategy]);
            }
        }

        if (empty($values)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('At least one valid strategy ("%s") is required for "%s" property', implode('", "', $strategies), $property)),
            ]);

            return null;
        }

        return $values;
    }
}
