<?php

namespace Sheaker\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UsersGraphicsController
{
    public function getNewUsersFromDate(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $params = [];
        $params['index']       = 'client_' . $app['client.id'];
        $params['type']        = 'user';
        $params['search_type'] = 'count';
        $params['body']  = [
            'aggs' => [
                'new_users_over_time' => [
                   'date_histogram' => [
                       'field'    => 'created_at',
                       'interval' => 'month',
                       'format'   => 'YYYY-MM-dd'
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
        foreach ($queryResponse['aggregations']['new_users_over_time']['buckets'] as $bucket) {
            array_push($response['labels'], $bucket['key_as_string']);
            array_push($data, $bucket['doc_count']);
        }
        array_push($response['data'], $data);

        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    public function getGenderRepartition(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $params = [];
        $params['index']       = 'client_' . $app['client.id'];
        $params['type']        = 'user';
        $params['search_type'] = 'count';
        $params['body']  = [
            'aggs' => [
                'gender_m' => [
                    'filter' => [
                        'term' => [
                            'gender' => 0
                        ]
                    ]
                ],
                'gender_f' => [
                    'filter' => [
                        'term' => [
                            'gender' => 1
                        ]
                    ]
                ]
            ]
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        $response = [
            'labels' => ['Male', 'Female'],
            'data'   => [
                $queryResponse['aggregations']['gender_m']['doc_count'],
                $queryResponse['aggregations']['gender_f']['doc_count']
            ]
        ];

        return json_encode($response, JSON_NUMERIC_CHECK);
    }
}
