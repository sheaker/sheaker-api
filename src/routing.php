<?php

/**
 * Main routes
 */
$app->get('/clients',      'Sheaker\Controller\MainController::getSheakerClient');
$app->get('/infos',        'Sheaker\Controller\MainController::getSheakerInfos');
$app->post('/login',       'Sheaker\Controller\MainController::login');
$app->post('/renew_token', 'Sheaker\Controller\MainController::renewToken');

/**
 * Users routes
 */
$app->get('/users',                   'Sheaker\Controller\UserController::getUsersList');
$app->get('/users/{id}',              'Sheaker\Controller\UserController::getUser');
$app->post('/users',                  'Sheaker\Controller\UserController::addUser');
$app->put('/users/{id}',              'Sheaker\Controller\UserController::editUser');
$app->delete('/users/{id}',           'Sheaker\Controller\UserController::deleteUser');
$app->get('/users/stats',             'Sheaker\Controller\UserController::statsUsers');
$app->get('/users/stats/new',         'Sheaker\Controller\UserController::newUsers');
$app->get('/users/stats/incbirthday', 'Sheaker\Controller\UserController::incUsersBirthdays');

/**
 * Payments routes
 */
$app->get('/payments',              'Sheaker\Controller\PaymentController::getPaymentsList');
$app->get('/payments/{id}',         'Sheaker\Controller\PaymentController::getPayment');
$app->post('/payments',             'Sheaker\Controller\PaymentController::addPayment');
$app->get('/payments/stats/new',    'Sheaker\Controller\PaymentController::newMemberships');
$app->get('/payments/stats/ending', 'Sheaker\Controller\PaymentController::endingMemberships');

/**
 * Checkin routes
 */
$app->get('/checkins',           'Sheaker\Controller\CheckinController::getCheckinsList');
$app->get('/checkins/{id}',      'Sheaker\Controller\CheckinController::getCheckin');
$app->post('/checkins',          'Sheaker\Controller\CheckinController::addCheckin');
$app->get('/checkins/stats/new', 'Sheaker\Controller\CheckinController::newCheckins');

/**
 * Elasticsearch routes
 */
$app->post('/elasticsearch/indexing', 'Sheaker\Controller\ElasticSearchController::indexing');
