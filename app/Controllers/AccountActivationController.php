<?php

namespace App\Controllers;

use App\Contracts\AuthInterface;
use App\Contracts\SessionInterface;
use App\Contracts\UserInterface;
use App\Contracts\UserServiceInterface;
use App\Mailing\ConfirmationEmail;
use App\ResponseFormatter;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AccountActivationController
{
    public function __construct(
        private readonly Twig                 $twig,
        private readonly UserServiceInterface $userService,
        private readonly ConfirmationEmail    $confirmationEmail,
        private readonly SessionInterface     $session,
        private readonly AuthInterface        $auth,
        private readonly ResponseFormatter    $responseFormatter
    ) {
    }

    public function index(Response $response): Response
    {
        return $this->twig->render($response, 'auth/activation_alert.twig');
    }

    public function activate(Request $request, Response $response, array $args): Response
    {
        // Check if the connected User is actually the one with the account activation request
        /** @var UserInterface $user */
        $user  = $request->getAttribute('user');
        $email = $user->getEmail();
        $id    = $user->getId();

        $idFromRequest = (int)$args['id'];
        // email hash
        $hashFromRequest = $args['hash'];

        if (! $id === $idFromRequest || ! hash_equals($hashFromRequest, sha1($email))) {
            return $this->twig->render($response, 'alerts/failed_account_activation.twig');
        }

        if (! $user->getVerifiedAt()) {
            $this->userService->activateUser($user);
        }

        $this->session->flash('alert', [
            'message' => "Your account has been successfully activated. You're now ready to explore our platform!"
        ]);

        return $response->withStatus(302)->withHeader('location', '/');
    }

    public function sendActivationEmail(Request $request, Response $response): Response
    {
        /** @var UserInterface $user */
        $user = $request->getAttribute('user');

        if ($user->getVerifiedAt()) {
            return $this->responseFormatter->asJson($response->withStatus(302), []);
        }

        $this->confirmationEmail->send($user);

        return $response;
    }

}