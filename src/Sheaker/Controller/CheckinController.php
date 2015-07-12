<?php

namespace Sheaker\Controller;

use Sheaker\Entity\Checkin;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckinController
{

    public function getCheckinList(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)){
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $getParams = [];
        $getParams['user']   = $app->escape($request->get('user'));
        $getParams['offset'] = $app->escape($request->get('offset', 0));
        $getParams['limit']  = $app->escape($request->get('limit',  50));
        $getParams['sortBy'] = $app->escape($request->get('sortBy', 'created_at'));
        $getParams['order']  = $app->escape($request->get('order',  'desc'));

        /*
        if ($getParams['user']) {
            $checkin = $app['repository.checkin']->findAll($getParams['limit'], $getParams['offset'], array($getParams['sortBy'] => $getParams['order']), array('user_id' => $getParams['user']));
        }
        else {
            $checkin = $app['repository.checkin']->findAll($getParams['limit'], $getParams['offset'], array($getParams['sortBy'] => $getParams['order']));
        }*/

        $params = [];
        $params['index'] = 'client_' . $app->escape($request->get('id_client'));
        $params['type']  = 'checkin';
        $params['body']['from']  = $getParams['offset'];
        $params['body']['size']  = $getParams['limit'];
        $params['body']['query'] = [
            'has_parent' => [
                'type'  => 'user',
                'query' => [
                    'match_all' => new \stdClass()
                ],
                'inner_hits' => new \stdClass()
            ]
        ];
        $params['body']['sort'] = [
            [ $getParams['sortBy'] => $getParams['order'] ]
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        $results = [];
        foreach ($queryResponse['hits']['hits'] as $key => $doc) {
            // concat parent (user) to checkin results
            $doc['_source']['user'] = $doc['inner_hits']['user']['hits']['hits'][0]['_source'];
            array_push($results, $doc['_source']);
        }

        return json_encode(array_values($results), JSON_NUMERIC_CHECK);
    }

    public function getCheckin(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $getParams = [];
        $getParams['id'] = $app->escape($request->get('id'));

        foreach ($getParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        $checkin = $app['repository.checkin']->find($getParams['id']);
        if (!$checkin) {
            $app->abort(Response::HTTP_NOT_FOUND, 'Checkin not found');
        }

        return json_encode($checkin, JSON_NUMERIC_CHECK);
    }

    public function addCheckin(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $addParams = [];
        $addParams['user'] = $app->escape($request->get('user'));

        foreach ($addParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        $checkin = new Checkin();
        $checkin->setUserId($addParams['user']);
        $checkin->setCreatedAt(date('c'));
        $app['repository.checkin']->save($checkin);

        return json_encode($checkin, JSON_NUMERIC_CHECK);
    }
}
