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
        $getParams['interval'] = $app->escape($request->get('interval', 'month'));
        $getParams['fromDate'] = $app->escape($request->get('from_date'));

        $aggs = [
            'payments' => [
                'nested' => [
                    'path' => 'payments'
                ]
            ],
            'from_date' => [
                'filter' => [
                    'range' => [
                        'payments.created_at' => [
                            'gte' => $getParams['fromDate']
                        ]
                    ]
                ]
            ],
            'new_payments_over_time' => [
               'date_histogram' => [
                   'field'    => 'payments.created_at',
                   'interval' => $getParams['interval'],
                   'format'   => 'YYYY-MM-dd'
                ]
            ],
            'monthly_gain' => [
               'sum' => [
                   'field' => 'payments.price'
                ]
            ]
        ];

        $params = [];
        $params['index']       = 'client_' . $app['client.id'];
        $params['type']        = 'user';
        $params['search_type'] = 'count';
        $params['body']  = [
            'aggs' => [
                'payments' => array_merge($aggs['payments'], [
                    'aggs' => ($getParams['fromDate']) ? [
                        'from_date' => array_merge($aggs['from_date'], [
                                'aggs' => [
                                    'new_payments_over_time' => array_merge($aggs['new_payments_over_time'], [
                                        'aggs' => [
                                            'monthly_gain' => $aggs['monthly_gain']
                                        ]
                                    ])
                                ]
                            ])
                    ] : [
                        'new_payments_over_time' => array_merge($aggs['new_payments_over_time'], [
                            'aggs' => [
                                'monthly_gain' => $aggs['monthly_gain']
                            ]
                        ])
                    ]
                ])
            ]
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);
        $queryResponse = ($getParams['fromDate']) ? $queryResponse['aggregations']['payments']['from_date']['new_payments_over_time'] : $queryResponse['aggregations']['payments']['new_payments_over_time'];

        $response = [
            'labels' => [],
            'data'   => []
        ];

        $data = [];
        foreach ($queryResponse['buckets'] as $bucket) {
            array_push($response['labels'], $bucket['key_as_string']);
            array_push($data, $bucket['monthly_gain']['value']);
        }
        array_push($response['data'], $data);

        return json_encode($response, JSON_NUMERIC_CHECK);
    }
}
