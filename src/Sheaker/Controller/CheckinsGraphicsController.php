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
            throw new AppException(Response::HTTP_FORBIDDEN, 'Forbidden', 4011);
        }

        $getParams = [];
        $getParams['fromDate'] = $app->escape($request->get('from_date'));

        foreach ($getParams as $value) {
            if (!isset($value)) {
                throw new AppException(Response::HTTP_BAD_REQUEST, 'Missing parameters', 4012);
            }
        }

        $getParams['toDate']   = $app->escape($request->get('to_date',  date('c')));
        $getParams['interval'] = $app->escape($request->get('interval', 'month'));

        $filters = [];
        $filters['from_date']['range'] = [
            'checkins.created_at' => [
                'gte' => $getParams['fromDate']
            ]
        ];
        $filters['to_date']['range'] = [
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
                'aggs' => [
                    'checkins' => [
                        'nested' => [
                            'path' => 'checkins'
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

        $queryResponse = $app['elasticsearch.client']->search($params);
        $queryResponse = $queryResponse['aggregations']['checkins']['from_date']['over_time'];

        $response = [
            'labels' => [],
            'data'   => []
        ];

        $data = [];
        foreach ($queryResponse['buckets'] as $bucket) {
            array_push($response['labels'], $bucket['key_as_string']);
            array_push($data, $bucket['doc_count']);
        }
        array_push($response['data'], $data);

        return $app->json($response, Response::HTTP_OK);
    }
}
