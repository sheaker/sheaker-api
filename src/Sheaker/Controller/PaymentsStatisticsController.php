<?php

namespace Sheaker\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentsStatisticsController
{
    public function newMembershipsList(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        $params['body']  = [
            'query' => [
                'match_all' => new \stdClass()
            ],
            'sort' => [
                'payments.created_at' => 'desc'
            ],
            'size' => 10
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        // format elasticsearch response to something more pretty
        $response = [];
        foreach ($queryResponse['hits']['hits'] as $qr) {
            array_push($response, $qr['_source']);
        }

        foreach ($response as &$user) {
            $user['active_membership_id'] = null;
            foreach ($user['payments'] as $p) {
                if (strtotime($p['start_date']) < time() && time() < strtotime($p['end_date'])) {
                    $user['active_membership_id'] = $p['id'];
                }
            }

            unset($user['payments']);
        }

        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    public function endingMembershipsList(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        $params['body']  = [
            'query' => [
                'bool' => [
                    'must' => [
                        'nested' => [
                            'path'   =>'payments',
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        'range' => [
                                            'payments.end_date' => [
                                                'gte' => 'now',
                                                'lte' => 'now+3d'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'sort' => [
                'payments.end_date' => [
                    'order' => 'asc',
                    'nested_filter' => [
                        'range' => [
                            'payments.end_date' => [
                                'gte' => 'now',
                                'lte' => 'now+3d'
                            ]
                        ]
                    ]
                ]
            ],
            'size' => 10
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        // format elasticsearch response to something more pretty
        $response = [];
        foreach ($queryResponse['hits']['hits'] as $qr) {
            array_push($response, $qr['_source']);
        }

        return json_encode($response, JSON_NUMERIC_CHECK);
    }
}
