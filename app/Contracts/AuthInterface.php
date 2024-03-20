<?php

namespace App\Contracts;

use App\DTO\RegisteredUserDTO;
use App\Enum\LoginAttemptStatus;

interface AuthInterface
{
    public function user(): ?UserInterface;

    public function attemptLogin(array $credentials): LoginAttemptStatus;

    public function loginWith2FA(UserInterface $user): void;

    public function checkCredentials(array $credentials, UserInterface $user): bool;

    public function logOut(): void;

    public function register(RegisteredUserDTO $data): UserInterface;

    public function attemptLoginWith2FA(array $data): bool;

    public function isDifferent($newPassword , UserInterface $user): bool;
}