<?php

namespace Sheaker\Controller;

use Sheaker\Entity\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController
{
    public function getUsersList(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $getParams = [];
        $getParams['limit']  = $app->escape($request->get('limit'));
        $getParams['offset'] = $app->escape($request->get('offset', 0));
        $getParams['sortBy'] = $app->escape($request->get('sortBy', 'created_at'));
        $getParams['order']  = $app->escape($request->get('order',  'DESC'));

        $users = $app['repository.user']->findAll($getParams['limit'], $getParams['offset'], array($getParams['sortBy'] => $getParams['order']));

        foreach ($users as $user) {
            $user->setPayments($app['repository.payment']->find($user->activeMembershipId));
        }

        return json_encode(array_values($users), JSON_NUMERIC_CHECK);
    }

    public function getUser(Request $request, Application $app)
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

        $user = $app['repository.user']->findByCustomId($getParams['id']);
        if (!$user) {
            $user = $app['repository.user']->findById($getParams['id']);
            if (!$user || ($user->id && $user->customId)) {
                $app->abort(Response::HTTP_NOT_FOUND, 'User not found');
            }
        }

        $user->setPayments($app['repository.payment']->findAllByUser($user->id));

        return json_encode($user, JSON_NUMERIC_CHECK);
    }

    public function addUser(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $addParams = [];
        $addParams['firstName']      = $app->escape($request->get('firstName'));
        $addParams['lastName']       = $app->escape($request->get('lastName'));

        foreach ($addParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        $addParams['phone']          = $app->escape($request->get('phone'));
        $addParams['mail']           = $app->escape($request->get('mail'));
        $addParams['birthdate']      = $app->escape($request->get('birthdate'));
        $addParams['addressStreet1'] = $app->escape($request->get('addressStreet1'));
        $addParams['addressStreet2'] = $app->escape($request->get('addressStreet2'));
        $addParams['city']           = $app->escape($request->get('city'));
        $addParams['zip']            = $app->escape($request->get('zip'));
        $addParams['gender']         = $app->escape($request->get('gender', -1));
        $addParams['customId']       = $app->escape($request->get('customId', 0));
        $addParams['photo']          = $app->escape($request->get('photo'));
        $addParams['sponsor']        = $app->escape($request->get('sponsor', 0));
        $addParams['comment']        = $app->escape($request->get('comment'));

        $photoPath = '';
        if (!empty($addParams['photo'])) {
            $clientPhotosPath = 'photos/' . $app->escape($request->get('client'));
            if (!file_exists($clientPhotosPath)) {
                mkdir($clientPhotosPath);
            }

            $photoPath = $clientPhotosPath . '/' . uniqid() . '.png';
            list($photoType, $addParams['photo']) = explode(';', $addParams['photo']);
            list(, $addParams['photo'])           = explode(',', $addParams['photo']);
            file_put_contents($photoPath, base64_decode($addParams['photo']));
        }

        $generatedPassword = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?"), 0, 6);

        $user = new User();
        $user->setCustomId($addParams['customId']);
        $user->setFirstName($addParams['firstName']);
        $user->setLastName($addParams['lastName']);
        $user->setPassword(password_hash($generatedPassword, PASSWORD_DEFAULT));
        $user->setPhone($addParams['phone']);
        $user->setMail($addParams['mail']);
        $user->setBirthdate(date('Y-m-d H:i:s', strtotime($addParams['birthdate'])));
        $user->setAddressStreet1($addParams['addressStreet1']);
        $user->setAddressStreet2($addParams['addressStreet2']);
        $user->setCity($addParams['city']);
        $user->setZip($addParams['zip']);
        $user->setGender($addParams['gender']);
        $user->setSponsor($addParams['sponsor']);
        $user->setComment($addParams['comment']);
        $user->setLastSeen('0000-00-00 00:00:00');
        $user->setLastIP('0.0.0.0');
        $user->setSubscriptionDate(date('c'));
        $user->setFailedLogins(0);
        $user->setPhoto($photoPath);
        $app['repository.user']->save($user);

        return json_encode($user, JSON_NUMERIC_CHECK);
    }

    public function editUser(Request $request, Application $app)
    {
        $token = $app['jwt']->getDecodedToken();

        if (!in_array('admin', $token->user->permissions) && !in_array('modo', $token->user->permissions)) {
            $app->abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        $editParams = [];
        $editParams['id']             = $app->escape($request->get('id'));
        $editParams['firstName']      = $app->escape($request->get('firstName'));
        $editParams['lastName']       = $app->escape($request->get('lastName'));

        foreach ($editParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        $editParams['phone']          = $app->escape($request->get('phone'));
        $editParams['mail']           = $app->escape($request->get('mail'));
        $editParams['birthdate']      = $app->escape($request->get('birthdate'));
        $editParams['addressStreet1'] = $app->escape($request->get('addressStreet1'));
        $editParams['addressStreet2'] = $app->escape($request->get('addressStreet2'));
        $editParams['city']           = $app->escape($request->get('city'));
        $editParams['zip']            = $app->escape($request->get('zip'));
        $editParams['gender']         = $app->escape($request->get('gender', -1));
        $editParams['customId']       = $app->escape($request->get('customId', 0));
        $editParams['photo']          = $app->escape($request->get('photo'));
        $editParams['sponsor']        = $app->escape($request->get('sponsor', 0));
        $editParams['comment']        = $app->escape($request->get('comment'));

        $user = $app['repository.user']->findById($editParams['id']);
        if (!$user) {
            $app->abort(Response::HTTP_NOT_FOUND, 'User not found');
        }

        $photoPath = '';
        if (!empty($editParams['photo'])) {
            if (file_exists($user->getPhoto())) {
                unlink($user->getPhoto());
            }

            $clientPhotosPath = 'photos/' . $app->escape($request->get('client'));
            if (!file_exists($clientPhotosPath)) {
                mkdir($clientPhotosPath);
            }

            $photoPath = $clientPhotosPath . '/' . uniqid() . '.png';
            list($photoType, $editParams['photo']) = explode(';', $editParams['photo']);
            list(, $editParams['photo'])           = explode(',', $editParams['photo']);
            file_put_contents($photoPath, base64_decode($editParams['photo']));
        }

        $user->setCustomId($editParams['customId']);
        $user->setFirstName($editParams['firstName']);
        $user->setLastName($editParams['lastName']);
        $user->setPhone($editParams['phone']);
        $user->setMail($editParams['mail']);
        $user->setBirthdate($editParams['birthdate']);
        $user->setAddressStreet1($editParams['addressStreet1']);
        $user->setAddressStreet2($editParams['addressStreet2']);
        $user->setCity($editParams['city']);
        $user->setZip($editParams['zip']);
        $user->setGender($editParams['gender']);
        $user->setSponsor($editParams['sponsor']);
        $user->setComment($editParams['comment']);
        $user->setPhoto($photoPath);
        $app['repository.user']->save($user);

        return json_encode($user, JSON_NUMERIC_CHECK);
    }
}
