<?php
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Created by PhpStorm.
 * User: libin
 * Date: 16/9/20
 * Time: 17:30
 */

if (!function_exists('cas_route')) {
    /**
     * @param string $name
     * @param array  $parameters
     * @param bool   $absolute
     * @return string
     */
    function cas_route($name, $parameters = [], $absolute = true)
    {
        $name = config('cas.router.name_prefix').$name;

        return route($name, $parameters, $absolute);
    }
}

/**
 * @param string $name
 * @return string
 */
function route_uri($name)
{
    return app('router')->getRoutes()->getByName($name)->getUri();
}

/**
 * @param $name
 * @return string
 */
function cas_route_uri($name)
{
    $name = config('cas.router.name_prefix').$name;

    return route_uri($name);
}

/**
 * Send a request to remote cas client
 * @author stephen 2018/03/06
 *
 * @param string $method
 * @param string $host
 * @param string $uri
 * @param string $body
 *
 * @return string $response
 */
function request_cas_client($method, $host, $uri, $body)
{
    $method = strtoupper($method);

    $client = new Client([
        'base_uri' => config('cas.client_protocal').'://'. trim($host,'/').'/'. trim($uri,'/'),
        'timeout' => 4.0,
    ]);
    
    try{
        switch ($method) {
            case 'GET':
                $response = $client->request(
                    'GET',
                    '',
                    ['query' => $body]
                );
                break;
            case 'POST':
                $response = $client->request(
                    'POST',
                    '',
                    ['form_params' => $body]
                );
                break;
            default:
                return '';
        }

        return $response->getBody();
    } catch (RequestException $e) {
        return '';
    }
}

function libin_debug( $msg )
{
	file_put_contents(storage_path().'/logs/libin_debug.log', var_export($msg, true));
}