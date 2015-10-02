<?php

/**
 * Main routes
 */
$app->get('/clients',                 'Sheaker\Controller\MainController::getClient');
$app->post('/clients',                'Sheaker\Controller\MainController::createClient');
$app->put('/clients/index',           'Sheaker\Controller\MainController::indexClient');
$app->get('/infos',                   'Sheaker\Controller\MainController::getSheakerInfos');

/**
 * Users routes
 */
$app->post('/users/login',            'Sheaker\Controller\UserController::login');
$app->post('/users/renew_token',      'Sheaker\Controller\UserController::renewToken');

$app->get('/users',                   'Sheaker\Controller\UserController::getUsersList')
    ->before($beforeCheckToken);
$app->get('/users/search',            'Sheaker\Controller\UserController::searchUsers')
    ->before($beforeCheckToken);
$app->get('/users/{user_id}',         'Sheaker\Controller\UserController::getUser')
    ->assert('user_id', '\d+')
    ->before($beforeCheckToken);
$app->post('/users',                  'Sheaker\Controller\UserController::addUser')
    ->before($beforeCheckToken);
$app->put('/users/{user_id}',         'Sheaker\Controller\UserController::editUser')
    ->assert('user_id', '\d+')
    ->before($beforeCheckToken);
$app->delete('/users/{user_id}',      'Sheaker\Controller\UserController::deleteUser')
    ->assert('user_id', '\d+')
    ->before($beforeCheckToken);

$app->get('/users/stats/active',      'Sheaker\Controller\UsersStatisticsController::getActiveUsers')
    ->before($beforeCheckToken);
$app->get('/users/stats/new',         'Sheaker\Controller\UsersStatisticsController::getNewUsersFromDate')
    ->before($beforeCheckToken);

$app->get('/users/graph/new',         'Sheaker\Controller\UsersGraphicsController::getNewUsersFromDate')
    ->before($beforeCheckToken);
$app->get('/users/graph/sex',         'Sheaker\Controller\UsersGraphicsController::getGenderRepartition')
    ->before($beforeCheckToken);

/**
 * Payments routes
 */
$app->get('/payments/{payment_id}',     'Sheaker\Controller\PaymentController::getPayment')
    ->assert('payment_id', '\d+')
    ->before($beforeCheckToken);

$app->get('/payments/stats/gains',      'Sheaker\Controller\PaymentsStatisticsController::getGainsFromDate')
    ->before($beforeCheckToken);

$app->get('/payments/graph/gains',      'Sheaker\Controller\PaymentsGraphicsController::getGains')
    ->before($beforeCheckToken);

// Payments by user
$app->get('/users/{user_id}/payments',  'Sheaker\Controller\PaymentController::getPaymentsListByUser')
    ->assert('user_id', '\d+')
    ->before($beforeCheckToken);
$app->post('/users/{user_id}/payments', 'Sheaker\Controller\PaymentController::addPayment')
    ->assert('user_id', '\d+')
    ->before($beforeCheckToken);

/**
 * Checkin routes
 */
$app->get('/checkins/stats/new',        'Sheaker\Controller\CheckinsStatisticsController::getCheckinsFromDate')
    ->before($beforeCheckToken);

$app->get('/checkins/graph/new',        'Sheaker\Controller\CheckinsGraphicsController::getCheckinsFromDate')
    ->before($beforeCheckToken);

// Checkins by user
$app->get('/users/{user_id}/checkins',  'Sheaker\Controller\CheckinController::getCheckinsListByUser')
    ->assert('user_id', '\d+')
    ->before($beforeCheckToken);
$app->post('/users/{user_id}/checkins', 'Sheaker\Controller\CheckinController::addCheckin')
    ->assert('user_id', '\d+')
    ->before($beforeCheckToken);
