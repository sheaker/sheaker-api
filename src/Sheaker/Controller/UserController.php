<?php

namespace Sheaker\Controller;

use Sheaker\Entity\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController
{
    public function login(Request $request, Application $app)
    {
        $loginParams = [];
        $loginParams['id']       = $app->escape($request->get('id'));
        $loginParams['password'] = $app->escape($request->get('password'));

        foreach ($loginParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        $loginParams['rememberMe'] = $app->escape($request->get('rememberMe'));

        $user = $app['repository.user']->find($loginParams['id']);
        if (!$user) {
            $app->abort(Response::HTTP_NOT_FOUND, 'User not found');
        }

        if (password_verify($loginParams['password'], $user->getPassword())) {
            $user->setLastSeen(date('c', time()));
            $user->setLastIP($request->getClientIp());
            $user->setFailedLogins(0);
            $app['repository.user']->save($user);

            $exp = ($loginParams['rememberMe']) ? time() + 60 * 60 * 24 * 30 : time() + 60 * 60 * 24; // expire in 30 days or 24h
            $userToken = [
                'number'      => $user->getId(),
                'name'        => $user->getFirstName(),
                'lastname'    => $user->getLastname(),
                'permissions' => [
                    $app['api.accessLevels'][$user->getUserLevel()]
                ],
                'rememberMe'  => $loginParams['rememberMe']
            ];

            $token = $app['jwt']->createToken($request, $exp, $userToken);
        }
        else {
            $user->setFailedLogins($user->getFailedLogins() + 1);
            $app['repository.user']->save($user);
            $app->abort(Response::HTTP_FORBIDDEN, 'Wrong password');
        }

        return json_encode(['token' => $token], JSON_NUMERIC_CHECK);
    }

    public function renewToken(Request $request, Application $app)
    {
        $renewParams = [];
        $renewParams['idClient'] = $app->escape($request->get('id_client'));
        $renewParams['oldToken'] = $app->escape($request->get('oldToken'));

        foreach ($renewParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        $oldToken = \JWT::decode($renewParams['oldToken'], $app['client']->getClient()->secretKey, false);

        $exp = ($oldToken->user->rememberMe) ? time() + 60 * 60 * 24 * 30 : time() + 60 * 60 * 24; // expire in 30 days or 24h
        $newToken = $app['jwt']->createToken($request, $exp, $oldToken->user);

        return json_encode(['token' => $newToken], JSON_NUMERIC_CHECK);
    }

    public function getUsersList(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $getParams = [];
        $getParams['offset'] = $app->escape($request->get('offset', 0));
        $getParams['limit']  = $app->escape($request->get('limit',  5));
        $getParams['sortBy'] = $app->escape($request->get('sortBy', 'created_at'));
        $getParams['order']  = $app->escape($request->get('order',  'desc'));

        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        $params['body']  = [
            'query' => [
                'filtered' => [
                    'filter' => [
                        'missing' => [
                            'field' => 'deleted_at'
                        ]
                    ]
                ]
            ],
            'sort' => [
                [ $getParams['sortBy'] => $getParams['order'] ]
            ],
            'from' => $getParams['offset'],
            'size' => $getParams['limit']
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        // format elasticsearch response to something more pretty
        $response = [];
        foreach ($queryResponse['hits']['hits'] as $qr) {
            $user = $qr['_source'];

            $user['active_membership_id'] = null;
            foreach ($user['payments'] as $p) {
                if (strtotime($p['start_date']) <= time() && time() <= strtotime($p['end_date'])) {
                    $user['active_membership_id'] = $p['id'];
                }
            }

            // We 'normaly' don't need theses informations here
            unset($user['payments']);
            unset($user['checkins']);

            array_push($response, $user);
        }

        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    public function getUsersSearch(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $getParams = [];
        $getParams['query'] = $app->escape($request->get('query'));

        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        $params['body']  = [
            'query' => [
                'multi_match' => [
                    'fields'    => ['id', 'first_name', 'last_name'],
                    'query'     => $getParams['query'],
                    'fuzziness' => 'AUTO'
                ]
            ]
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        // format elasticsearch response to something more pretty
        $response = [];
        foreach ($queryResponse['hits']['hits'] as $qr) {
            $user = $qr['_source'];

            $user['active_membership_id'] = null;
            foreach ($user['payments'] as $p) {
                if (strtotime($p['start_date']) <= time() && time() <= strtotime($p['end_date'])) {
                    $user['active_membership_id'] = $p['id'];
                }
            }

            // We 'normaly' don't need theses informations here
            unset($user['payments']);
            unset($user['checkins']);

            array_push($response, $user);
        }

        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    public function getUser(Request $request, Application $app, $user_id)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

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

        if ($queryResponse['hits']['total'] === 0) {
            $app->abort(Response::HTTP_NOT_FOUND, 'User not found');
        }

        // There should have only 1 user, no need to iterate
        $user = $queryResponse['hits']['hits'][0]['_source'];

        $user['active_membership_id'] = null;
        foreach ($user['payments'] as $p) {
            if (strtotime($p['start_date']) <= time() && time() <= strtotime($p['end_date'])) {
                $user['active_membership_id'] = $p['id'];
            }
        }

        // Remove unneeded array which can be huge
        // use instead /user/{user_id}/{payments|checkins} if you really want them
        unset($user['payments']);
        unset($user['checkins']);

        return json_encode($user, JSON_NUMERIC_CHECK);
    }

    public function addUser(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $addParams = [];
        $addParams['firstName']      = $app->escape($request->get('first_name'));
        $addParams['lastName']       = $app->escape($request->get('last_name'));

        foreach ($addParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        $addParams['phone']          = $app->escape($request->get('phone'));
        $addParams['mail']           = $app->escape($request->get('mail'));
        $addParams['birthdate']      = $app->escape($request->get('birthdate'));
        $addParams['addressStreet1'] = $app->escape($request->get('address_street_1'));
        $addParams['addressStreet2'] = $app->escape($request->get('address_street_2'));
        $addParams['city']           = $app->escape($request->get('city'));
        $addParams['zip']            = $app->escape($request->get('zip'));
        $addParams['gender']         = $app->escape($request->get('gender'));
        $addParams['photo']          = $app->escape($request->get('photo'));
        $addParams['sponsor']        = $app->escape($request->get('sponsor'));
        $addParams['comment']        = $app->escape($request->get('comment'));
        $addParams['userLevel']      = $app->escape($request->get('user_level'));

        $photoPath = '';
        if (!empty($addParams['photo'])) {
            $clientPhotosPath = 'photos/' . $app->escape($request->get('id_client'));
            if (!file_exists($clientPhotosPath)) {
                mkdir($clientPhotosPath);
            }

            $image = explode(',', $addParams['photo']);
            if (preg_match('/\/(\w*);/', $image[0], $matches)) {
                $photoPath = $clientPhotosPath . '/' . uniqid() . '.' . $matches[1];
                file_put_contents($photoPath, base64_decode($image[1]));
            }
        }

        $generatedPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?'), 0, 6);

        $user = new User();
        $user->setFirstName($addParams['firstName']);
        $user->setLastName($addParams['lastName']);
        $user->setPassword(password_hash($generatedPassword, PASSWORD_DEFAULT));
        $user->setPhone($addParams['phone']);
        $user->setMail($addParams['mail']);
        $user->setBirthdate(($addParams['birthdate']) ? $addParams['birthdate'] : null);
        $user->setAddressStreet1($addParams['addressStreet1']);
        $user->setAddressStreet2($addParams['addressStreet2']);
        $user->setCity($addParams['city']);
        $user->setZip($addParams['zip']);
        $user->setGender(($addParams['gender'] != '') ? $addParams['gender'] : null);
        $user->setPhoto($photoPath);
        $user->setSponsor(($addParams['sponsor']) ? $addParams['sponsor'] : null);
        $user->setComment($addParams['comment']);
        $user->setFailedLogins(0);
        $user->setLastSeen(null);
        $user->setLastIP('');
        $user->setCreatedAt(date('c'));
        $user->setDeletedAt(null);
        $user->setUserLevel($addParams['userLevel']);
        $app['repository.user']->save($user);

        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        $params['id']    = $user->getId();
        $params['body']  = [
            'id'               => $user->getId(),
            'first_name'       => $user->getFirstName(),
            'last_name'        => $user->getLastName(),
            'password'         => $user->getPassword(),
            'phone'            => $user->getPhone(),
            'mail'             => $user->getMail(),
            'birthdate'        => $user->getBirthdate(),
            'address_street_1' => $user->getAddressStreet1(),
            'address_street_2' => $user->getAddressStreet2(),
            'city'             => $user->getCity(),
            'zip'              => $user->getZip(),
            'gender'           => $user->getGender(),
            'photo'            => $user->getPhoto(),
            'sponsor_id'       => $user->getSponsor(),
            'comment'          => $user->getComment(),
            'failed_logins'    => $user->getFailedLogins(),
            'last_seen'        => $user->getLastSeen(),
            'last_ip'          => $user->getLastIp(),
            'created_at'       => $user->getCreatedAt(),
            'user_level'       => $user->getUserLevel(),
            'payments'         => new \stdClass(),
            'checkins'         => new \stdClass()
        ];

        $app['elasticsearch.client']->index($params);

        return json_encode($user, JSON_NUMERIC_CHECK);
    }

    public function editUser(Request $request, Application $app, $user_id)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $editParams = [];
        $editParams['firstName']      = $app->escape($request->get('first_name'));
        $editParams['lastName']       = $app->escape($request->get('last_name'));

        foreach ($editParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        $editParams['phone']          = $app->escape($request->get('phone'));
        $editParams['mail']           = $app->escape($request->get('mail'));
        $editParams['birthdate']      = $app->escape($request->get('birthdate'));
        $editParams['addressStreet1'] = $app->escape($request->get('address_street_1'));
        $editParams['addressStreet2'] = $app->escape($request->get('address_street_2'));
        $editParams['city']           = $app->escape($request->get('city'));
        $editParams['zip']            = $app->escape($request->get('zip'));
        $editParams['gender']         = $app->escape($request->get('gender'));
        $editParams['photo']          = $app->escape($request->get('photo'));
        $editParams['sponsor']        = $app->escape($request->get('sponsor'));
        $editParams['comment']        = $app->escape($request->get('comment'));
        $editParams['userLevel']      = $app->escape($request->get('user_level'));

        $user = $app['repository.user']->find($user_id);
        if (!$user) {
            $app->abort(Response::HTTP_NOT_FOUND, 'User not found');
        }

        $photoPath = '';
        if (!empty($editParams['photo'])) {
            if (file_exists($user->getPhoto())) {
                unlink($user->getPhoto());
            }

            $clientPhotosPath = 'photos/' . $app->escape($request->get('id_client'));
            if (!file_exists($clientPhotosPath)) {
                mkdir($clientPhotosPath);
            }

            $image = explode(',', $editParams['photo']);
            if (preg_match('/\/(\w*);/', $image[0], $matches)) {
                $photoPath = $clientPhotosPath . '/' . uniqid() . '.' . $matches[1];
                file_put_contents($photoPath, base64_decode($image[1]));
            }
        }

        $user->setFirstName($editParams['firstName']);
        $user->setLastName($editParams['lastName']);
        $user->setPhone($editParams['phone']);
        $user->setMail($editParams['mail']);
        $user->setBirthdate(($editParams['birthdate']) ? $editParams['birthdate'] : null);
        $user->setAddressStreet1($editParams['addressStreet1']);
        $user->setAddressStreet2($editParams['addressStreet2']);
        $user->setCity($editParams['city']);
        $user->setZip($editParams['zip']);
        $user->setGender(($editParams['gender'] != '') ? $editParams['gender'] : null);
        $user->setPhoto($photoPath);
        $user->setSponsor(($editParams['sponsor']) ? $editParams['sponsor'] : null);
        $user->setComment($editParams['comment']);
        $user->setUserLevel($editParams['userLevel']);
        $app['repository.user']->save($user);

        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        $params['id']    = $user_id;
        $params['body']  = [
            'doc' => [
                'first_name'       => $user->getFirstName(),
                'last_name'        => $user->getLastName(),
                'phone'            => $user->getPhone(),
                'mail'             => $user->getMail(),
                'birthdate'        => $user->getBirthdate(),
                'address_street_1' => $user->getAddressStreet1(),
                'address_street_2' => $user->getAddressStreet2(),
                'city'             => $user->getCity(),
                'zip'              => $user->getZip(),
                'gender'           => $user->getGender(),
                'sponsor_id'       => $user->getSponsor(),
                'comment'          => $user->getComment(),
                'user_level'       => $user->getUserLevel()
            ]
        ];

        if (!empty($editParams['photo'])) {
            $params['body']['doc']['photo'] = $photoPath;
        }

        $app['elasticsearch.client']->update($params);

        return json_encode($user, JSON_NUMERIC_CHECK);
    }

    public function deleteUser(Request $request, Application $app, $user_id)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $user = $app['repository.user']->find($user_id);
        if (!$user) {
            $app->abort(Response::HTTP_NOT_FOUND, 'User not found');
        }

        $user->setDeletedAt(date('c', time()));
        $app['repository.user']->save($user);

        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        $params['id']    = $user_id;
        $params['body']  = [
            'doc' => [
                'deleted_at' => $user->getDeletedAt()
            ]
        ];

        $app['elasticsearch.client']->update($params);

        return json_encode($user, JSON_NUMERIC_CHECK);
    }

    /*
     * Stats
     */
    public function statsUsers(Request $request, Application $app)
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
                'filtered' => [
                    'filter' => [
                        'missing' => [
                            'field' => 'deleted_at'
                        ]
                    ]
                ]
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

    public function newUsers(Request $request, Application $app)
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
                'filtered' => [
                    'filter' => [
                        'missing' => [
                            'field' => 'deleted_at'
                        ]
                    ]
                ]
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

    public function incUsersBirthdays(Request $request, Application $app)
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

    public function newUsersGraph(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $params = [];
        $params['index']       = 'client_' . $app['client.id'];
        $params['type']        = 'user';
        $params['search_type'] = 'count';
        $params['body']  = [
            'aggs' => [
                'new_users_over_time' => [
                   'date_histogram' => [
                       'field'    => 'created_at',
                       'interval' => 'month',
                       'format'   => 'YYYY-MM-dd'
                    ]
                ]
            ]
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        $response = [
            'labels' => [],
            'data'   => []
        ];

        $data = [];
        foreach ($queryResponse['aggregations']['new_users_over_time']['buckets'] as $bucket) {
            array_push($response['labels'], $bucket['key_as_string']);
            array_push($data, $bucket['doc_count']);
        }
        array_push($response['data'], $data);

        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    public function userSexGraph(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions) && !in_array('user', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $params = [];
        $params['index']       = 'client_' . $app['client.id'];
        $params['type']        = 'user';
        $params['search_type'] = 'count';
        $params['body']  = [
            'aggs' => [
                'gender_m' => [
                    'filter' => [
                        'term' => [
                            'gender' => 0
                        ]
                    ]
                ],
                'gender_f' => [
                    'filter' => [
                        'term' => [
                            'gender' => 1
                        ]
                    ]
                ]
            ]
        ];

        $queryResponse = $app['elasticsearch.client']->search($params);

        $response = [
            'labels' => ['Male', 'Female'],
            'data'   => [
                $queryResponse['aggregations']['gender_m']['doc_count'],
                $queryResponse['aggregations']['gender_f']['doc_count']
            ]
        ];

        return json_encode($response, JSON_NUMERIC_CHECK);
    }
}
