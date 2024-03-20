<?php

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\UserInterface;
use App\Entity\User2FACode;

class User2FACodeService
{
    public function __construct(private readonly EntityManagerServiceInterface $entityManagerService)
    {
    }

    // Generate code/Get existent
    public function generate(UserInterface $user): User2FACode
    {
        // check if a two-factor code exists and is still valid
        $user2FACode = $this->getByUser($user);
        $now = new \DateTime();

        if ($user2FACode && $user2FACode->getExpirationDate() > $now){
            return $user2FACode;
        }

        if (! $user2FACode){
            $user2FACode = new User2FACode();
        }

        $code = (string) random_int(100000, 999999); // 6 digits code

        $user2FACode->setCode($code);
        $user2FACode->setExpirationDate(new \DateTime('+10 minutes'));
        $user2FACode->setUser($user);
        $user2FACode->setEnabled(true);
        $this->entityManagerService->sync($user2FACode);

        return $user2FACode;
    }

    public function getByUser(UserInterface $user): ?User2FACode
    {
        return $this->entityManagerService->getRepository(User2FACode::class)->findOneBy(['user' => $user]);
    }

    public function verify(UserInterface $user, string $code): bool
    {
        /** @var  User2FACode $user2FACode */
        $user2FACode = $this->entityManagerService->getRepository(User2FACode::class)
            ->findOneBy([
                'user' => $user,
                'code' => $code,
                'enabled' => true,
            ]);

        if (! $user2FACode){
            return false;
        }

        $now = new \DateTime();

        if ($user2FACode->getExpirationDate() < $now){
            return false;
        }

        return true;
    }
}