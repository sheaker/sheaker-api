<?php

namespace Sheaker\Controller;

use Sheaker\Entity\Payment;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController
{
    public function getPaymentsList(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $getParams = [];
        $getParams['user']   = $app->escape($request->get('user'));
        $getParams['limit']  = $app->escape($request->get('limit',  200));
        $getParams['offset'] = $app->escape($request->get('offset', 0));
        $getParams['sortBy'] = $app->escape($request->get('sortBy', 'created_at'));
        $getParams['order']  = $app->escape($request->get('order',  'DESC'));

        if ($getParams['user']) {
            $users = $app['repository.payment']->findAll($getParams['limit'], $getParams['offset'], array($getParams['sortBy'] => $getParams['order']), array('user_id' => $getParams['user']));
        }
        else {
            $users = $app['repository.payment']->findAll($getParams['limit'], $getParams['offset'], array($getParams['sortBy'] => $getParams['order']));
        }

        return json_encode(array_values($users), JSON_NUMERIC_CHECK);
    }

    public function getPayment(Request $request, Application $app)
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

        $payment = $app['repository.payment']->find($getParams['id']);
        if (!$payment) {
            $app->abort(Response::HTTP_NOT_FOUND, 'Payment not found');
        }

        return json_encode($payment, JSON_NUMERIC_CHECK);
    }

    public function addPayment(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $addParams = [];
        $addParams['user']      = $app->escape($request->get('user'));
        $addParams['days']      = $app->escape($request->get('days'));
        $addParams['startDate'] = $app->escape($request->get('startDate'));
        $addParams['endDate']   = $app->escape($request->get('endDate'));
        $addParams['price']     = $app->escape($request->get('price'));
        $addParams['method']    = $app->escape($request->get('method'));

        foreach ($addParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        $addParams['comment'] = $app->escape($request->get('comment'));

        $payment = new Payment();
        $payment->setUserId($addParams['user']);
        $payment->setDays($addParams['days']);
        $payment->setStartDate(date('c', strtotime($addParams['startDate'])));
        $payment->setEndDate(date('c', strtotime($addParams['endDate'])));
        $payment->setComment($addParams['comment']);
        $payment->setPrice($addParams['price']);
        $payment->setMethod($addParams['method']);
        $payment->setPaymentDate(date('c'));
        $app['repository.payment']->save($payment);

        return json_encode($payment, JSON_NUMERIC_CHECK);
    }

    /*
     * Stats
     */
    public function newMemberships(Request $request, Application $app)
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
                'payments.created_at' => 'desc'
            ],
            'size' => 10
        ];

        $response = $app['elasticsearch.client']->search($params);

        return json_encode(array_values($response['hits']['hits']), JSON_NUMERIC_CHECK);
    }

    public function endingMemberships(Request $request, Application $app)
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
                'bool' => [
                    'must' => [
                        'nested' => [
                            'path'   =>'payments',
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        'range' => [
                                            'payments.end_date' => [
                                                'gte' => 'now',
                                                'lte' => 'now+3d'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'sort' => [
                'payments.end_date' => 'asc'
            ],
            'size' => 10
        ];

        $response = $app['elasticsearch.client']->search($params);

        return json_encode(array_values($response['hits']['hits']), JSON_NUMERIC_CHECK);
    }
}
