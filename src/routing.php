<?php

/**
 * Main routes
 */
$app->get('/clients',                   'Sheaker\Controller\MainController::getSheakerClient');
$app->get('/infos',                     'Sheaker\Controller\MainController::getSheakerInfos');
$app->post('/login',                    'Sheaker\Controller\MainController::login');
$app->post('/renew_token',              'Sheaker\Controller\MainController::renewToken');

/**
 * Users routes
 */
$app->get('/users',                   'Sheaker\Controller\UserController::getUsersList');
$app->get('/users/{user_id}',         'Sheaker\Controller\UserController::getUser')
    ->assert('user_id', '\d+');
$app->post('/users',                  'Sheaker\Controller\UserController::addUser');
$app->put('/users/{user_id}',         'Sheaker\Controller\UserController::editUser')
    ->assert('user_id', '\d+');
$app->delete('/users/{user_id}',      'Sheaker\Controller\UserController::deleteUser')
    ->assert('user_id', '\d+');

$app->get('/users/stats',             'Sheaker\Controller\UserController::statsUsers');
$app->get('/users/stats/new',         'Sheaker\Controller\UserController::newUsers');
$app->get('/users/stats/incbirthday', 'Sheaker\Controller\UserController::incUsersBirthdays');

/**
 * Payments routes
 */
$app->get('/payments/{payment_id}',     'Sheaker\Controller\PaymentController::getPayment')
    ->assert('payment_id', '\d+');
//$app->delete('/payments/{payment_id}',  'Sheaker\Controller\PaymentController::deletePayment')
//    ->assert('payment_id', '\d+');

$app->get('/payments/stats/new',        'Sheaker\Controller\PaymentController::newMemberships');
$app->get('/payments/stats/ending',     'Sheaker\Controller\PaymentController::endingMemberships');

// Payments by user
$app->get('/users/{user_id}/payments',  'Sheaker\Controller\PaymentController::getPaymentsListByUser')
    ->assert('user_id', '\d+');
$app->post('/users/{user_id}/payments', 'Sheaker\Controller\PaymentController::addPayment')
    ->assert('user_id', '\d+');

/**
 * Checkin routes
 */
$app->get('/checkins/{checkin_id}',     'Sheaker\Controller\CheckinController::getCheckin')
    ->assert('checkin_id', '\d+');
//$app->delete('/checkins/{checkin_id}',  'Sheaker\Controller\CheckinController::deleteCheckin')
//    ->assert('checkin_id', '\d+');

$app->get('/checkins/stats/new',        'Sheaker\Controller\CheckinController::newCheckins');

// Checkins by user
$app->get('/users/{user_id}/checkins',  'Sheaker\Controller\CheckinController::getCheckinsListByUser')
    ->assert('user_id', '\d+');
$app->post('/users/{user_id}/checkins', 'Sheaker\Controller\CheckinController::addCheckin')
    ->assert('user_id', '\d+');

/**
 * Elasticsearch routes
 */
$app->post('/elasticsearch/indexing', 'Sheaker\Controller\ElasticSearchController::indexing');
