<?php

declare(strict_types=1);

use App\Controllers\AccountActivationController;
use App\Controllers\AuthController;
use App\Controllers\CategoriesController;
use App\Controllers\InvalidRequestController;
use App\Controllers\PasswordResetController;
use App\Controllers\HomeController;
use App\Controllers\ReceiptsController;
use App\Controllers\SettingsController;
use App\Controllers\TransactionsController;
use App\Middleware\AccountActivationMiddleware;
use App\Middleware\ActivatedUserMiddleware;
use App\Middleware\ActiveRouteMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\RateLimitingMiddleware;
use App\Middleware\VerifySignatureMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/{dateRange:YTD|MTD|WTD|DTD|TODAY|customDate|}', [HomeController::class, 'index'])
              ->setName('overview')
              ->add(ActiveRouteMiddleware::class);
        $group->post('/{dateRange:customDate}', [HomeController::class, 'index']);
        $group->get('/stats/{dateRange:YTD|MTD|WTD|DTD|TODAY|customDate|}', [HomeController::class, 'getStatistics']);

        $group->group('/categories', function (RouteCollectorProxy $categories) {
            $categories->get('', [CategoriesController::class, 'index'])
                       ->setName('categories')
                       ->add(ActiveRouteMiddleware::class);
            $categories->post('', [CategoriesController::class, 'store']);
            $categories->post('/{category:[0-9]+}', [CategoriesController::class, 'update']);
            $categories->get('/{category:[0-9]+}', [CategoriesController::class, 'get']);
            $categories->delete('/{category:[0-9]+}', [CategoriesController::class, 'delete']);
            $categories->get('/load', [CategoriesController::class, 'load']);
        });

        $group->group('/transactions', function (RouteCollectorProxy $transaction) {
            $transaction->get('', [TransactionsController::class, 'index'])
                        ->setName('transactions')
                        ->add(ActiveRouteMiddleware::class);
            $transaction->post('', [TransactionsController::class, 'store']);
            $transaction->delete('/{transaction:[0-9]+}', [TransactionsController::class, 'delete']);
            $transaction->get('/{transaction:[0-9]+}', [TransactionsController::class, 'get']);
            $transaction->post('/{transaction:[0-9]+}', [TransactionsController::class, 'update']);
            $transaction->get('/load', [TransactionsController::class, 'load']);
            $transaction->post('/upload', [TransactionsController::class, 'upload']);
            $transaction->post('/{transaction:[0-9]+}/receipts', [ReceiptsController::class, 'store']);
            $transaction->get('/{transaction:[0-9]+}/receipts/{receipt:[0-9]+}', [ReceiptsController::class, 'download']
            );
            $transaction->delete('/{transaction:[0-9]+}/receipts/{receipt:[0-9]+}',
                [ReceiptsController::class, 'delete']
            );
            $transaction->post('/{transaction:[0-9]+}/reviewed', [TransactionsController::class, 'toggleReviewed']);
        });
    })->add(AccountActivationMiddleware::class)->add(AuthMiddleware::class);


    $app->group('', function (RouteCollectorProxy $guest) {
        $guest->get('/login', [AuthController::class, 'loginView']);
        $guest->get('/register', [AuthController::class, 'registerView']);
        $guest->post('/login', [AuthController::class, 'login'])
              ->setName('login')
              ->add(RateLimitingMiddleware::class);
        $guest->post('/register', [AuthController::class, 'register'])
              ->setName('register')
              ->add(RateLimitingMiddleware::class);
        $guest->post('/loginWith2FA', [AuthController::class, 'loginWith2FA'])
              ->setName('loginWith2FA')
              ->add(RateLimitingMiddleware::class);
        $guest->get('/forgotPassword', [PasswordResetController::class, 'index']);
        $guest->post('/sendPasswordResetEmail', [PasswordResetController::class, 'sendPasswordResetEmail'])
              ->setName('sendPasswordResetEmail')
              ->add(RateLimitingMiddleware::class);
        $guest->get('/resetPasswordForm/{token}/{hash}', [PasswordResetController::class, 'resetPasswordForm'])
              ->setName('resetPassword')
              ->add(VerifySignatureMiddleware::class)
              ->add(RateLimitingMiddleware::class);
        $guest->post('/resetPassword/{token}/{hash}', [PasswordResetController::class, 'resetPassword'])
              ->setName('resetPassword')
              ->add(RateLimitingMiddleware::class);
    })->add(GuestMiddleware::class);


    $app->group('', function (RouteCollectorProxy $group) {
        $group->post('/logout', [AuthController::class, 'logout']);
        $group->get('/activate', [AccountActivationController::class, 'index'])->add(ActivatedUserMiddleware::class);
        $group->get('/activate/{id:[0-9]+}/{hash}', [AccountActivationController::class, 'activate'])
              ->setName('activate')
              ->add(VerifySignatureMiddleware::class)
              ->add(RateLimitingMiddleware::class);

        $group->post('/sendActivationEmail', [AccountActivationController::class, 'sendActivationEmail'])
              ->setName('sendActivationEmail')
              ->add(RateLimitingMiddleware::class);
        $group->group('/settings', function (RouteCollectorProxy $settings) {
            $settings->get('', [SettingsController::class, 'index'])->setName('profile');
            $settings->get('/profile', [SettingsController::class, 'index'])->setName('profile');
            $settings->post('/profile/update', [SettingsController::class, 'updateProfile']);
            $settings->post('/profile/changePassword', [SettingsController::class, 'changePassword']);
            $settings->get('/authentication', [SettingsController::class, 'authentication'])->setName('authentication');
            $settings->post('/authentication/toggle2FA', [SettingsController::class, 'toggle2FA']);
            $settings->post('/authentication/disableCode', [SettingsController::class, 'disableCode']);
            $settings->get('/help', [SettingsController::class, 'help'])->setName('help');
        })->add(ActiveRouteMiddleware::class);
    })->add(AuthMiddleware::class);

    $app->get('/invalidRequest', [InvalidRequestController::class, 'index'])->add(AuthMiddleware::class);
    $app->get('/tooManyRequests', [InvalidRequestController::class, 'tooManyRequests']);
};
