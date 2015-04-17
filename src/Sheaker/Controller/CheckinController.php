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

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $getParams = [];
        $getParams['user']   = $app->escape($request->get('user'));
        $getParams['limit']  = $app->escape($request->get('limit',  200));
        $getParams['offset'] = $app->escape($request->get('offset', 0));
        $getParams['sortBy'] = $app->escape($request->get('sortBy', 'created_at'));
        $getParams['order']  = $app->escape($request->get('order',  'DESC'));

        if ($getParams['user']) {
            $checkin = $app['repository.checkin']->findAllByUser($getParams['user'], $getParams['limit'], $getParams['offset'], array($getParams['sortBy'] => $getParams['order']));
        }
        else {
            $checkin = $app['repository.checkin']->findAll($getParams['limit'], $getParams['offset'], array($getParams['sortBy'] => $getParams['order']));
        }

        return json_encode(array_values($checkin), JSON_NUMERIC_CHECK);
    }

    public function getCheckin(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions)) {
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

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $addParams = [];
        $addParams['user'] = $app->escape($request->get('user'));

        foreach ($addParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        $user = $app['repository.user']->findById($addParams['user']);

        $checkin = new Checkin();
        $checkin->setUser($user);
        $checkin->setCreatedAt(date('c'));
        $app['repository.checkin']->save($checkin);

        return json_encode($checkin, JSON_NUMERIC_CHECK);
    }
}
