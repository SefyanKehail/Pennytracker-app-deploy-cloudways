<?php

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\UserInterface;
use App\Contracts\UserServiceInterface;
use App\DTO\RegisteredUserDTO;
use App\Entity\User;

class UserService implements UserServiceInterface
{
    public function __construct(private readonly EntityManagerServiceInterface $entityManagerService,
    private readonly HashService $hashService)
    {
    }

    public function getById(int $userId): ?UserInterface
    {
        return $this->entityManagerService->getRepository(User::class)->find($userId);
    }

    public function getByCredentials(array $credentials): ?UserInterface
    {
        return $this->entityManagerService->getRepository(User::class)->findOneBy(['email' => $credentials['email']]);
    }

    public function createUser(RegisteredUserDTO $data): UserInterface
    {
        $user = new User();

        $user->setEmail($data->email);
        $user->setName($data->name);
        $user->setPassword($this->hashService->passwordHash($data->password));

        $this->entityManagerService->sync($user);

        return $user;
    }

    public function activateUser(UserInterface $user): void
    {
        $user->setVerifiedAt(new \DateTime());

        $this->entityManagerService->sync($user);
    }
}