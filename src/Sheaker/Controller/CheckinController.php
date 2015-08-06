<?php

namespace Sheaker\Controller;

use Sheaker\Entity\Checkin;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckinController
{
    public function getCheckinsListByUser(Request $request, Application $app, $user_id)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)){
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $params = [];
        $params['index'] = 'client_' . $app->escape($request->get('id_client'));
        $params['type']  = 'user';
        $params['id']    = $user_id;

        $queryResponse = $app['elasticsearch.client']->get($params);

        return json_encode($queryResponse['_source']['checkins'], JSON_NUMERIC_CHECK);
    }

    public function addCheckin(Request $request, Application $app, $user_id)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $checkin = new Checkin();
        $checkin->setUserId($user_id);
        $checkin->setCreatedAt(date('c'));
        $app['repository.checkin']->save($checkin);

        $params = [];
        $params['index'] = 'client_' . $app->escape($request->get('id_client'));
        $params['type']  = 'user';
        $params['body'] = [
            'query' => [
                'filtered' => [
                    'filter' => [
                        'term' => [
                            'custom_id' => $user_id
                        ]
                    ]
                ]
            ]
        ];

        // search first one user with this custom id...
        $queryResponse = $app['elasticsearch.client']->search($params);

        if (!isset($queryResponse['hits']['hits'][0])) {
            $params['body'] = [
                'query' => [
                    'filtered' => [
                        'filter' => [
                            'term' => [
                                'id' => $user_id
                            ]
                        ]
                    ]
                ]
            ];

            // ...otherwise search one user with this id
            $queryResponse = $app['elasticsearch.client']->search($params);
        }

        // There should have only 1 user, no need to iterate
        $user = $queryResponse['hits']['hits'][0]['_source'];

        $newCheckin = [
            'id'             => $checkin->getId(),
            'created_at'     => $checkin->getCreatedAt()
        ];

        // update the user checkins with the new checkin
        $params = [];
        $params['index'] = 'client_' . $app->escape($request->get('id_client'));
        $params['type']  = 'user';
        $params['id']    = $user_id;
        $params['body']  = [
            'doc' => [
                'checkins' => array_push($user['checkins'], $newCheckin)
            ]
        ];
        $app['elasticsearch.client']->update($params);

        return json_encode($checkin, JSON_NUMERIC_CHECK);
    }

    /*
     * Stats
     */
    public function newCheckins(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $params = [];
        $params['index'] = 'client_' . $app->escape($request->get('id_client'));
        $params['type']  = 'user';
        $params['body']  = [
            'query' => [
                'match_all' => new \stdClass()
            ],
            'sort' => [
                'checkins.created_at' => 'desc'
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
}
