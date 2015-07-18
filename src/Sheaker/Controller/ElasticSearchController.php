<?php

namespace Sheaker\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ElasticSearchController
{
    public function indexing(Request $request, Application $app)
    {
        //$token = $app['jwt']->getDecodedToken();

        //if (!in_array('admin', $token->user->permissions)) {
        //    $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        //}

        $params['index'] = 'client_' . $app->escape($request->get('id_client'));
        //$app['elasticsearch.client']->indices()->delete($params['index']);
        //$app['elasticsearch.client']->indices()->create($params['index']);

        $params['type']  = 'user';

        $users = $app['repository.user']->findAll(0, 0, ['created_at' => 'asc']);
        foreach ($users as $u)
        {
            $payments = [];
            foreach ($app['repository.payment']->findAll(0, 0, ['created_at' => 'asc'], ['user_id' => $u->getId()]) as $p) {
                array_push($payments, [
                        'id'             => $p->getId(),
                        'start_date'     => $p->getStartDate(),
                        'end_date'       => $p->getEndDate(),
                        'days'           => $p->getDays(),
                        'price'          => $p->getPrice(),
                        'payment_method' => $p->getMethod(),
                        'comment'        => $p->getComment(),
                        'created_at'     => $p->getPaymentDate()
                    ]
                );
            }

            $checkins = [];
            foreach ($app['repository.checkin']->findAll(0, 0, ['created_at' => 'asc'], ['user_id' => $u->getId()]) as $ci) {
                array_push($checkins, [
                        'id'         => $ci->getId(),
                        'created_at' => $ci->getCreatedAt()
                    ]
                );
            }

            $params['body'][] = [
                'index' => [
                    '_id' => $u->getId()
                ]
            ];
            $params['body'][] = [
                'custom_id'        => $u->getCustomId(),
                'first_name'       => $u->getFirstName(),
                'last_name'        => $u->getLastName(),
                'password'         => $u->getPassword(),
                'phone'            => $u->getphone(),
                'mail'             => $u->getMail(),
                'birthdate'        => ($u->getBirthdate() != '0000-00-00') ? $u->getBirthdate() : null,
                'address_street_1' => $u->getAddressStreet1(),
                'address_street_2' => $u->getAddressStreet2(),
                'city'             => $u->getCity(),
                'zip'              => $u->getZip(),
                'gender'           => $u->getGender(),
                'photo'            => $u->getPhoto(),
                'sponsor_id'       => $u->getSponsor(),
                'comment'          => $u->getComment(),
                'last_seen'        => ($u->getLastSeen() != '0000-00-00') ? $u->getLastSeen() : null,
                'last_ip'          => $u->getLastIP(),
                'failed_logins'    => $u->getFailedLogins(),
                'payments'         => $payments,
                'checkins'         => $checkins
            ];
        }

        $responses = $app['elasticsearch.client']->bulk($params);

        return json_encode($responses, JSON_NUMERIC_CHECK);
    }
}
