<?php

namespace Sheaker\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentsGraphicsController
{
    public function getGains(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            throw new AppException(Response::HTTP_FORBIDDEN, 'Forbidden', 4007);
        }

        $getParams = [];
        $getParams['fromDate'] = $app->escape($request->get('from_date'));

        foreach ($getParams as $value) {
            if (!isset($value)) {
                throw new AppException(Response::HTTP_BAD_REQUEST, 'Missing parameters', 4008);
            }
        }

        $getParams['toDate']   = $app->escape($request->get('to_date',  date('c')));
        $getParams['interval'] = $app->escape($request->get('interval', 'month'));

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
        $aggs['over_time']['date_histogram'] = [
           'field'    => 'payments.created_at',
           'interval' => $getParams['interval'],
           'format'   => 'YYYY-MM-dd'
        ];
        $aggs['gain']['sum'] = [
            'field' => 'payments.price'
        ];

        $params = [
            'index'       => 'client_' . $app['client.id'],
            'type'        => 'user',
            'search_type' => 'count',
            'body'        => [
                'aggs' => [
                    'payments' => [
                        'nested' => [
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
                                    'over_time' => $aggs['over_time']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $params['body']['aggs']['payments']['aggs']['from_date']['aggs']['over_time']['aggs'] = [
            'gain' => $aggs['gain']
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);
        $queryResponse = $queryResponse['aggregations']['payments']['from_date']['over_time'];

        $response = [
            'labels' => [],
            'data'   => []
        ];

        $data = [];
        foreach ($queryResponse['buckets'] as $bucket) {
            array_push($response['labels'], $bucket['key_as_string']);
            array_push($data, $bucket['gain']['value']);
        }
        array_push($response['data'], $data);

        return $app->json($response, Response::HTTP_OK);
    }
}
