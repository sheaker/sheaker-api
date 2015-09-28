<?php

namespace Sheaker\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckinsGraphicsController
{
    public function getCheckinsFromDate(Request $request, Application $app)
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
            'checkins.created_at' => [
                'gte' => $getParams['fromDate']
            ]
        ];
        $queries['to_date']['range'] = [
            'checkins.created_at' => [
                'lte' => $getParams['toDate']
            ]
        ];

        $aggs = [];
        $aggs['over_time']['date_histogram'] = [
           'field'    => 'checkins.created_at',
           'interval' => $getParams['interval'],
           'format'   => 'YYYY-MM-dd'
        ];

        $params = [
            'index'       => 'client_' . $app['client.id'],
            'type'        => 'user',
            'search_type' => 'count',
            'body'        => [
                'query' => [
                    'nested' => [
                        'path' => 'checkins',
                        'query' => [
                            'bool' => [
                                'must' => [ $queries['from_date'], $queries['to_date'] ]
                            ]
                        ]
                    ]
                ],
                'aggs' => [
                    'checkins' => [
                        'nested'  => [
                            'path' => 'checkins'
                        ],
                        'aggs' => [
                            'over_time' => $aggs['over_time']
                        ]
                    ]
                ]
            ]
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        $response = [
            'labels' => [],
            'data'   => []
        ];

        $data = [];
        foreach ($queryResponse['aggregations']['checkins']['over_time']['buckets'] as $bucket) {
            array_push($response['labels'], $bucket['key_as_string']);
            array_push($data, $bucket['doc_count']);
        }
        array_push($response['data'], $data);

        return json_encode($response, JSON_NUMERIC_CHECK);
    }
}
