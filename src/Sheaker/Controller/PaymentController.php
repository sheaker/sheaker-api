<?php

namespace Sheaker\Controller;

use Sheaker\Entity\Payment;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController
{
    public function getPaymentsListByUser(Request $request, Application $app, $user_id)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $params = [];
        $params['index'] = 'client_' . $app->escape($request->get('id_client'));
        $params['type']  = 'user';
        $params['id']    = $user_id;

        $queryResponse = $app['elasticsearch.client']->get($params);

        return json_encode($queryResponse['_source']['payments'], JSON_NUMERIC_CHECK);
    }

    public function addPayment(Request $request, Application $app, $user_id)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $addParams = [];
        $addParams['days']      = $app->escape($request->get('days'));
        $addParams['startDate'] = $app->escape($request->get('start_date'));
        $addParams['endDate']   = $app->escape($request->get('end_date'));
        $addParams['price']     = $app->escape($request->get('price'));
        $addParams['method']    = $app->escape($request->get('method'));

        foreach ($addParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        $addParams['comment'] = $app->escape($request->get('comment'));

        $payment = new Payment();
        $payment->setUserId($user_id);
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
