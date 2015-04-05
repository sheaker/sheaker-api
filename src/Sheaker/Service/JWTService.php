<?php

namespace Sheaker\Service;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a way to handle JWT a bit more properly
 */
class JWTService
{
    /**
     * @var Application
     */
    protected $app;

    protected $decodedToken;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->client = $app['client']->getClient();
    }

    public function createToken(Request $request, $exp, $user)
    {
        $idClient = $this->app->escape($request->get('id_client'));
        if (!isset($idClient)) {
            $this->app->abort(Response::HTTP_UNAUTHORIZED, 'No client specified');
        }

        $payload = [
            'iss' => $request->getClientIp(),
            'iat' => time(),
            'exp' => $exp,
            'user' => $user
        ];

        $token = \JWT::encode($payload, $this->client->secretKey);

        return $token;
    }

    public function checkTokenAuthenticity(Request $request)
    {
        // Authorization shouldn't being able to be retrieve here, but rewrite magic happen in vhost configuration
        $authorizationHeader = $request->headers->get('Authorization');
        if ($authorizationHeader == null) {
            $this->app->abort(Response::HTTP_UNAUTHORIZED, 'No authorization header sent');
        }

        // $authorizationHeader should be in that form: "Bearer {THE_TOKEN}"
        $token = explode(' ', $authorizationHeader)[1];
        try {
            $decoded_token = \JWT::decode($token, $this->client->secretKey);
        }
        catch (UnexpectedValueException $ex) {
            $this->app->abort(Response::HTTP_UNAUTHORIZED, 'Invalid token');
        }

        $this->decodedToken = $decoded_token;
    }

    public function getDecodedToken()
    {
        return $this->decodedToken;
    }
}
