<?php

namespace Sheaker\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UsersStatisticsController
{
    public function usersStats(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $params = [];
        $params['index']       = 'client_' . $app['client.id'];
        $params['type']        = 'user';
        $params['search_type'] = 'count';
        $params['body']        = [
            'query' => [
                'match_all' => new \stdClass()
            ],
            'aggs' => [
                'total' => [
                    'value_count' => [
                        'field' => '_uid'
                    ]
                ],
                'active_memberships' => [
                    'filter' => [
                        'nested' => [
                            'path'   =>'payments',
                            'filter' => [
                                'bool' => [
                                    'must' => [
                                        [
                                            'range' => [
                                                'payments.start_date' => [
                                                    'lte' => 'now'
                                                ]
                                            ]
                                        ],
                                        [
                                            'range' => [
                                                'payments.end_date' => [
                                                    'gte' => 'now'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'staff_total' => [
                    'filter' => [
                        'range' => [
                            'user_level' => [
                                'gt' => 0
                            ]
                        ]
                    ]
                ],
                'staff_user' => [
                    'filter' => [
                        'term' => [
                            'user_level' => 1
                        ]
                    ]
                ],
                'staff_modo' => [
                    'filter' => [
                        'term' => [
                            'user_level' => 2
                        ]
                    ]
                ],
                'staff_admin' => [
                    'filter' => [
                        'term' => [
                            'user_level' => 3
                        ]
                    ]
                ]
            ]
        ];

        $response = $app['elasticsearch.client']->search($params);

        return json_encode($response['aggregations'], JSON_NUMERIC_CHECK);
    }

    public function newUsersList(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        $params['body']  = [
            'query' => [
                'match_all' => new \stdClass()
            ],
            'sort' => [
                'created_at' => 'desc'
            ],
            'size' => 10
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        // format elasticsearch response to something more pretty
        $response = [];
        foreach ($queryResponse['hits']['hits'] as $qr) {
            array_push($response, $qr['_source']);
        }

        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    public function incBirthdaysUsersList(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        //$params['body']  = [
        //    'query' => [
        //        'match_all' => new \stdClass()
        //    ],
        //    'sort' => [
        //        'birthdate' => 'desc'
        //    ],
        //    'size' => 10
        //];

        $queryResponse = $app['elasticsearch.client']->search($params);

        // format elasticsearch response to something more pretty
        $response = [];
        foreach ($queryResponse['hits']['hits'] as $qr) {
            array_push($response, $qr['_source']);
        }

        return json_encode($response, JSON_NUMERIC_CHECK);
    }
}
