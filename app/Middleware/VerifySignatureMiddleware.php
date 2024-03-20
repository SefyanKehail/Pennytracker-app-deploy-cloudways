<?php

namespace App\Middleware;

use App\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Views\Twig;

class VerifySignatureMiddleware implements MiddlewareInterface
{

    public function __construct(
        private readonly Config          $config,
        private readonly ResponseFactory $responseFactory,
        private readonly Twig            $twig
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // get query params
        // get uri to recreate hash ( unset signature because it's in the uri too)
        // get expiration date and validate it
        // compare and handle

        $uri                  = $request->getUri();
        $queryParams          = $request->getQueryParams();
        $signatureFromRequest = $queryParams['signature'] ?? '';
        $expirationDate       = $queryParams['expiration'] ?? 0;

        unset($queryParams['signature']);

        // re-construct the url without signature ( uri can be cast to url string )
        $url                 = (string )$uri->withQuery(http_build_query($queryParams));
        $signatureFromServer = hash_hmac(
            'sha256',
            $url,
            $this->config->get('app_key')
        );

        if ($expirationDate <= time() || ! hash_equals($signatureFromRequest, $signatureFromServer)) {
            return $this->responseFactory->createResponse(302)->withHeader('location','/invalidRequest');
        }

        return $handler->handle($request);
    }
}