<?php

namespace Sheaker\Service;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \Firebase\JWT\JWT;

/**
 * Provides a way to handle JWT a bit more properly
 */
class JWTService
{
    /**
     * @var Application
     */
    protected $app;

    protected $client;

    protected $decodedToken;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->client = $app['client']->getClient();
    }

    public function createToken(Request $request, $exp, $user)
    {
        $idClient = $this->app->escape($request->get('id_client'));

        $rand_val = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);

        $payload = [
            'iss' => $request->getClientIp(),
            'sub' => '',
            'aud' => 'http://sheaker.com',
            'exp' => $exp,
            'nbf' => time(),
            'iat' => time(),
            'jti' => hash('sha256', time() . $rand_val),
            'user' => $user
        ];

        $token = JWT::encode($payload, $this->client->secretKey);

        return $token;
    }

    public function checkTokenAuthenticity(Request $request)
    {
        // Authorization shouldn't being able to be retrieve here, but rewrite magic happen in vhost configuration
        $authorizationHeader = $request->headers->get('Authorization');
        if ($authorizationHeader == null) {
            throw new AppException(Response::HTTP_UNAUTHORIZED, 'No authorization header sent', 5010);
        }

        // $authorizationHeader should be in that form: "Bearer {THE_TOKEN}"
        $token = explode(' ', $authorizationHeader)[1];
        try {
            $this->decodedToken = JWT::decode($token, $this->client->secretKey, array('HS256'));
        }
        catch (UnexpectedValueException $ex) {
            throw new AppException(Response::HTTP_UNAUTHORIZED, 'Invalid token', 5011);
        }
    }

    public function getDecodedToken()
    {
        return $this->decodedToken;
    }
}
