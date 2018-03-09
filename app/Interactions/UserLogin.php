<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 16/9/17
 * Time: 21:35
 */

namespace App\Interactions;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Contracts\Models\UserModel;
use App\Plugin\OAuth\PluginCenter;
use Symfony\Component\HttpFoundation\Response;
use App\Contracts\Interactions\UserLogin as Contract;

class UserLogin implements Contract
{
    use AuthenticatesUsers, ValidatesRequests;

    protected $guard;//current guard's name

    /**
     * @param Request $request
     * @return UserModel|null
     */
    public function login(Request $request)
    {
        if (config('cas_server.disable_pwd_login')) {
            return null;
        }

        $credentials            = $this->getCredentials($request);
        $credentials['enabled'] = true;
        $credentials['service'] = $request->input('service');//added by stephen 2018/01/31

        $this->guard = null;
        if($credentials['service'])//Client system user using CasSessionGuard
        {
            $this->guard = config('auth.cas.guard');
            //Force to remember
            $request->merge(['remember' => 'true']);
        }
        else//Backgroud user/administrator using SessionGuard
        {
            unset($credentials['service']);
        }

        if (Auth::guard($this->getGuard())->attempt($credentials, $request->has('remember'))) {
            return Auth::guard($this->getGuard())->user();
        }

        return null;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function showAuthenticateFailed(Request $request)
    {
        return $this->sendFailedLoginResponse($request);
    }

    /**
     * @param Request $request
     * @return UserModel|null
     */
    public function getCurrentUser(Request $request)
    {
        //Recongnize wether the request has a parameter of service or not,by stephen 2018/03/09
        if($request->input('service', '')){//Use CasSessionGuard to authorize if has parameter of service
            $this->guard = config('auth.cas.guard');
            return $request->user($this->guard);
        }

        //Use original SessionGuard to authorize if request has no parameter of service
        return $request->user();
    }

    /**
     * @param Request $request
     * @param string  $jumpUrl
     * @param string  $service
     * @return Response
     */
    public function showLoginWarnPage(Request $request, $jumpUrl, $service)
    {
        return view('auth.login_warn', ['url' => $jumpUrl, 'service' => $service]);
    }

    /**
     * @param Request $request
     * @param array   $errors
     * @return Response
     */
    public function showLoginPage(Request $request, array $errors = [])
    {
        return view(
            'auth.login',
            [
                'errorMsgs' => $errors,
                'plugins'   => app(PluginCenter::class)->getAll(),
                'service'   => $request->get('service', null),
            ]
        );
    }

    /**
     * @param array $errors
     * @return Response
     */
    public function redirectToHome(array $errors = [])
    {
        return redirect()->route('home')->withErrors(['global' => $errors]);
    }

    /**
     * @param Request $request
     * @return void
     */
    public function logout(Request $request)
    {
        //If request has a parameter of service use CasSessionGuard, by stephen 2018/01/31
        $this->guard = null;
        if($request->input('service', ''))//Client system user using CasSessionGuard
        {
            $this->guard = config('auth.cas.guard');
        }
        
        Auth::guard($this->getGuard())->logout();
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function showLoggedOut(Request $request)
    {
        return view('auth.logged_out');
    }


    /**
     * Ovrerride Trait method with the same name
     * @author stephen 2018/03/09
     * Get the failed login message.
     *
     * @return string
     */
    protected function getFailedLoginMessage()
    {
        if (!Auth::guard($this->getGuard())->getProvider()->getService()) {
            return Lang::has('auth.unknown_service')
                ? Lang::get('auth.unknown_service')
                : 'Unknown service.';
        }else if(!Auth::guard($this->getGuard())->getProvider()->getLoginApi()){
            return Lang::has('auth.api_error')
                ? Lang::get('auth.api_error')
                : 'Remote url error.Please contact the administrator.';
        }else if(!Auth::guard($this->getGuard())->getProvider()->getResponse()){
            return Lang::has('auth.response_error')
                ? Lang::get('auth.response_error')
                : 'Remote resopnse error.';
        }

        return Lang::has('auth.failed')
                ? Lang::get('auth.failed')
                : 'These credentials do not match our records.';
    }
}
