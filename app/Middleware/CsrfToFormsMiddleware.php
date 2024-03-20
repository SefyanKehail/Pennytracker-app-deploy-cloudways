<?php

namespace App\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Csrf\Guard;
use Slim\Views\Twig;

class CsrfToFormsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Twig               $twig,
        private readonly ContainerInterface $container
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $csrf = $this->container->get('csrf');

        // CSRF token name and value
        $csrfNameKey  = $csrf->getTokenNameKey();
        $csrfValueKey = $csrf->getTokenValueKey();
        $csrfName     = $csrf->getTokenName();
        $csrfValue    = $csrf->getTokenValue();

        $fields = <<<CSRFF
<input type="hidden" name="$csrfNameKey" value="$csrfName">
<input type="hidden" name="$csrfValueKey" value="$csrfValue">
CSRFF;

        $csrf = [
            'keys'   => [
                'name'  => $csrfNameKey,
                'value' => $csrfValueKey,
            ],
            'name'   => $csrfName,
            'value'  => $csrfValue,
            'fields' => $fields,
        ];

        $this->twig->getEnvironment()->addGlobal('csrf', $csrf);

        return $handler->handle($request);
    }
}