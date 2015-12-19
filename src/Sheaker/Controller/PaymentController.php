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
            throw new AppException(Response::HTTP_FORBIDDEN, 'Forbidden', 2000);
        }

        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        $params['id']    = $user_id;

        $queryResponse = $app['elasticsearch.client']->get($params);

        return $app->json($queryResponse['_source']['payments'], Response::HTTP_OK);
    }

    public function getPayment(Request $request, Application $app, $payment_id)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            throw new AppException(Response::HTTP_FORBIDDEN, 'Forbidden', 2001);
        }

        $payment = $app['repository.payment']->find($payment_id);
        if (!$payment) {
            throw new AppException(Response::HTTP_NOT_FOUND, 'Payment not found', 2002);
        }

        return $app->json($payment, Response::HTTP_OK);
    }

    public function addPayment(Request $request, Application $app, $user_id)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions)) {
            throw new AppException(Response::HTTP_FORBIDDEN, 'Forbidden', 2003);
        }

        $addParams = [];
        $addParams['days']      = $app->escape($request->get('days'));
        $addParams['startDate'] = $app->escape($request->get('start_date'));
        $addParams['endDate']   = $app->escape($request->get('end_date'));
        $addParams['price']     = $app->escape($request->get('price'));
        $addParams['method']    = $app->escape($request->get('method'));

        foreach ($addParams as $value) {
            if (!isset($value)) {
                throw new AppException(Response::HTTP_BAD_REQUEST, 'Missing parameters', 2004);
            }
        }

        $addParams['comment'] = $app->escape($request->get('comment'));

        $payment = new Payment();
        $payment->setUserId($user_id);
        $payment->setDays($addParams['days']);
        $payment->setStartDate(date('Y-m-d H:i:s', strtotime($addParams['startDate'])));
        $payment->setEndDate(date('Y-m-d H:i:s', strtotime($addParams['endDate'])));
        $payment->setComment($addParams['comment']);
        $payment->setPrice($addParams['price']);
        $payment->setMethod($addParams['method']);
        $payment->setCreatedAt(date('Y-m-d H:i:s'));
        $app['repository.payment']->save($payment);

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

        $newPayment = [
            'id'             => $payment->getId(),
            'start_date'     => date('c', strtotime($payment->getStartDate())),
            'end_date'       => date('c', strtotime($payment->getEndDate())),
            'days'           => $payment->getDays(),
            'price'          => $payment->getPrice(),
            'payment_method' => $payment->getMethod(),
            'comment'        => $payment->getComment(),
            'created_at'     => date('c', strtotime($payment->getCreatedAt()))
        ];
        array_push($user['payments'], $newPayment);

        // update the user payments with the new payment
        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        $params['id']    = $user_id;
        $params['body']  = [
            'doc' => [
                'payments' => $user['payments']
            ]
        ];
        $app['elasticsearch.client']->update($params);

        return $app->json($payment, Response::HTTP_CREATED);
    }
}
