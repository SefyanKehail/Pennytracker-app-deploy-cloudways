<?php

namespace App\Contracts;

use App\DTO\RegisteredUserDTO;

interface UserServiceInterface
{
    public function getById(int $userId): ?UserInterface;

    public function getByCredentials(array $credentials): ?UserInterface;

    public function createUser(RegisteredUserDTO $data): UserInterface;

    public function activateUser(UserInterface $user): void;

}