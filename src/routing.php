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

$app->get('/users/stats',             'Sheaker\Controller\UserController::statsUsers')
    ->before($beforeCheckToken);
$app->get('/users/stats/new',         'Sheaker\Controller\UserController::newUsers')
    ->before($beforeCheckToken);
$app->get('/users/stats/incbirthday', 'Sheaker\Controller\UserController::incUsersBirthdays')
    ->before($beforeCheckToken);

/**
 * Payments routes
 */
//$app->delete('/payments/{payment_id}',  'Sheaker\Controller\PaymentController::deletePayment')
//    ->assert('payment_id', '\d+')
//    ->before($beforeCheckToken);

$app->get('/payments/stats/new',        'Sheaker\Controller\PaymentController::newMemberships')
    ->before($beforeCheckToken);
$app->get('/payments/stats/ending',     'Sheaker\Controller\PaymentController::endingMemberships')
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
//$app->delete('/checkins/{checkin_id}',  'Sheaker\Controller\CheckinController::deleteCheckin')
//    ->assert('checkin_id', '\d+')
//    ->before($beforeCheckToken);

$app->get('/checkins/stats/new',        'Sheaker\Controller\CheckinController::newCheckins')
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
