<?php

namespace App\Middleware;

use App\Contracts\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Views\Twig;

class FlashErrorsAndDataMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Twig             $twig,
        private readonly SessionInterface $session
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($errors = $this->session->getFlash('errors')) {
            $twigEnv = $this->twig->getEnvironment();
            $twigEnv->addGlobal('errors', $errors);
            $twigEnv->addGlobal('old', $this->session->getFlash('old'));
        }

        return $handler->handle($request);
    }
}