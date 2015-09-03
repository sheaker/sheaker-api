<?php

/**
 * Main routes
 */
$app->get('/clients',                   'Sheaker\Controller\MainController::getSheakerClient');
$app->get('/infos',                     'Sheaker\Controller\MainController::getSheakerInfos');

/**
 * Users routes
 */
$app->post('/users/login',            'Sheaker\Controller\UserController::login');
$app->post('/users/renew_token',      'Sheaker\Controller\UserController::renewToken');

$app->get('/users',                   'Sheaker\Controller\UserController::getUsersList')
    ->before($beforeCheckToken);
$app->get('/users/search',            'Sheaker\Controller\UserController::getUsersSearch')
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

$app->get('/users/graph/new',         'Sheaker\Controller\UsersGraphicsController::newUsers')
    ->before($beforeCheckToken);
$app->get('/users/graph/sex',         'Sheaker\Controller\UsersGraphicsController::genderRepartition')
    ->before($beforeCheckToken);

/**
 * Payments routes
 */
$app->get('/payments/{payment_id}',     'Sheaker\Controller\PaymentController::getPayment')
    ->assert('payment_id', '\d+')
    ->before($beforeCheckToken);

$app->get('/payments/stats/gains',      'Sheaker\Controller\PaymentsStatisticsController::getGainsFromDate')
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

$app->get('/checkins/graph/new',        'Sheaker\Controller\CheckinsGraphicsController::newCheckinsFromDate')
    ->before($beforeCheckToken);

// Checkins by user
$app->get('/users/{user_id}/checkins',  'Sheaker\Controller\CheckinController::getCheckinsListByUser')
    ->assert('user_id', '\d+')
    ->before($beforeCheckToken);
$app->post('/users/{user_id}/checkins', 'Sheaker\Controller\CheckinController::addCheckin')
    ->assert('user_id', '\d+')
    ->before($beforeCheckToken);

/**
 * Elasticsearch routes
 */
$app->post('/elasticsearch/indexing', 'Sheaker\Controller\ElasticSearchController::indexing');
