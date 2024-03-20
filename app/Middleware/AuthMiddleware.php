<?php

namespace App\Middleware;

use App\Contracts\AuthInterface;
use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\SessionInterface;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Views\Twig;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly EntityManagerServiceInterface $entityManagerService,
        private readonly ResponseFactoryInterface      $responseFactory,
        private readonly SessionInterface              $session,
        private readonly AuthInterface                 $auth,
        private readonly Twig                          $twig
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->session->has('user')) {
            return $this->responseFactory->createResponse(302)->withHeader('location', '/login');
        }

        $user = $this->auth->user();

        $this->twig->getEnvironment()->addGlobal(
            'auth',
            [
                'id'           => $user->getId(),
                'name'         => $user->getName(),
            ]
        );

        $this->entityManagerService->getFilters()->enable('user')->setParameter('user_id', $user->getId());

        return $handler->handle($request->withAttribute('user', $user));
    }
}