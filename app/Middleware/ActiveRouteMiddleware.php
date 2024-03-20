<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

class ActiveRouteMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Twig $twig)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        if (empty($route)) {
            throw new \RuntimeException('Retrieving route info failed');
        }

        $name = $route->getName() ?? '';
        $routeParam = $route->getArgument('dateRange') ?? '';


        if ($name !== ''){
            $this->twig->getEnvironment()->addGlobal('activeRoute', $name);
            $this->twig->getEnvironment()->addGlobal('dateRange', $routeParam);
        }

        return $handler->handle($request);
    }
}