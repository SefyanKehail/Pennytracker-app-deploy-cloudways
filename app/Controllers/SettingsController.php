<?php

namespace App\Controllers;

use App\Contracts\AuthInterface;
use App\Contracts\SessionInterface;
use App\Contracts\UserInterface;
use App\Contracts\ValidatorFactoryInterface;
use App\DTO\UserProfileDTO;
use App\Exceptions\ValidationException;
use App\Services\EntityManagerService;
use App\Services\PasswordResetService;
use App\Services\SettingsService;
use App\Services\User2FACodeService;
use App\Validators\ChangePasswordValidator;
use App\Validators\Toggle2FAValidator;
use App\Validators\UpdateProfileValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;


class SettingsController
{
    public function __construct(
        private readonly Twig                      $twig,
        private readonly ValidatorFactoryInterface $validatorFactory,
        private readonly AuthInterface             $auth,
        private readonly SettingsService           $settingsService,
        private readonly EntityManagerService      $entityManagerService,
        private readonly User2FACodeService        $user2FACodeService,
        private readonly PasswordResetService      $passwordResetService,
        private readonly SessionInterface          $session,
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        /** @var UserInterface $user */
        $user = $request->getAttribute('user');

        $profile = [
            'name'   => $user->getName(),
            'email'  => $user->getEmail(),
            'has2FA' => $user->isTwoFactorEnabled()
        ];

        return $this->twig->render($response, 'settings/profile.twig', ['profile' => $profile]);
    }

    public function authentication(Request $request, Response $response): Response
    {
        /** @var UserInterface $user */
        $user = $request->getAttribute('user');

        $user2FACode = $this->user2FACodeService->getByUser($user);

        $expiration = $user2FACode->getExpirationDate()->getTimestamp() - time();

        $isCodeEnabled = $user2FACode->isEnabled();

        return $this->twig->render($response,
            'settings/authentication.twig',
            [
                'isTwoFactorEnabled' => $user->isTwoFactorEnabled(),
                'expiration'         => $expiration,
                'isCodeEnabled'      => $isCodeEnabled
            ]
        );
    }

    public function help(Response $response): Response
    {
        return $this->twig->render($response, 'settings/help.twig');
    }

    public function updateProfile(Request $request, Response $response): Response
    {
        $data = $this->validatorFactory->make(UpdateProfileValidator::class)->validate($request->getParsedBody());

        if (! $this->auth->checkCredentials($data, $user = $request->getAttribute('user'))) {
            throw new ValidationException(['password' => ['Invalid password']]);
        }

        $user = $this->settingsService->updateProfile(
            $user,
            new UserProfileDTO($user->getEmail(), $data['name'])
        );

        $this->entityManagerService->sync($user);

        return $response;
    }

    public function changePassword(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        $data = $this->validatorFactory->make(ChangePasswordValidator::class)->validate(
            $request->getParsedBody() + ['user' => $user]
        );

        if (! $this->auth->checkCredentials($data, $user)) {
            throw new ValidationException(['password' => ['Invalid password']]);
        }

        $user = $this->passwordResetService->changePasswordNoToken(
            $user,
            $data['newPassword']
        );

        $this->entityManagerService->sync($user);

        $this->session->flash('alert', [
            'message' => "Your password has been successfully updated!"
        ]);

        $this->auth->logOut();

        return $response;
    }

    public function toggle2FA(Request $request, Response $response): Response
    {
        $data = $this->validatorFactory->make(Toggle2FAValidator::class)->validate($request->getParsedBody());

        if (! $this->auth->checkCredentials($data, $user = $request->getAttribute('user'))) {
            throw new ValidationException(['password' => ['Invalid password']]);
        }

        $user = $this->settingsService->toggle2FA($user);

        $this->entityManagerService->sync($user);

        $this->session->flash('alert', [
            'message' => "Two factor authentication settings has been updated!"
        ]);

        $this->auth->logOut();

        return $response;
    }

    public function disableCode(Request $request, Response $response): Response
    {
        /** @var UserInterface $user */
        $user = $request->getAttribute('user');

        $user2FACode = $this->user2FACodeService->getByUser($user);

        $user2FACode = $this->settingsService->disableCode($user2FACode);

        $this->entityManagerService->sync($user2FACode);

        return $response;
    }
}