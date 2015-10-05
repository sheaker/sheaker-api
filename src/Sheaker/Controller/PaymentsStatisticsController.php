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

        if (!in_array('admin', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $getParams = [];
        $getParams['fromDate'] = $app->escape($request->get('from_date'));

        foreach ($getParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        $getParams['fromDate'] = $app->escape($request->get('from_date'));
        $getParams['toDate']   = $app->escape($request->get('to_date',  date('c')));

        $queries = [];
        $queries['from_date']['range'] = [
            'payments.created_at' => [
                'gte' => $getParams['fromDate']
            ]
        ];
        $queries['to_date']['range'] = [
            'payments.created_at' => [
                'lte' => $getParams['toDate']
            ]
        ];

        $aggs = [];
        $aggs['gains']['sum'] = [
            'field' => 'payments.price'
        ];

        $params = [
            'index'       => 'client_' . $app['client.id'],
            'type'        => 'user',
            'search_type' => 'count',
            'body'        => [
                'query' => [
                    'nested' => [
                        'path' => 'payments',
                        'query' => [
                            'bool' => [
                                'must' => [ $queries['from_date'], $queries['to_date'] ]
                            ]
                        ]
                    ]
                ],
                'aggs' => [
                    'payments' => [
                        'nested'  => [
                            'path' => 'payments'
                        ],
                        'aggs' => [
                            'gains' => $aggs['gains']
                        ]
                    ]
                ]
            ]
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        return json_encode($queryResponse['aggregations']['payments']['gains'], JSON_NUMERIC_CHECK);
    }
}
