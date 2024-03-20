<?php

namespace App\Contracts;

use Doctrine\ORM\EntityManagerInterface;

/**
 * @mixin EntityManagerInterface
 */

interface EntityManagerServiceInterface
{
    public function sync($entity = null): void;

    public function delete($entity, bool $sync = false): void;

    public function __call(string $name, array $arguments): mixed;

    public function clear(?string $entityName = null): void;

}