<?php

namespace App\Providers;

use App\Interactions\UserLogin;
use App\Repositories\ServiceRepository;
use App\Services\TickerLocker;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Support\ServiceProvider;
use App\Contracts\Interactions\UserLogin as UserLoginInterface;
use App\Contracts\TicketLocker as TicketLockerInterface;
use App\Repositories\ServiceRepository as ServiceRepositoryBase;
use App\Plugin\OAuth\PluginCenter;
use NinjaMutex\Lock\LockAbstract;
use NinjaMutex\Lock\MySqlLock;
use App\Services\Utils\Services_JSON;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->_registerMyValidation();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() == 'local') {
            $this->app->register(IdeHelperServiceProvider::class);
        }

        $this->app->bind(UserLoginInterface::class, UserLogin::class);
        $this->app->bind(TicketLockerInterface::class, TickerLocker::class);
        $this->app->bind(ServiceRepositoryBase::class, ServiceRepository::class);
        $this->app->bind(
            LockAbstract::class,
            function () {
                $conf = config('database.connections.mysql');

                return new MySqlLock($conf['username'], $conf['password'], $conf['host']);
            }
        );
        $this->app->singleton(
            PluginCenter::class,
            function () {
                return new PluginCenter(app()->getLocale(), config('app.fallback_locale'));
            }
        );
        //Register Json_Service
        $this->app->singleton(
            'json',
            function () {
                return new Services_JSON();
            }
        );
    }

    /**
     * 注册自定义数据验证规则
     *
     * 
     */
    private function _registerMyValidation()
    {
        /**
         * 二维数组，e.g. array(array('key'=>'val'),array('key'=>'another_val')) 二级数组的相同的键对应的值必须是唯一的
         */
        \Validator::extend('different_in_array', function($attribute, $value, $parameters){

            if (is_array($value) and isset($parameters[0]) and is_string($parameters[0]) and trim($parameters[0]))
            {
                $_values = array();

                foreach($value as $key => $val)
                {
                    if (!is_array($val) or !isset($val[$parameters[0]]) or !$val[$parameters[0]] or in_array($val[$parameters[0]], $_values))
                    {
                        /*$debug['is_array'] = !is_array($val);
                        $debug['isset'] = !isset($val[$parameters[0]]);
                        $debug['i'] = !$val[$parameters[0]];
                        $debug['val'] = $val;
                        $debug['parameters0'] = $parameters[0];
                        $debug['in_array'] = in_array($val[$parameters[0]], $_values);

                        libin_debug($debug);*/
                        return false;
                    }

                    $_values[] = $val[$parameters[0]];
                }

                return true;
            }

            /*$debug['end'] = 1;
            libin_debug($debug);*/
            return false;
        });
    }
}
