<?php

namespace Sheaker\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Sheaker\Constants\ClientFlags;
use Sheaker\Entity\Client;

class MainController
{
    public function createClient(Request $request, Application $app)
    {
        $getParams = [];
        $getParams['name']      = $app->escape($request->get('name'));
        $getParams['subdomain'] = $app->escape($request->get('subdomain'));
        $getParams['email']     = $app->escape($request->get('email'));
        $getParams['password']  = $app->escape($request->get('password'));

        foreach ($getParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        // create our new client
        $client = new Client();
        $client->setName($getParams['name']);
        $client->setSubdomain($getParams['subdomain']);
        $client->setSecretKey(md5(uniqid(rand(), TRUE)));
        $client->setFlags($client->getFlags() | ClientFlags::INDEX_ELASTICSEARCH);
        $app['repository.client']->save($client);

        $clientAppName = 'client_' . $client->getId();

        // create client database
        $app['dbs']['sheaker']->query("CREATE DATABASE ${clientAppName}; USE ${clientAppName};");
        // Add our tables
        $app['dbs']['sheaker']->query(file_get_contents(__DIR__ . '/../../../sql/base/client_database.sql'));
        // Add admin user
        $app['dbs']['sheaker']->query("
            INSERT INTO
                users (`first_name`, `last_name`, `password`, `mail`)
            VALUES
                ('admin', 'admin', '" . password_hash($getParams['password'], PASSWORD_DEFAULT) . "', '" . $getParams['email'] . "');
        ");
        // Add user rights
        $app['dbs']['sheaker']->query("INSERT INTO users_access VALUES (LAST_INSERT_ID(), 3);");

        // create indice ES
        self::createElasticIndex($app, $clientAppName);

        // create AWS S3 bucket
        $s3 = $app['aws']->createS3();

        $bucketName = 'sheaker-' . md5($clientAppName);
        if (!$s3->doesBucketExist($bucketName)) {
            $s3->createBucket(['Bucket' => $bucketName]);
        }

        return json_encode($client, JSON_NUMERIC_CHECK);
    }

    public function getClient(Request $request, Application $app)
    {
        $getParams = [];
        $getParams['subdomain'] = $app->escape($request->get('subdomain'));

        foreach ($getParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        $client = clone $app['client']->getClient();

        /// SECURITY: Don't send the secret key, it's only needed here
        unset($client->secretKey);

        return json_encode($client, JSON_NUMERIC_CHECK);
    }

    public function indexClient(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!$app['debug'] && !in_array('admin', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $client = $app['client']->getClient();
        if (!$app['debug'] && !($client->getFlags() & ClientFlags::INDEX_ELASTICSEARCH)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $params['index'] = 'client_' . $app['client.id'];

        // First, delete existing index
        if ($app['elasticsearch.client']->indices()->exists($params))
            $app['elasticsearch.client']->indices()->delete($params);

        // Then, create a new index with the mapping inside
        self::createElasticIndex($app, $params['index']);

        $params['type']  = 'user';

        // Now, retrieve and put datas
        $users = $app['repository.user']->findAll(0, 0, ['created_at' => 'asc']);
        foreach ($users as $u)
        {
            $payments = [];
            foreach ($app['repository.payment']->findAll(0, 0, ['created_at' => 'asc'], ['user_id' => $u->getId()]) as $p) {
                array_push($payments, [
                        'id'             => $p->getId(),
                        'start_date'     => $p->getStartDate(),
                        'end_date'       => $p->getEndDate(),
                        'days'           => $p->getDays(),
                        'price'          => $p->getPrice(),
                        'payment_method' => $p->getMethod(),
                        'comment'        => $p->getComment(),
                        'created_at'     => $p->getCreatedAt()
                    ]
                );
            }

            $checkins = [];
            foreach ($app['repository.checkin']->findAll(0, 0, ['created_at' => 'asc'], ['user_id' => $u->getId()]) as $ci) {
                array_push($checkins, [
                        'id'         => $ci->getId(),
                        'created_at' => $ci->getCreatedAt()
                    ]
                );
            }

            $params['body'][] = [
                'index' => [
                    '_id' => (int)$u->getId()
                ]
            ];

            $params['body'][] = [
                'id'               => $u->getId(),
                'first_name'       => $u->getFirstName(),
                'last_name'        => $u->getLastName(),
                'password'         => $u->getPassword(),
                'phone'            => $u->getphone(),
                'mail'             => $u->getMail(),
                'birthdate'        => $u->getBirthdate(),
                'address_street_1' => $u->getAddressStreet1(),
                'address_street_2' => $u->getAddressStreet2(),
                'city'             => $u->getCity(),
                'zip'              => $u->getZip(),
                'gender'           => $u->getGender(),
                'photo'            => $u->getPhoto(),
                'sponsor_id'       => $u->getSponsor(),
                'comment'          => $u->getComment(),
                'last_seen'        => $u->getLastSeen(),
                'last_ip'          => $u->getLastIP(),
                'failed_logins'    => $u->getFailedLogins(),
                'created_at'       => $u->getCreatedAt(),
                'deleted_at'       => $u->getDeletedAt(),
                'user_level'       => $u->getUserLevel(),
                'payments'         => (count($payments)) ? $payments : new \stdClass(),
                'checkins'         => (count($checkins)) ? $checkins : new \stdClass()
            ];
        }

        $responses = $app['elasticsearch.client']->bulk($params);

        // We don't really care about that, light up the response size
        unset($responses['items']);

        $client->setFlags($client->getFlags() & ~ClientFlags::INDEX_ELASTICSEARCH);
        $app['repository.client']->save($client);

        return json_encode($responses, JSON_NUMERIC_CHECK);
    }

    public function getSheakerInfos(Request $request, Application $app)
    {
        $reserved_subdomains = [];
        foreach ($app['dbs']['sheaker']->fetchAll('SELECT * FROM reserved_subdomains rs') as $sub) {
            array_push($reserved_subdomains, $sub['subdomain']);
        }

        $infos = [];
        $infos['reservedSubdomains'] = $reserved_subdomains;

        return json_encode($infos, JSON_NUMERIC_CHECK);
    }

    private function createElasticIndex($app, $clientIndex)
    {
        $params['index'] = $clientIndex;

        if ($app['elasticsearch.client']->indices()->exists(['index' => $params['index']])) {
            $app->abort(Response::HTTP_CONFLICT, 'Already exists');
        }

        $params['body']['mappings']['user'] = [
            '_source' => [
                'enabled' => true
            ],
            'properties' => [
                'birthdate' => [
                    'type'   => 'date',
                    'format' => 'date'
                ],
                'failed_logins' => [
                    'type' => 'integer',
                ],
                'payments' => [
                    'type' => 'nested',
                    'properties' => [
                        'days'  => [ 'type' => 'integer' ],
                        'price' => [ 'type' => 'integer' ]
                    ]
                ],
                'checkins' => [
                    'type' => 'nested'
                ]
            ]
        ];

        return $app['elasticsearch.client']->indices()->create($params);
    }
}
