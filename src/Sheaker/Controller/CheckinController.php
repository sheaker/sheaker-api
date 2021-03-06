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
            throw new AppException(Response::HTTP_FORBIDDEN, 'Forbidden', 3000);
        }

        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        $params['id']    = $user_id;

        $queryResponse = $app['elasticsearch.client']->get($params);

        return $app->json($queryResponse['_source']['checkins'], Response::HTTP_OK);
    }

    public function addCheckin(Request $request, Application $app, $user_id)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            throw new AppException(Response::HTTP_FORBIDDEN, 'Forbidden', 3001);
        }

        $checkin = new Checkin();
        $checkin->setUserId($user_id);
        $checkin->setCreatedAt(date('Y-m-d H:i:s'));
        $app['repository.checkin']->save($checkin);

        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
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

        // There should have only 1 user, no need to iterate
        $user = $queryResponse['hits']['hits'][0]['_source'];

        $newCheckin = [
            'id'             => $checkin->getId(),
            'created_at'     => date('c', strtotime($checkin->getCreatedAt()))
        ];
        array_push($user['checkins'], $newCheckin);

        // update the user checkins with the new checkin
        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        $params['id']    = $user_id;
        $params['body']  = [
            'doc' => [
                'checkins' => $user['checkins']
            ]
        ];
        $app['elasticsearch.client']->update($params);

        return $app->json($checkin, Response::HTTP_CREATED);
    }
}
