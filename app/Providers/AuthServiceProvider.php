<?php

namespace App\Providers;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Auth\Cas\CasSessionGuard;
use App\Auth\Cas\CasUserProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function boot(GateContract $gate)
    {
        $this->registerPolicies($gate);

        $guard = config('auth.cas.guard');
        $guardConfig = config("auth.guards.{$guard}");

        libin_debug(CasUserProvider::class);

        $this->app
            ->make('auth')
            ->extend('CasCasSessionGuard', function($app, $name, $config){
                
                $provider = $app['auth']->createUserProvider($config['provider']);

                $guard = new CasSessionGuard($name, $provider, $app['session.store']);

                // When using the remember me functionality of the authentication services we
                // will need to be set the encryption instance of the guard, which allows
                // secure, encrypted cookie values to get generated for those cookies.
                if (method_exists($guard, 'setCookieJar')) {
                    $guard->setCookieJar($app['cookie']);
                }

                if (method_exists($guard, 'setDispatcher')) {
                    $guard->setDispatcher($app['events']);
                }

                if (method_exists($guard, 'setRequest')) {
                    $guard->setRequest($app->refresh('request', $guard, 'setRequest'));
                }

                return $guard;
            })
            ->provider('CasCasUserProvider', function($app, $config){
                
                return new CasUserProvider($app['hash'], $config['model']);
            });

        //
    }
}
