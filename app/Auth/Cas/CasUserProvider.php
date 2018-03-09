<?php

namespace App\Auth\Cas;

use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use App\Events\Model\CasUserTokenChangeEvent;
use Illuminate\Auth\EloquentUserProvider;
use App\Repositories\ServiceRepository;
use App\Models\Service;
use App\Models\CasUser;
use App\Models\CasServiceUser;
use App\Models\ServiceApi;
use App\Models\ServiceHost;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CasUserProvider extends EloquentUserProvider
{
    /**
     * @var App\Models\Service
     */
    protected $service;

    /**
     * @var App\Models\CasUser
     */
    protected $casUser;

    /**
     * @var array e.g.['name'=>'login', 'url'=>'admin/login', 'method'=>'POST', 'fields'=>'u,p', 'response_fields'=>'data.uid']
     */
    protected $loginApi;

    /**
     * @var array|number e.g.['data'=>['uid'=>1]] | 1 | false
     */
    protected $response;

    /**
     * Create a new database user provider.
     *
     * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
     * @param  string  $model
     * @param  ServiceRepository $serviceRepository
     * @return void
     */
    public function __construct(
        HasherContract $hasher,
        $model
    ) {
        $this->model = $model;
        $this->hasher = $hasher;
    }

    /**
     * Override parent method:

     * Retrieve a user from remote client by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials)) {
            return;
        }

        $this->initProperties();
        // First we will send username and password from credential to remote client.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.

        return $this->_loginWithServiceApi($credentials);
    }

    /**
     * Initialize the properties
     * @author stephen 2018/03/09
     */
    protected function initProperties()
    {
        $this->service = null;
        $this->casUser = null;
        $this->loginApi = null;
        $this->response = null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(UserContract $user, $token)
    {
        //get original token 
        // $beforeChangeToken = $user->getRememberToken();

        $user->setRememberToken($token);
        $change = $user->save();

     
        // if($change)
        // {
            //fire CasUserTokenChangeEvent
            // event(new CasUserTokenChangeEvent($beforeChangeToken, $token));
        // }
     
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        //Skip this step, App\Models\CasUser doesn't save password
        return true;
        $plain = $credentials['password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }

    /**
     * Verify a user by the given credentials with service apis.
     * @author stephen 2018/03/06
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private function _loginWithServiceApi(array $credentials)
    {
        $serviceUrl = isset($credentials['service']) ? $credentials['service'] : '';
        $host = parse_url($serviceUrl, PHP_URL_HOST);

        //Get service from the host
        if (!empty($serviceUrl) && !empty($host)) {
            $hostRecord = ServiceHost::where('host', $host)->first();
            if (!$hostRecord) {
                return null;
            }
            $this->service = $hostRecord->service;
        }
        else
        {
            return null;
        }

        //Get api by ServiceApi
        $loginApi = ServiceApi::where('name', 'login')
                    ->where('service_id', $this->service->id)->first();

        if($loginApi){
            //Set array to loginApi
            $this->loginApi = $loginApi->toArray();

            //Send login request to remote subsystem and get response
            $method = $this->loginApi['method'];

            $uri = $this->loginApi['url'];
            $fields = explode(',', $this->loginApi['fields']);
            $responseFields = explode(',', $this->loginApi['response_fields']);

            $body = [];
            $body[$fields[0]] = $userName = $credentials['email'];
            $body[$fields[1]] = $password = $credentials['password'];

            $serviceId = $this->service->id;
            //Get response through login API    
            $this->response = request_cas_client($method, $host, $uri, $body);

            if($this->response){
                //Decode response
                $userProfile = json_decode($this->response, true);
                // file_put_contents(storage_path().'/logs/cms1.login.20180307.log', __LINE__."\r\n", FILE_APPEND);
                //Get user_id from response and save to App\Models\CasServiceUser
                $userId = 0;
                if(!empty($userProfile) && !empty($responseFields) && (is_object($userProfile) || is_array($userProfile))  ){
                    
                    $userId = data_get($userProfile, $responseFields[0], '');
                    // file_put_contents(storage_path().'/logs/cms1.login.20180307.log', var_export($userId, true), FILE_APPEND);
                } else if(!$userProfile && is_numeric($this->response)){
                    $userId = $this->response;
                } else {//Unrecongnizable response
                    return $this->response = null;
                }

                //Declare the fields in App\Models\CasUser
                $email = (filter_var($userName, FILTER_VALIDATE_EMAIL) !== false) ? $userName : '';
                $realName = '';

                //Make and save a random string into App\Models\CasServiceUser
                $randomStr = Str::random(60);

                //Get the App\Models\CasUser
                $this->casUser = Auth::guard(config('auth.cas.guard'))->user();
                //Get the App\Models\CasServiceUser
                $serviceUser = CasServiceUser::where('service_id', $serviceId)
                            ->where('user_name', $userName)->first();
                
                if($this->casUser){//Old user
                    //Create an App\Models\CasServiceUser if not exists
                    if(!$serviceUser){
                        $serviceUser = new CasServiceUser();
                    }
                
                }else{//New user
                    //Create an new App\Models\CasUser
                    $this->casUser = new CasUser();

                    //Create a new App\Models\CasServiceUser
                    $serviceUser = new CasServiceUser();
                }

                //Set App\Models\CasUser attributes and save data
                $this->casUser->setAttribute('email', $email);
                $this->casUser->setAttribute('real_name', $realName);
                $this->casUser->setAttribute('name', $userName);
                $this->casUser->setAttribute('service_id', $this->service->id);
                $this->casUser->save();

                //Set App\Models\CasServiceUser attributes and save data
                $serviceUser->service()->associate($this->service);
                $serviceUser->casUser()->associate($this->casUser);

                $serviceUser->setAttribute('user_name', $userName);
                $serviceUser->setAttribute('user_id', $userId);
                $serviceUser->setAttribute('random_str', $randomStr);
                $serviceUser->save();

                // file_put_contents(storage_path().'/logs/cms1.login.20180307.log', var_export($userProfile, true), FILE_APPEND);
                return $this->casUser;
            }else{
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Get Service
     * @author stephen 2018/03/09
     * @return App\Models\Service|null service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Get CasUser
     * @author stephen 2018/03/09
     * @return App\Models\CasUser|null casUser
     */
    public function getCasUser()
    {
        return $this->casUser;
    }

    /**
     * Get loginApi
     * @author stephen 2018/03/09
     * @return array|null loginApi
     */
    public function getLoginApi()
    {
        return $this->loginApi;
    }

    /**
     * Get response
     * @author stephen 2018/03/09
     * @return array|number|null response
     */
    public function getResponse()
    {
        return $this->response;
    }
}