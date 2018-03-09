<?php
namespace App\Auth\Cas;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use App\Models\CasUser;
use App\Models\CasServiceUser;
use App\Models\ServiceHost;

class CasSessionGuard extends SessionGuard
{
    protected $serviceId; 

    /**
     * Override
     * Get the currently authenticated user relation, that is to say get both App\Models\CasUser and App\Models\CasServiceUser.
     *
     * @author libin 2018/03/09
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {

        if ($this->loggedOut) {
            return;
        }
        
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        /*        
        if (! is_null($this->user)) {
            return $this->user;
        }
        */
        /***** If you want to get a CasUser instance,
                then use below instead, 
                    by libin 2018/03/09 
        *****/
        /*
        $this->user();
        $this->user;
        */

        $id = $this->session->get($this->getName());//Get CasUser id
        $serviceId = $this->getServiceId();//Get Service id
        
        // First we will try to load the user using the identifier in the session if
        // one exists. Otherwise we will check for a "remember me" cookie in this
        // request, and if one exists, attempt to retrieve the user using that.
        $this->user = null;
        //Not only get the App\Models\CasUser, but also get the related App\Models\CasServiceUser, by stephen 2018/03/09
        $serviceUser = null;
        if (! is_null($id)) {
            $this->user = $this->provider->retrieveById($id);
            //Get App\Models\CasServiceUser, by stephen 2018/03/09
            $serviceUser = CasServiceUser::where([
                'service_id'=> $serviceId,
                'cas_user_id'=> $id,
                ])->first();
        }
        
        // If the user is null, but we decrypt a "recaller" cookie we can attempt to
        // pull the user data on that cookie which serves as a remember cookie on
        // the application. Once we have a user we can return it to the caller.
        $recaller = $this->getRecaller();

        if (is_null($this->user) && ! is_null($recaller)) {
            $this->user = $this->getUserByRecaller($recaller);
            
            if ($this->user) {
                $this->updateSession($this->user->getAuthIdentifier());

                $this->fireLoginEvent($this->user, true);
                //Get App\Models\CasServiceUser, by stephen 2018/03/09
                $serviceUser = CasServiceUser::where([
                    'service_id'=> $serviceId,
                    'cas_user_id'=> $this->user->id,
                    ])->first();
            }
        }

       
        // return $this->user = $user;
        //Only when both App\Models\CasUser and related App\Models\CasServiceUser is found, return the App\Models\CasUser
        return ($this->user && $serviceUser) ? $this->user : null;
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     * Ovreride intending, Other than the cas own database, This authentication use the subsystem database
     * @param  array  $credentials
     * @param  bool   $remember
     * @param  bool   $login
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = true, $login = true)
    {
        $credentials = $this->_formatCredentials($credentials);

        $this->fireAttemptEvent($credentials, $remember, $login);

        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        // If an implementation of UserInterface was returned, we'll ask the provider
        // to validate the user against the given credentials, and if they are in
        // fact valid we'll log the users into the application and return true.
        if ($this->hasValidCredentials($user, $credentials)) {
            if ($login) {
                $this->login($user, $remember);
            }

            return true;
        }

        // If the authentication attempt fails we will fire an event so that the user
        // may be notified of any suspicious attempts to access their account from
        // an unrecognized user. A developer may listen to this event as needed.
        if ($login) {
            $this->fireFailedEvent($user, $credentials);
        }

        return false;
    }

    private function _formatCredentials(array $credentials)
    {
        foreach ($credentials as $k => &$v) {
            $v = trim($v);
        }

        unset($v);
        return $credentials;
    }

    /**
     * Override
     * Log the user out of the service.
     *
     * Delete an App\Models\CasServiceUser from the App\Models\CasUser,when all CasServiceUser is removed,delete the related App\Models\CasUser
     * @author libin 2018/03/09
     * @return void
     */
    public function logout()
    {
        $isLogin = $this->user();

        //only can logout when the service parameter is supplied from request and the logging user has been got.
        if($this->user){

            $this->getServiceId();

            if(!$this->user->service_id && !$this->serviceId)
                return ;
            
            //Check the username and random_str is matched the current user


            $serviceId = $this->serviceId ? $this->serviceId : $this->user->service_id;
            
            //delete CasServiceUser related to CasUser
            if($serviceId){
                $casServiceUser = CasServiceUser::where([
                        'service_id' => $serviceId,
                        'cas_user_id' => $this->user->id
                    ])->first();
                //Validate user_name and random_str in the request if an App\Models\CasServiceUser is found.
                if($casServiceUser){

                    if( !$this->validateRandomStr($casServiceUser, $this->request->input('random_str', '')) 
                        || !$this->validateUserName($casServiceUser, $this->request->input('user_name', ''))
                    ){
                        return ;
                    }
                    $casServiceUser->delete();
                }
            }else{
                return ;
            }
        }else{//Can not log out without sevice or user.
            return ;
        }

        //Delete the relation, that is to say deleting the App\Models\CasUser, by libin 2018/03/09
        if(!$this->user || count($this->user->serviceUsers) == 0){
            // If we have an event dispatcher instance, we can fire off the logout event
            // so any further processing can be done. This allows the developer to be
            // listening for anytime a user signs out of this application manually.
            $this->clearUserDataFromStorage();
            //Delete CasUser if relating serviceUsers is empty, by libin 2018/03/09
            if (!is_null($this->user)) {
                $this->user->delete();
            }

            /*
            if (!is_null($this->user)) {
                $this->refreshRememberToken($user);
            }
            */
        }

        if (isset($this->events)) {
            $this->events->fire(new Events\Logout($this->user));
        }

        // Once we have fired the logout event we will clear the users out of memory
        // so they are no longer available as the user is no longer considered as
        // being signed into this application and should not be available here.

        //Added by libin 2018/03/09
        $tihs->serviceId = null;

        $this->user = null;
        
        $this->loggedOut = true;
        

    }

    /**
     * check random_str in CasServiceUser wether is matched with the one in request parameters or not
     * @param App\Models\CasServiceUser $casServiceUser
     * @param string $randomStr. the random_str to be validated
     * @author libin 2018/03/09
     * @return bool
     */
    public function validateRandomStr($casServiceUser, $randomStr){
        return $casServiceUser->random_str == $randomStr;
    }

    /**
     * check user_name in CasServiceUser wether is matched with the one in request parameters or not
     * @param App\Models\CasServiceUser $casServiceUser
     * @param string $userName. the user_name to be validated
     * @author libin 2018/03/09
     * @return bool
     */
    public function validateUserName($casServiceUser, $userName){
        return $casServiceUser->user_name == $userName;
    }

    /**
     * Get the id of service by Request.
     *
     * @author libin 2018/03/09
     * @return int|null $serviceId
     */
    public function getServiceId()
    {
        if(!$this->serviceId){
            $url = $this->request->input('service','');

            if($url){
                $host = parse_url($url, PHP_URL_HOST);
                $serviceHost = ServiceHost::where('host', $host)->first();

                if (!$serviceHost) {
                    return ;
                }else if($serviceHost && $serviceHost->service){
                    $this->serviceId = $serviceHost->service->id;
                }else{//Bad relation
                    return ;
                }

            }else{//Can not log out without sevice or user.
                return ;
            }
        }

        return $this->serviceId;
    }

}