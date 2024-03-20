<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\AuthInterface;
use App\Contracts\ValidatorFactoryInterface;
use App\DTO\RegisteredUserDTO;
use App\Enum\LoginAttemptStatus;
use App\Exceptions\ValidationException;
use App\ResponseFormatter;
use App\Validators\UserLoginValidator;
use App\Validators\UserRegistrationValidator;
use App\Validators\UserTwoFactorLoginValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AuthController
{
    public function __construct(
        private readonly Twig                      $twig,
        private readonly AuthInterface             $auth,
        private readonly ValidatorFactoryInterface $validatorFactory,
        private readonly ResponseFormatter         $responseFormatter
    ) {
    }

    public function loginView(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'auth/login.twig');
    }

    public function registerView(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'auth/register.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $this->validatorFactory->make(UserLoginValidator::class)->validate($request->getParsedBody());

        $credentials = [
            'email'    => $data['email'],
            'password' => $data['password']
        ];

        $loginStatus = $this->auth->attemptLogin($credentials);

        if ($loginStatus === LoginAttemptStatus::FAILED) {
            throw new ValidationException(['password' => ['You have entered an invalid email or password']]);
        }

        if ($loginStatus === LoginAttemptStatus::TWO_FACTOR_AUTH) {
            return $this->responseFormatter->asJson($response, ['two_factor' => true]);
        }

        return $this->responseFormatter->asJson($response, []);
    }

    public function loginWith2FA(Request $request, Response $response): Response
    {
        // code and email

        $data = $this->validatorFactory->make(UserTwoFactorLoginValidator::class)->validate($request->getParsedBody());

        if (!$this->auth->attemptLoginWith2FA($data)){
            throw new ValidationException(['code' =>'Invalid code']);
        };


        // on success
        return $response->withStatus(302)->withHeader('location', '/');
    }

    public function register(Request $request, Response $response): Response
    {
        $data = $this->validatorFactory->make(UserRegistrationValidator::class)->validate($request->getParsedBody());

        $this->auth->register(
            new RegisteredUserDTO($data['name'], $data['email'], $data['password'])
        );

        return $response->withStatus(302)->withHeader('location', '/');
    }

    public function logout(Response $response): Response
    {
        $this->auth->logOut();

        return $response->withStatus(302)->withHeader('location', '/login');
    }

}