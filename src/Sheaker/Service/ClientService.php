<?php

namespace Sheaker\Service;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a way to handle Sheaker Client
 */
class ClientService
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var \Sheaker\Entity\Client $client
     */
    protected $client;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->client = null;
    }

    public function fetchClient(Request $request)
    {
        $getParams = [];
        if ($request->get('subdomain'))
            $getParams['subdomain'] = $this->app->escape($request->get('subdomain'));
        else if ($request->get('id_client'))
            $getParams['id_client'] = $this->app->escape($request->get('id_client'));
        else
            throw new AppException(Response::HTTP_UNAUTHORIZED, 'No client specified', 5007);

        if (!empty($getParams['id_client'])) {
            $client = $this->app['repository.client']->find($getParams['id_client']);
            if (!$client) {
                throw new AppException(Response::HTTP_NOT_FOUND, 'Client not found', 5008);
            }
        }
        else if (!empty($getParams['subdomain'])) {
            $client = $this->app['repository.client']->findBySubdomain($getParams['subdomain']);
            if (!$client) {
                throw new AppException(Response::HTTP_NOT_FOUND, 'Client not found', 5009);
            }

            unset($client->secretKey);
        }
        else {
            throw new AppException(Response::HTTP_BAD_REQUEST, 'Missing parameters', 5010);
        }

        $this->client = $client;
    }

    public function getClient()
    {
        return $this->client;
    }
}
