<?php

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use http\Exception\BadMessageException;

class EntityManagerService implements EntityManagerServiceInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }


    public function sync($entity = null): void
    {
        if ($entity) {
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();
    }

    public function delete($entity, bool $sync = false): void
    {
        $this->entityManager->remove($entity);

        if ($sync){
            $this->sync();
        }
    }

    public function clear(?string $entityName = null): void
    {
        if (! $entityName) {
            $this->entityManager->clear();
        }

        $unitOfWork = $this->entityManager->getUnitOfWork();
        $entities = $unitOfWork->getIdentityMap()[$entityName] ?? [];

        foreach ($entities as $entity) {
            $this->entityManager->detach($entity);
        }
    }


    public function __call(string $name, array $arguments): mixed
    {
        // check if the method actually exists

        if (method_exists($this->entityManager, $name)){
            return call_user_func_array([$this->entityManager, $name], $arguments);
        };

        // throw an exception
        Throw new BadMessageException('Call to undefined method " '. $name . '" ');
    }

}