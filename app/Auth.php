<?php

namespace App;

use App\Contracts\AuthInterface;
use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\SessionInterface;
use App\Contracts\UserInterface;
use App\Contracts\UserServiceInterface;
use App\DTO\RegisteredUserDTO;
use App\Entity\User;
use App\Enum\LoginAttemptStatus;
use App\Mailing\ConfirmationEmail;
use App\Mailing\TwoFactorEmail;
use App\Services\HashService;
use App\Services\User2FACodeService;

class Auth implements AuthInterface
{
    private ?UserInterface $user = null;

    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly SessionInterface     $session,
        private readonly ConfirmationEmail    $confirmationEmail,
        private readonly TwoFactorEmail $twoFactorEmail,
        private readonly User2FACodeService $user2FACodeService,
        private readonly HashService $hashService
    ) {
    }

    public function user(): ?UserInterface
    {
        // get the user if it's already authenticated and set in the login
        if ($this->user !== null) {
            return $this->user;
        }

        $userId = $this->session->get('user');

        if (! $userId) {
            return null;
        }

        return $this->userService->getById($userId);
    }

    public function attemptLogin(array $credentials): LoginAttemptStatus
    {
        $user = $this->userService->getByCredentials($credentials);

        // set this for the middlewares res. the property here
        $this->user = $user;

        if (! $user || ! $this->checkCredentials($credentials, $user)) {
            return LoginAttemptStatus::FAILED;
        }

        if ($user->isTwoFactorEnabled()) {

            $this->loginWith2FA($user);

            return LoginAttemptStatus::TWO_FACTOR_AUTH;
        }

        $this->logIn($user);

        return LoginAttemptStatus::SUCCESS;
    }

    public function isDifferent($newPassword , UserInterface $user): bool
    {
        // hash here
        return !password_verify($newPassword, $user->getPassword());
    }

    public function checkCredentials(array $credentials, UserInterface $user): bool
    {
        return password_verify($credentials['password'], $user->getPassword());
    }

    public function logOut(): void
    {
        $this->session->forget('user');
        $this->user = null;
    }

    public function register(RegisteredUserDTO $data): UserInterface
    {
        $user = $this->userService->createUser($data);

        $this->logIn($user);

        $this->confirmationEmail->send($user);

        return $user;
    }

    public function logIn(UserInterface $user): void
    {
        $this->session->regenerate();

        $this->session->set('user', $user->getId());
    }

    public function loginWith2FA(UserInterface $user): void
    {
        $this->session->regenerate();

        $this->session->set('user_2FA', $user->getId());

        $this->twoFactorEmail->send($user);
    }

    public function attemptLoginWith2FA(array $data): bool
    {
        $userId = $this->session->get('user_2FA');
        $email = $data['email'];
        $code = $data['code'];

        if (! $userId) {
            return false;
        }

        $user = $this->userService->getById($userId);

        // if user doesn't exist or the verification is done on a different email rather than the one on the login attempt
        if (! $user || $user->getEmail() !== $email){
            return false;
        }


        if (! $this->user2FACodeService->verify($user, $code)){
            return false;
        }

        $this->session->forget('user_2FA');

        $this->logIn($user);

        return true;
    }
}