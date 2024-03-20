<?php

namespace App\Controllers;

use App\Contracts\SessionInterface;
use App\Contracts\UserServiceInterface;
use App\Contracts\ValidatorFactoryInterface;
use App\Exceptions\ValidationException;
use App\Mailing\PasswordResetEmail;
use App\Services\EntityManagerService;
use App\Services\PasswordResetService;
use App\Validators\PasswordResetValidator;
use App\Validators\SendPasswordResetEmailValidator;
use Doctrine\ORM\Mapping\Entity;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class PasswordResetController
{
    public function __construct(
        private readonly Twig                      $twig,
        private readonly ValidatorFactoryInterface $validatorFactory,
        private readonly UserServiceInterface      $userService,
        private readonly PasswordResetEmail        $passwordResetEmail,
        private readonly PasswordResetService      $passwordResetService,
        private readonly SessionInterface $session
    ) {
    }

    public function index(Response $response): Response
    {
        return $this->twig->render($response, 'auth/forgot_password.twig');
    }

    public function sendPasswordResetEmail(Request $request, Response $response): Response
    {
        // get data and validate it
        $data = $this->validatorFactory->make(SendPasswordResetEmailValidator::class)->validate($request->getParsedBody(
        )
        );

        // check if the email exists -> deactivate past resets then send
        if ($this->userService->getByCredentials($data)) {
            // deactivate past tokens related to this email ( i)
            $this->passwordResetService->deactivatePastTokens($data['email']);

            $passwordReset = $this->passwordResetService->generate($data['email']);

            // send email
            $this->passwordResetEmail->send($passwordReset);
        }

        return $response;
    }

    // get request with signed url
    public function resetPasswordForm(Response $response, array $args): Response
    {
        $passwordReset = $this->passwordResetService->getByToken($args['token']);

        if (! $passwordReset) {
            return $response->withStatus(302)->withHeader('location', '/');
        }

        return $this->twig->render($response, 'auth/reset_password.twig', [
            'token' => $args['token'],
            'hash'  => $args['hash']
        ]);
    }

    public function resetPassword(Request $request, Response $response, array $args): Response
    {
        $token = $args['token'];
        $hash  = $args['hash'];

        $passwordReset = $this->passwordResetService->getByToken($token);

        if (! $passwordReset || ! $user = $this->userService->getByCredentials(['email' => $passwordReset->getEmail()]
            )) {
            throw new ValidationException(['confirmPassword' => 'Unexpected Error']);
        }

        // requested email != email from token data
        if (! hash_equals($hash, sha1($passwordReset->getEmail()))) {
            throw new ValidationException(['confirmPassword' => 'Unexpected Error']);
        }

        $data = $this->validatorFactory->make(PasswordResetValidator::class)
                                       ->validate($request->getParsedBody() + ['user' => $user]);

        $password = $data['password'];

        // update
        $this->passwordResetService->changePasswordWithToken($user, $password);

        $this->session->flash('alert', [
            'message' => "Your password has been reset successfully! Please login with your new password."
        ]);

        return $response;
    }
}