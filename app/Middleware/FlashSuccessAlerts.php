<?php

namespace App\Middleware;

use App\Contracts\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Views\Twig;

class FlashSuccessAlerts implements MiddlewareInterface
{
    public function __construct(
        private readonly Twig             $twig,
        private readonly SessionInterface $session
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($alert = $this->session->getFlash('alert')){
            $this->twig->getEnvironment()->addGlobal('alert', $alert);
        }

        return $handler->handle($request);
    }
}