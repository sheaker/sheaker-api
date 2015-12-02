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

        $getParams['toDate']   = $app->escape($request->get('to_date', date('c')));

        $filters = [];
        $filters['from_date']['range'] = [
            'payments.created_at' => [
                'gte' => $getParams['fromDate']
            ]
        ];
        $filters['to_date']['range'] = [
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
                'aggs' => [
                    'payments' => [
                        'nested'  => [
                            'path' => 'payments'
                        ],
                        'aggs' => [
                            'from_date' => [
                                'filter' => [
                                    'bool' => [
                                        'must' => [ $filters['from_date'], $filters['to_date'] ]
                                    ]
                                ],
                                'aggs' => [
                                    'gains' => $aggs['gains']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        return $app->json($queryResponse['aggregations']['payments']['from_date']['gains'], Response::HTTP_OK);
    }
}
