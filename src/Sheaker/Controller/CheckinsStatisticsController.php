<?php

namespace Sheaker\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckinsStatisticsController
{
    public function getCheckinsFromDate(Request $request, Application $app)
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

        $getParams['toDate'] = $app->escape($request->get('to_date', date('c')));

        $params = [];
        $params['index']       = 'client_' . $app['client.id'];
        $params['type']        = 'user';
        $params['search_type'] = 'count';
        $params['body']        = [
            'query' => [
                'nested' => [
                    'path' => 'checkins',
                    'query' => [
                        'bool' => [
                            'must' => [
                                [
                                    'range' => [
                                        'checkins.created_at' => [
                                            'gte'    => $getParams['fromDate'],
                                            'lte'    => $getParams['toDate']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        return json_encode($queryResponse['hits'], JSON_NUMERIC_CHECK);
    }
}
