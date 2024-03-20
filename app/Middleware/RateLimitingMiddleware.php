<?php

namespace App\Middleware;

use App\Config;
use App\Exceptions\RateLmitException;
use App\ResponseFormatter;
use App\Services\RequestService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Slim\Routing\RouteContext;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimitingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly RequestService           $requestService,
        private readonly Config                   $config,
        private readonly RateLimiterFactory       $rateLimiterFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $clientIp     = $this->requestService->getClientIp($request, $this->config->get('trusted_proxies'));
        $routeContext = RouteContext::fromRequest($request);
        $routeName    = $routeContext->getRoute()->getName();
        $rateLimiter  = $this->rateLimiterFactory->create($routeName . '_' . $clientIp); // a limiter on the ip

        // our rate to be consumed on every request
        if (! $rateLimiter->consume()->isAccepted()) {
            return $this->responseFactory->createResponse(429, 'Too many requests');
        }

        return $handler->handle($request);
    }
}