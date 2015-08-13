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
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        $params['id']    = $user_id;

        $queryResponse = $app['elasticsearch.client']->get($params);

        return json_encode($queryResponse['_source']['payments'], JSON_NUMERIC_CHECK);
    }

    public function getPayment(Request $request, Application $app, $payment_id)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $payment = $app['repository.payment']->find($payment_id);
        if (!$payment) {
            $app->abort(Response::HTTP_NOT_FOUND, 'Payment not found');
        }

        return json_encode($payment, JSON_NUMERIC_CHECK);
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
        $payment->setCreatedAt(date('c'));
        $app['repository.payment']->save($payment);

        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
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

        $newPayment = [
            'id'             => $payment->getId(),
            'start_date'     => $payment->getStartDate(),
            'end_date'       => $payment->getEndDate(),
            'days'           => $payment->getDays(),
            'price'          => $payment->getPrice(),
            'payment_method' => $payment->getMethod(),
            'comment'        => $payment->getComment(),
            'created_at'     => $payment->getCreatedAt()
        ];

        // update the user payments with the new payment
        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        $params['id']    = $user_id;
        $params['body']  = [
            'doc' => [
                'payments' => array_push($user['payments'], $newPayment)
            ]
        ];
        $app['elasticsearch.client']->update($params);

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
        $params['index'] = 'client_' . $app['client.id'];
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

        $queryResponse = $app['elasticsearch.client']->search($params);

        // format elasticsearch response to something more pretty
        $response = [];
        foreach ($queryResponse['hits']['hits'] as $qr) {
            array_push($response, $qr['_source']);
        }

        foreach ($response as &$user) {
            $user['active_membership_id'] = null;
            foreach ($user['payments'] as $p) {
                if (strtotime($p['start_date']) < time() && time() < strtotime($p['end_date'])) {
                    $user['active_membership_id'] = $p['id'];
                }
            }

            unset($user['payments']);
        }

        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    public function endingMemberships(Request $request, Application $app)
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
                'payments.end_date' => [
                    'order' => 'asc',
                    'nested_filter' => [
                        'range' => [
                            'payments.end_date' => [
                                'gte' => 'now',
                                'lte' => 'now+3d'
                            ]
                        ]
                    ]
                ]
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
