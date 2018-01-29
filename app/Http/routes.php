<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


/**
 * index
 */

Route::group(
    [
        'middleware' => config('cas.middleware.auth'),
    ],
    function () {
        Route::get('/', ['as' => 'home', 'uses' => 'HomeController@indexAction']);
        Route::post('changePwd', ['as' => 'password.change.post', 'uses' => 'HomeController@changePwdAction']);
    }
);

/**
 * oauth
 */
Route::get('oauth/{name}', ['as' => 'oauth.login', 'uses' => 'Auth\OAuthController@login']);
Route::get('oauth/{name}/callback', ['as' => 'oauth.callback', 'uses' => 'Auth\OAuthController@callback']);

/**
 * forget password
 */    
if (config('cas_server.allow_reset_pwd')) {
    
    Route::group(
        [
            'middleware' => 'guest',
        ],
        function () {
            Route::get(
                'password/email',
                ['as' => 'password.reset.request.get', 'uses' => 'PasswordController@getEmail']
            );
            Route::post(
                'password/email',
                ['as' => 'password.reset.request.post', 'uses' => 'PasswordController@sendResetLinkEmail']
            );
            Route::get(
                'password/reset/{token?}',
                ['as' => 'password.reset.get', 'uses' => 'PasswordController@showResetForm']
            );
            Route::post('password/reset', ['as' => 'password.reset.post', 'uses' => 'PasswordController@reset']);
        }
    );
}

/**
 * register
 */
if (config('cas_server.allow_register')) {

    Route::group(
        [
            'middleware' => 'guest',
            'namespace'  => 'Auth',
        ],
        function () {
            Route::get('register', ['as' => 'register.get', 'uses' => 'RegisterController@show']);
            Route::post('register', ['as' => 'register.post', 'uses' => 'RegisterController@postRegister']);
        }
    );
}

/**
 * admin
 */
Route::group(
    [
        'namespace'  => 'Admin',
        'middleware' => 'admin',
        'prefix'     => 'admin',
    ],
    function () {
        Route::get('home', ['as' => 'admin_home', 'uses' => 'HomeController@indexAction']);

        Route::resource(
            'user',
            'UserController',
            [
                'only'  => ['index', 'store', 'update'],
                'names' => [
                    'index'  => 'admin.user.index',
                    'store'  => 'admin.user.store',
                    'update' => 'admin.user.update',
                ],
            ]
        );

        Route::resource(
            'service',
            'ServiceController',
            [
                'only'  => ['index', 'store', 'update'],
                'names' => [
                    'index'  => 'admin.service.index',
                    'store'  => 'admin.service.store',
                    'update' => 'admin.service.update',
                ],
            ]
        );
    }
);


/**
 * cas
 */

$casOptions = [
    'prefix'    => config('cas.router.prefix'),
];

Route::group(
    $casOptions,
    function () {
        $auth = config('cas.middleware.auth');
        $p    = config('cas.router.name_prefix');
        Route::get('login', 'SecurityController@showLogin')->name($p.'login.get');
        Route::post('login', 'SecurityController@login')->name($p.'login.post');
        Route::get('logout', 'SecurityController@logout')->name($p.'logout')->middleware($auth);
        Route::any('validate', 'ValidateController@v1ValidateAction')->name($p.'v1.validate');
        Route::any('serviceValidate', 'ValidateController@v2ServiceValidateAction')->name($p.'v2.validate.service');
        Route::any('proxyValidate', 'ValidateController@v2ProxyValidateAction')->name($p.'v2.validate.proxy');
        Route::any('proxy', 'ValidateController@proxyAction')->name($p.'proxy');
        Route::any('p3/serviceValidate', 'ValidateController@v3ServiceValidateAction')->name($p.'v3.validate.service');
        Route::any('p3/proxyValidate', 'ValidateController@v3ProxyValidateAction')->name($p.'v3.validate.proxy');
    }
);