<?php

declare(strict_types=1);

use App\Config;
use App\Enum\AppEnvironment;
use App\Middleware\ActiveRouteMiddleware;
use App\Middleware\CsrfToFormsMiddleware;
use App\Middleware\FlashErrorsAndDataMiddleware;
use App\Middleware\FlashSuccessAlerts;
use App\Middleware\PassAuthenticatedUserMiddleware;
use App\Middleware\StartSessionMiddleware;
use App\Middleware\Uncatched429Middlware;
use App\Middleware\ValidationExceptionMiddleware;
use Slim\App;
use Clockwork\Support\Slim\ClockworkMiddleware;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Clockwork\Clockwork;

return function (App $app) {
    $container = $app->getContainer();
    $config    = $container->get(Config::class);

    $app->add(FlashSuccessAlerts::class);
    $app->add(CsrfToFormsMiddleware::class);
    $app->add('csrf');
    $app->add(TwigMiddleware::create($app, $container->get(Twig::class)));
    $app->add(FlashErrorsAndDataMiddleware::class);
    $app->add(ValidationExceptionMiddleware::class);
    $app->add(StartSessionMiddleware::class);
    $app->addRoutingMiddleware();
    $app->add(new MethodOverrideMiddleware());
    $app->addBodyParsingMiddleware();
    if (AppEnvironment::isDevelopment($config->get('app_environment'))) {
        $app->add(new ClockworkMiddleware($app, $container->get(Clockwork::class)));
    }
    $app->addErrorMiddleware(
        (bool)$config->get('display_error_details'),
        (bool)$config->get('log_errors'),
        (bool)$config->get('log_error_details')
    );
};
