<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Silex\Application;
use Monolog\Logger;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Elasticsearch\ClientBuilder;
use Aws\Silex\AwsServiceProvider;

define('APPLICATION_ENV', getenv('APPLICATION_ENV') ?: 'production');

$app = new Application();

if (APPLICATION_ENV != 'production') {
    $app['debug'] = true;
    ini_set('display_error', 1);
    error_reporting(E_ALL);
} else {
    $app['controllers']->requireHttps();
}

if ($app['debug']) {
    require_once __DIR__ . '/../config/developement.php';
} else {
    require_once __DIR__ . '/../config/production.php';
}

date_default_timezone_set($app['timezone']);

$app['api.accessLevels'] = [
    0 => 'client',
    1 => 'user',
    2 => 'modo',
    3 => 'admin'
];

/**
 * Register service providers
 */
$app['client.id'] = isset($_GET['id_client']) ? $_GET['id_client'] : 0;
$app->register(new DoctrineServiceProvider(), [
    'dbs.options' => [
        'gym' => [
            'dbname'        => 'client_' . $app['client.id'],
            'host'          => $app['database.gym']['host'],
            'user'          => $app['database.gym']['user'],
            'password'      => $app['database.gym']['passwd'],
            'charset'       => 'utf8',
            'driverOptions' => array(1002 => 'SET NAMES utf8')
        ],
        'sheaker' => [
            'dbname'        => $app['database.sheaker']['dbname'],
            'host'          => $app['database.sheaker']['host'],
            'user'          => $app['database.sheaker']['user'],
            'password'      => $app['database.sheaker']['passwd'],
            'charset'       => 'utf8',
            'driverOptions' => array(1002 => 'SET NAMES utf8')
        ]
    ]
]);

$app->register(new MonologServiceProvider(), [
    'monolog.logfile' => __DIR__ . '/../logs/application.log',
    'monolog.level'   => Logger::WARNING,
    'monolog.name'    => 'api'
]);

$app['elasticsearch.client'] = function($app) {
    return ClientBuilder::create()
                        ->setHosts($app['elasticsearch.config']['hosts'])
                        ->setLogger(ClientBuilder::defaultLogger($app['elasticsearch.config']['logPath']))
                        ->build();
};

$app->register(new AwsServiceProvider(), [
    'aws.config' => $app['aws.config']
]);

/**
 * Register our custom services
 */
$app['client'] = $app->share(function ($app) {
    return new Sheaker\Service\ClientService($app);
});

$app['jwt'] = $app->share(function ($app) {
    return new Sheaker\Service\JWTService($app);
});

/**
 * Register repositories
 */
$app['repository.client'] = $app->share(function ($app) {
    return new Sheaker\Repository\ClientRepository($app['dbs']['sheaker']);
});

$app['repository.user'] = $app->share(function ($app) {
    return new Sheaker\Repository\UserRepository($app['dbs']['gym']);
});

$app['repository.payment'] = $app->share(function ($app) {
    return new Sheaker\Repository\PaymentRepository($app['dbs']['gym']);
});

$app['repository.checkin'] = $app->share(function ($app) {
    return new Sheaker\Repository\CheckinRepository($app['dbs']['gym']);
});

/**
 * Register before handler
 */
$app->before(function (Request $request, Application $app) {
    if ($request->getMethod() === 'OPTIONS') {
        return;
    }

    if (strpos($request->headers->get('Content-Type'), 'application/json') === 0) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }

    if (strpos($request->getPathInfo(), 'clients') === false) {
        $app['client']->fetchClient($request);
    }
});

$beforeCheckToken = function (Request $request, Application $app) {
    $app['jwt']->checkTokenAuthenticity($request);
};

/**
 * Register after handler
 */
$app->after(function (Request $request, Response $response) {
    $response->headers->set('Content-Type', 'application/json');
});

/**
 * Register error handler
 */
$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    $app['monolog']->addError($e->getMessage());
    $app['monolog']->addError($e->getTraceAsString());

    return new JsonResponse(['error' => ['code' => $code, 'message' => $e->getMessage()]]);
});

// Black magic to handle OPTIONS with the API
$app->match('{url}', function($url) use ($app) { return 'OK'; })->assert('url', '.*')->method('OPTIONS');

require_once __DIR__ . '/routing.php';
require_once __DIR__ . '/constants.php';

return $app;
