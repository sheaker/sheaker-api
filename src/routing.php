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
$app->get('/users',         'Sheaker\Controller\UserController::getUsersList');
$app->get('/users/{id}',    'Sheaker\Controller\UserController::getUser');
$app->post('/users',        'Sheaker\Controller\UserController::addUser');
$app->put('/users/{id}',    'Sheaker\Controller\UserController::editUser');
$app->delete('/users/{id}', 'Sheaker\Controller\UserController::deleteUser');

/**
 * Payments routes
 */
$app->get('/payments',      'Sheaker\Controller\PaymentController::getPaymentsList');
$app->get('/payments/{id}', 'Sheaker\Controller\PaymentController::getPayment');
$app->post('/payments',     'Sheaker\Controller\PaymentController::addPayment');

/**
 * Checkin routes
 */
$app->get('/checkin',      'Sheaker\Controller\CheckinController::getCheckinList');
$app->get('/checkin/{id}', 'Sheaker\Controller\CheckinController::getCheckin');
$app->post('/checkin',     'Sheaker\Controller\CheckinController::addCheckin');

/**
 * Elasticsearch routes
 */
$app->post('/elasticsearch/indexing', 'Sheaker\Controller\ElasticSearchController::indexing');
