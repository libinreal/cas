<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 14:50
 */

namespace App\Http\Controllers;

use App\Contracts\Interactions\UserLogin;
use App\Contracts\Models\UserModel;
use App\Events\CasUserLoginEvent;
use App\Events\CasUserLogoutEvent;
use App\Exceptions\CAS\CasException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Repositories\PGTicketRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\TicketRepository;

class SecurityController extends Controller
{
    /**
     * @var ServiceRepository
     */
    protected $serviceRepository;

    /**
     * @var TicketRepository
     */
    protected $ticketRepository;

    /**
     * @var PGTicketRepository
     */
    protected $pgTicketRepository;
    /**
     * @var UserLogin
     */
    protected $loginInteraction;

    /**
     * SecurityController constructor.
     * @param ServiceRepository  $serviceRepository
     * @param TicketRepository   $ticketRepository
     * @param PGTicketRepository $pgTicketRepository
     * @param UserLogin          $loginInteraction
     */
    public function __construct(
        ServiceRepository $serviceRepository,
        TicketRepository $ticketRepository,
        PGTicketRepository $pgTicketRepository,
        UserLogin $loginInteraction
    ) {
        $this->serviceRepository  = $serviceRepository;
        $this->ticketRepository   = $ticketRepository;
        $this->loginInteraction   = $loginInteraction;
        $this->pgTicketRepository = $pgTicketRepository;
    }

    public function showLogin(Request $request)
    {
        $service = $request->get('service', '');
        $errors  = [];
        if (!empty($service)) {
            //service not found in white list
            if (!$this->serviceRepository->isUrlValid($service)) {
                $errors[] = (new CasException(CasException::INVALID_SERVICE))->getCasMsg();
            }
        }
        $user = $this->loginInteraction->getCurrentUser($request);
        
        //user already has sso session
        if ($user) {
            //has errors, should not be redirected to target url
            if (!empty($errors)) {
                return $this->loginInteraction->redirectToHome($errors);
            }
            
            //must not be transparent
            if ($request->get('warn') === 'true' && !empty($service)) {
                $query = $request->query->all();
                unset($query['warn']);
                $url = cas_route('login_page', $query);
                
                return $this->loginInteraction->showLoginWarnPage($request, $url, $service);
            }
            
            return $this->authenticated($request, $user);

        }
        
        return $this->loginInteraction->showLoginPage($request, $errors);
    }

    public function login(Request $request)
    {
        $user = $this->loginInteraction->login($request);
        if (is_null($user)) {
            return $this->loginInteraction->showAuthenticateFailed($request);
        }

        return $this->authenticated($request, $user);
    }

    public function authenticated(Request $request, UserModel $user)
    {
        event(new CasUserLoginEvent($request, $user));
        $serviceUrl = $request->get('service', '');
        if (!empty($serviceUrl)) {
            $query = parse_url($serviceUrl, PHP_URL_QUERY);
            try {
                $ticket = $this->ticketRepository->applyTicket($user, $serviceUrl);
            } catch (CasException $e) {
                
                return $this->loginInteraction->redirectToHome([$e->getCasMsg()]);
            }
            $finalUrl = $serviceUrl.($query ? '&' : '?').'ticket='.$ticket->ticket;
           // libin_debug($finalUrl);
           // exit;
            return redirect($finalUrl);
        }
        
        return $this->loginInteraction->redirectToHome();
    }

    public function logout(Request $request)
    {
        //Added by libin 2018/03/12
        $serviceLogout = false;
        $user = $this->loginInteraction->getCurrentUser($request);
        if ($user) {
            $serviceLogout = $this->loginInteraction->logout($request);//Added by libin 2018/03/12
            $this->pgTicketRepository->invalidTicketByUser($user);
            event(new CasUserLogoutEvent($request, $user));
        }
        $service = $request->get('service');
        if ($service && $this->serviceRepository->isUrlValid($service)) {
            //If log out successfully then return 'ok',otherwise return '',by libin 2018/03/12 
            // return redirect($service);
            if($serviceLogout){//Successfully log out
                return new Response('ok');
            }else{
                return new Response('');
            }
        }

        return $this->loginInteraction->showLoggedOut($request);
    }
}
