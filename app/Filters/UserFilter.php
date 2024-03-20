<?php

namespace App\Filters;

use App\Contracts\FilterableByUserInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class UserFilter extends SQLFilter
{

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (! $targetEntity->getReflectionClass()->implementsInterface(FilterableByUserInterface::class)){
            return '';
        }
        return $targetTableAlias . '.user_id = ' . $this->getParameter('user_id');
    }
}