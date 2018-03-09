<?php

namespace App\Facades;
use Illuminate\Support\Facades\Facade;

/**
 * @see App\Services\Utils\Services_JSON
 */
class Json extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'json';
    }
}
