<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/13
 * Time: 17:49
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class CASServerServiceProvider
 * @package app
 */
class CASServerServiceProvider extends ServiceProvider
{
    /**
     * @inheritdoc
     */
    public function register()
    {
        // TODO: Implement register() method.
    }

    /**
     * @inheritdoc
     */
    public function boot()
    {
        if (!$this->app->routesAreCached()) {
            
            require $this->app->path().'/Http/routes.php';
        }

        /*$this->publishes(
            [
                __DIR__.'/../config/cas.php' => config_path('cas.php'),
            ],
            'config'
        );

        $this->publishes(
            [
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ],
            'migrations'
        );*/
    }
}
