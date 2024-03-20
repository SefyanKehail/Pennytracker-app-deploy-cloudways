<?php

namespace App;

use Closure;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CsrfFailureHandler
{
    public function __construct(private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    public function handleFailure(): Closure
    {
        return function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $response = $this->responseFactory->createResponse();
            return $response->withStatus(403);
        };
    }
}