<?php

namespace App;

use Slim\Interfaces\RouteParserInterface;

class SignedURLGenerator
{
    public function __construct(
        private readonly RouteParserInterface $routeParser,
        private readonly Config               $config
    ) {
    }

    // {BASE_URL}/routeName/...routeParams?expiration={EXPIRATION_TIMESTAMP}&signature={SIGNATURE}
    public function fromRoute(string $routeName, array $routeParams, \DateTime $expirationDate): string
    {

        $expiration  = $expirationDate->getTimestamp();
        $baseURL     = trim($this->config->get('app_url'), '/');
        $queryParams = ['expiration' => $expiration];

//      we key-hash this part  {BASE_URL}/routeName/...routeParams?expiration={EXPIRATION_TIMESTAMP}
        $signature = hash_hmac(
            'sha256',
            $baseURL . $this->routeParser->urlFor($routeName, $routeParams, $queryParams),
            $this->config->get('app_key')
        );

        $queryParams['signature'] = $signature;

        return $baseURL . $this->routeParser->urlFor($routeName, $routeParams, $queryParams);
    }
}