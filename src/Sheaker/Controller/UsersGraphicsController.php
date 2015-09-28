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

        $getParams = [];
        $getParams['fromDate'] = $app->escape($request->get('from_date'));
        $getParams['toDate']   = $app->escape($request->get('to_date',  date('c')));
        $getParams['interval'] = $app->escape($request->get('interval', 'month'));

        $queries = [];
        $queries['from_date']['range'] = [
            'created_at' => [
                'gte' => $getParams['fromDate']
            ]
        ];
        $queries['to_date']['range'] = [
            'created_at' => [
                'lte' => $getParams['toDate']
            ]
        ];

        $aggs = [];
        $aggs['new_users_over_time']['date_histogram'] = [
            'field'    => 'created_at',
            'interval' => $getParams['interval'],
            'format'   => 'YYYY-MM-dd'
        ];

        $params = [];
        $params['index']       = 'client_' . $app['client.id'];
        $params['type']        = 'user';
        $params['search_type'] = 'count';

        $params['body']['query']['bool']['must'] = [
            $queries['from_date'],
            $queries['to_date']
        ];
        $params['body']['aggs'] = [
            'new_users_over_time' => $aggs['new_users_over_time']
        ];

        //echo json_encode($params['body']);

        $queryResponse = $app['elasticsearch.client']->search($params);
        $queryResponse = $queryResponse['aggregations']['new_users_over_time'];

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

        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    public function getGenderRepartition(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $getParams = [];
        $getParams['fromDate'] = $app->escape($request->get('from_date'));
        $getParams['toDate']   = $app->escape($request->get('to_date',  date('c')));

        $filters = [];
        $filters['gender_m']['term'] = [
            'gender' => 0
        ];
        $filters['gender_f']['term'] = [
            'gender' => 1
        ];

        $params = [];
        $params['index']       = 'client_' . $app['client.id'];
        $params['type']        = 'user';
        $params['search_type'] = 'count';
        $params['body']['aggs'] = [
            'gender_m' => [
                'filter' => $filters['gender_m']
            ],
            'gender_f' => [
                'filter' => $filters['gender_f']
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
