<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

class AccountActivationMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ResponseFactory $responseFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // redirect to verification alert
        $user = $request->getAttribute('user');

        if ($user?->getVerifiedAt()){
            return $handler->handle($request);
        }

        return $this->responseFactory->createResponse(302)->withHeader('location', '/activate');
    }
}