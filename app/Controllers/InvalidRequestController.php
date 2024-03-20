<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class InvalidRequestController
{

    public function __construct(private readonly Twig $twig)
    {
    }

    public function index(Response $response): Response
    {
        return $this->twig->render($response, 'alerts/failed_signature_verification.twig');
    }

    public function tooManyRequests(Response $response): Response
    {
        return $this->twig->render($response, 'alerts/too_many_requests.twig');
    }
}