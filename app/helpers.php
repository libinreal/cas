<?php
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

function libin_debug( $msg )
{
	\Illuminate\Support\Facades\Log::debug(var_export($msg, true));
}