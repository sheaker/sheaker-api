<?php

namespace Sheaker\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckinsGraphicsController
{
    public function newCheckinsFromDate(Request $request, Application $app)
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

        $getParams['interval'] = $app->escape($request->get('interval', 'month'));

        $params = [];
        $params['index']       = 'client_' . $app['client.id'];
        $params['type']        = 'user';
        $params['search_type'] = 'count';
        $params['body']  = [
            'aggs' => [
                'checkins' => [
                    'nested' => [
                        'path' => 'checkins'
                    ],
                    'aggs' => [
                        'new_checkins_since' => [
                            'filter' => [
                                'range' => [
                                    'checkins.created_at' => [
                                        'gte' => $getParams['fromDate']
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'new_checkins_over_time' => [
                                    'date_histogram' => [
                                        'field'    => 'checkins.created_at',
                                        'interval' => $getParams['interval'],
                                        'format'   => 'YYYY-MM-dd'
                                    ]
                                ]
                            ]
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
        foreach ($queryResponse['aggregations']['checkins']['new_checkins_since']['new_checkins_over_time']['buckets'] as $bucket) {
            array_push($response['labels'], $bucket['key_as_string']);
            array_push($data, $bucket['doc_count']);
        }
        array_push($response['data'], $data);

        return json_encode($response, JSON_NUMERIC_CHECK);
    }
}
