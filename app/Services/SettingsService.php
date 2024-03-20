<?php

namespace App\Services;

use App\Contracts\UserInterface;
use App\DTO\UserProfileDTO;
use App\Entity\User2FACode;

class SettingsService
{
    public function __construct()
    {
    }

    public function updateProfile(UserInterface $user, UserProfileDTO $userProfileDTO): UserInterface
    {
        $user->setEmail($userProfileDTO->email)
             ->setName($userProfileDTO->name);

        return $user;
    }

    public function toggle2FA(UserInterface $user): UserInterface
    {
        return $user->setTwoFactorEnabled(! $user->isTwoFactorEnabled());
    }

    public function disableCode(User2FACode $user2FACode): User2FACode
    {
        return $user2FACode->setEnabled(false);
    }

}