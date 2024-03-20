<?php

namespace App\Middleware;

// un-expose the /activate route for already activated users
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ActivatedUserMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getAttribute('user')->getVerifiedAt()){
            return $this->responseFactory->createResponse(302)->withHeader('location', '/');
        }
        return $handler->handle($request);
    }
}