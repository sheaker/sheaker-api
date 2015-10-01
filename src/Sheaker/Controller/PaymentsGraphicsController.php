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
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $getParams = [];
        $getParams['fromDate'] = $app->escape($request->get('from_date'));
        $getParams['toDate']   = $app->escape($request->get('to_date',  date('c')));
        $getParams['interval'] = $app->escape($request->get('interval', 'month'));

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
                                        'must' => [ $queries['from_date'], $queries['to_date'] ]
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

        return json_encode($response, JSON_NUMERIC_CHECK);
    }
}
