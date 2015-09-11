<?php

namespace Sheaker\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MainController
{
    public function getSheakerClient(Request $request, Application $app)
    {
        $getParams = [];
        $getParams['subdomain'] = $app->escape($request->get('subdomain'));

        foreach ($getParams as $value) {
            if (!isset($value)) {
                $app->abort(Response::HTTP_BAD_REQUEST, 'Missing parameters');
            }
        }

        $client = clone $app['client']->getClient();

        /// SECURITY: Don't send the secret key, it's only needed here
        unset($client->secretKey);

        return json_encode($client, JSON_NUMERIC_CHECK);
    }

    public function getSheakerInfos(Request $request, Application $app)
    {
        $reserved_subdomains = [];
        foreach ($app['dbs']['sheaker']->fetchAll('SELECT * FROM reserved_subdomains rs') as $sub) {
            array_push($reserved_subdomains, $sub['subdomain']);
        }

        $infos = [];
        $infos['reservedSubdomains'] = $reserved_subdomains;

        return json_encode($infos, JSON_NUMERIC_CHECK);
    }
}
