<?php

/**
 * Main routes
 */
$app->get('/clients',                 'Sheaker\Controller\MainController::getClient')
    ->before($fetchClient);
$app->post('/clients',                'Sheaker\Controller\MainController::createClient');
$app->put('/clients/index',           'Sheaker\Controller\MainController::indexClient')
    ->before($fetchClient);
$app->get('/infos',                   'Sheaker\Controller\MainController::getSheakerInfos');

/**
 * Users routes
 */
$app->post('/users/login',            'Sheaker\Controller\UserController::login')
    ->before($fetchClient);

$app->get('/users',                   'Sheaker\Controller\UserController::getUsersList')
    ->before($fetchClient)
    ->before($checkToken);
$app->get('/users/search',            'Sheaker\Controller\UserController::searchUsers')
    ->before($fetchClient)
    ->before($checkToken);
$app->get('/users/{user_id}',         'Sheaker\Controller\UserController::getUser')
    ->assert('user_id', '\d+')
    ->before($fetchClient)
    ->before($checkToken);
$app->post('/users',                  'Sheaker\Controller\UserController::addUser')
    ->before($fetchClient)
    ->before($checkToken);
$app->put('/users/{user_id}',         'Sheaker\Controller\UserController::editUser')
    ->assert('user_id', '\d+')
    ->before($fetchClient)
    ->before($checkToken);
$app->delete('/users/{user_id}',      'Sheaker\Controller\UserController::deleteUser')
    ->assert('user_id', '\d+')
    ->before($fetchClient)
    ->before($checkToken);

$app->get('/users/stats/active',      'Sheaker\Controller\UsersStatisticsController::getActiveUsers')
    ->before($fetchClient)
    ->before($checkToken);
$app->get('/users/stats/new',         'Sheaker\Controller\UsersStatisticsController::getNewUsersFromDate')
    ->before($fetchClient)
    ->before($checkToken);

$app->get('/users/graph/new',         'Sheaker\Controller\UsersGraphicsController::getNewUsersFromDate')
    ->before($fetchClient)
    ->before($checkToken);
$app->get('/users/graph/sex',         'Sheaker\Controller\UsersGraphicsController::getGenderRepartition')
    ->before($fetchClient)
    ->before($checkToken);

/**
 * Payments routes
 */
$app->get('/payments/{payment_id}',     'Sheaker\Controller\PaymentController::getPayment')
    ->assert('payment_id', '\d+')
    ->before($fetchClient)
    ->before($checkToken);

$app->get('/payments/stats/gains',      'Sheaker\Controller\PaymentsStatisticsController::getGainsFromDate')
    ->before($fetchClient)
    ->before($checkToken);

$app->get('/payments/graph/gains',      'Sheaker\Controller\PaymentsGraphicsController::getGains')
    ->before($fetchClient)
    ->before($checkToken);

// Payments by user
$app->get('/users/{user_id}/payments',  'Sheaker\Controller\PaymentController::getPaymentsListByUser')
    ->assert('user_id', '\d+')
    ->before($fetchClient)
    ->before($checkToken);
$app->post('/users/{user_id}/payments', 'Sheaker\Controller\PaymentController::addPayment')
    ->assert('user_id', '\d+')
    ->before($fetchClient)
    ->before($checkToken);

/**
 * Checkin routes
 */
$app->get('/checkins/stats/new',        'Sheaker\Controller\CheckinsStatisticsController::getCheckinsFromDate')
    ->before($fetchClient)
    ->before($checkToken);

$app->get('/checkins/graph/new',        'Sheaker\Controller\CheckinsGraphicsController::getCheckinsFromDate')
    ->before($fetchClient)
    ->before($checkToken);

// Checkins by user
$app->get('/users/{user_id}/checkins',  'Sheaker\Controller\CheckinController::getCheckinsListByUser')
    ->assert('user_id', '\d+')
    ->before($fetchClient)
    ->before($checkToken);
$app->post('/users/{user_id}/checkins', 'Sheaker\Controller\CheckinController::addCheckin')
    ->assert('user_id', '\d+')
    ->before($fetchClient)
    ->before($checkToken);
