<?php

namespace Sheaker\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ElasticSearchController
{
    public function indexing(Request $request, Application $app)
    {
        //$token = $app['jwt']->getDecodedToken();

        //if (!in_array('admin', $token->user->permissions)) {
        //    $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        //}

        // First, delete existing index
        $params['index'] = 'client_' . $app->escape($request->get('id_client'));

        if ($app['elasticsearch.client']->indices()->exists(['index' => $params['index']]))
            $app['elasticsearch.client']->indices()->delete($params);

        // Then, create a new index with the mapping inside
        $params['body']['mappings']['user'] = [
            '_source' => [
                'enabled' => true
            ],
            'properties' => [
                'birthdate' => [
                    'type'   => 'date',
                    'format' => 'date'
                ],
                'payments' => [
                    'type' => 'nested',
                ],
                'checkins' => [
                    'type' => 'nested'
                ]
            ]
        ];
        if (!$app['elasticsearch.client']->indices()->exists(['index' => $params['index']]))
            $app['elasticsearch.client']->indices()->create($params);

        unset($params['body']['mappings']); // Delete mapping field from previous query
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
                    '_id' => $u->getId()
                ]
            ];
            $params['body'][] = [
                'id'               => $u->getId(),
                'custom_id'        => $u->getCustomId(),
                'first_name'       => $u->getFirstName(),
                'last_name'        => $u->getLastName(),
                'password'         => $u->getPassword(),
                'phone'            => $u->getphone(),
                'mail'             => $u->getMail(),
                'birthdate'        => ($u->getBirthdate() != '0000-00-00') ? $u->getBirthdate() : null,
                'address_street_1' => $u->getAddressStreet1(),
                'address_street_2' => $u->getAddressStreet2(),
                'city'             => $u->getCity(),
                'zip'              => $u->getZip(),
                'gender'           => $u->getGender(),
                'photo'            => $u->getPhoto(),
                'sponsor_id'       => $u->getSponsor(),
                'comment'          => $u->getComment(),
                'last_seen'        => ($u->getLastSeen() != '0000-00-00') ? $u->getLastSeen() : null,
                'last_ip'          => $u->getLastIP(),
                'failed_logins'    => $u->getFailedLogins(),
                'created_at'       => $u->getCreatedAt(),
                'user_level'       => $u->getUserLevel(),
                'payments'         => (count($payments)) ? $payments : new \stdClass(),
                'checkins'         => (count($checkins)) ? $checkins : new \stdClass()
            ];
        }

        $responses = $app['elasticsearch.client']->bulk($params);

        return json_encode($responses, JSON_NUMERIC_CHECK);
    }
}
