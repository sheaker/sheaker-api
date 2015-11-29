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
            $user->setLastSeen(date('Y-m-d H:i:s'));
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

        return $app->json(['token' => $token], Response::HTTP_OK);
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

        return $app->json($response, Response::HTTP_OK);
    }

    public function searchUsers(Request $request, Application $app)
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

        return $app->json($response, Response::HTTP_OK);
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

        return $app->json($user, Response::HTTP_OK);
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

        $photoURL = '';
        if (!empty($addParams['photo'])) {
            $s3 = $app['aws']->createS3();

            $bucketName = ($app['debug']) ? 'sheaker-dev' : 'sheaker-' . md5('client_' . $app['client.id']);
            if (!$s3->doesBucketExist($bucketName)) {
                $s3->createBucket(['Bucket' => $bucketName]);
            }

            $image = explode(',', $addParams['photo']);
            if (preg_match('/\/(\w*);/', $image[0], $matches)) {
                $s3AddResult = $s3->putObject([
                    'Bucket' => $bucketName,
                    'Key'    => uniqid() . '.' . $matches[1],
                    'Body'   => base64_decode($image[1]),
                    'ACL'    => 'public-read',
                ]);

                $photoURL = $s3AddResult['ObjectURL'];
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
        $user->setPhoto($photoURL);
        $user->setSponsor(($addParams['sponsor']) ? $addParams['sponsor'] : null);
        $user->setComment($addParams['comment']);
        $user->setFailedLogins(0);
        $user->setLastSeen(null);
        $user->setLastIP('');
        $user->setCreatedAt(date('Y-m-d H:i:s'));
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
            'created_at'       => date('c', strtotime($user->getCreatedAt())),
            'user_level'       => $user->getUserLevel(),
            'payments'         => new \stdClass(),
            'checkins'         => new \stdClass()
        ];

        $app['elasticsearch.client']->index($params);

        return $app->json($user, Response::HTTP_CREATED);
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

        $photoURL = '';
        if (!empty($editParams['photo'])) {
            $s3 = $app['aws']->createS3();

            $bucketName = ($app['debug']) ? 'sheaker-dev' : 'sheaker-' . md5('client_' . $app['client.id']);
            if (!$s3->doesBucketExist($bucketName)) {
                $s3->createBucket(['Bucket' => $bucketName]);
            }

            $image = explode(',', $editParams['photo']);
            if (preg_match('/\/(\w*);/', $image[0], $matches)) {
                $photoName = basename($user->getPhoto());
                if ($photoName && $s3->doesObjectExist($bucketName, $photoName)) {
                    $s3DeleteResult = $s3->deleteObject([
                        'Bucket' => $bucketName,
                        'Key'    => $photoName
                    ]);
                }

                $s3AddResult = $s3->putObject([
                    'Bucket' => $bucketName,
                    'Key'    => uniqid() . '.' . $matches[1],
                    'Body'   => base64_decode($image[1]),
                    'ACL'    => 'public-read',
                ]);

                $photoURL = $s3AddResult['ObjectURL'];
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
        $user->setPhoto($photoURL);
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
            $params['body']['doc']['photo'] = $user->getPhoto();
        }

        $app['elasticsearch.client']->update($params);

        return $app->json($user, Response::HTTP_OK);
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

        $user->setDeletedAt(date('Y-m-d H:i:s'));
        $app['repository.user']->save($user);

        $params = [];
        $params['index'] = 'client_' . $app['client.id'];
        $params['type']  = 'user';
        $params['id']    = $user_id;
        $params['body']  = [
            'doc' => [
                'deleted_at' => date('c', strtotime($user->getDeletedAt()))
            ]
        ];

        $app['elasticsearch.client']->update($params);

        return $app->json(null, Response::HTTP_NO_CONTENT);
    }
}
