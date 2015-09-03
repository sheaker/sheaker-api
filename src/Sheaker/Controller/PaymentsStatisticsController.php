<?php

namespace Sheaker\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentsStatisticsController
{
    public function getGainsFromDate(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $getParams = [];
        $getParams['fromDate'] = $app->escape($request->get('from_date'));

        foreach ($getParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        $getParams['toDate'] = $app->escape($request->get('to_date', date('c')));

        $params = [];
        $params['index']       = 'client_' . $app['client.id'];
        $params['type']        = 'user';
        $params['search_type'] = 'count';
        $params['body']        = [
            'query' => [
                'nested' => [
                    'path' => 'payments',
                    'query' => [
                        'bool' => [
                            'must' => [
                                [
                                    'range' => [
                                        'payments.created_at' => [
                                            'gte'    => $getParams['fromDate'],
                                            'lte'    => $getParams['toDate']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'aggs' => [
                'payments' => [
                    'nested' => [
                        'path' => 'payments'
                    ],
                    'aggs' => [
                        'gains_from_date' => [
                            'filter' => [
                                'range' => [
                                    'payments.created_at' => [
                                        'gte'    => $getParams['fromDate'],
                                        'lte'    => $getParams['toDate']
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total' => [
                                    'sum' => [
                                        'field' => 'payments.price'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        return json_encode($queryResponse['aggregations']['payments']['gains_from_date'], JSON_NUMERIC_CHECK);
    }
}
