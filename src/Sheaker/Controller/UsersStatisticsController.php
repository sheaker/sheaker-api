<?php

namespace Sheaker\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UsersStatisticsController
{
    public function getActiveUsers(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            throw new AppException(Response::HTTP_FORBIDDEN, 'Forbidden', 4004);
        }

        $getParams = [];
        $getParams['fromDate'] = $app->escape($request->get('from_date', date('c')));
        $getParams['toDate']   = $app->escape($request->get('to_date',  date('c')));

        $queries = [];
        $queries['from_date']['range'] = [
            'payments.start_date' => [
                'lte' => $getParams['fromDate']
            ]
        ];
        $queries['to_date']['range'] = [
            'payments.end_date' => [
                'gte' => $getParams['toDate']
            ]
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
                ]
            ]
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        return $app->json($queryResponse['hits'], Response::HTTP_OK);
    }

    public function getNewUsersFromDate(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            throw new AppException(Response::HTTP_FORBIDDEN, 'Forbidden', 4005);
        }

        $getParams = [];
        $getParams['fromDate'] = $app->escape($request->get('from_date'));

        foreach ($getParams as $value) {
            if (!isset($value)) {
                throw new AppException(Response::HTTP_BAD_REQUEST, 'Missing parameters', 4006);
            }
        }

        $getParams['toDate'] = $app->escape($request->get('to_date', date('c')));

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

        $params = [
            'index'       => 'client_' . $app['client.id'],
            'type'        => 'user',
            'search_type' => 'count',
            'body'        => [
                'query' => [
                    'bool' => [
                        'must' => [ $queries['from_date'], $queries['to_date'] ]
                    ]
                ]
            ]
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        return $app->json($queryResponse['hits'], Response::HTTP_OK);
    }
}
