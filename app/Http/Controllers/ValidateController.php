<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 14:52
 */

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Contracts\TicketLocker;
use App\Repositories\PGTicketRepository;
use App\Repositories\TicketRepository;
use App\Exceptions\CAS\CasException;
use App\Models\Ticket;
use App\User;
use App\Models\CasUser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Responses\JsonAuthenticationFailureResponse;
use App\Responses\JsonAuthenticationSuccessResponse;
use App\Responses\JsonProxyFailureResponse;
use App\Responses\JsonProxySuccessResponse;
use App\Responses\XmlAuthenticationFailureResponse;
use App\Responses\XmlAuthenticationSuccessResponse;
use App\Responses\XmlProxyFailureResponse;
use App\Responses\XmlProxySuccessResponse;
use App\Services\PGTCaller;
use App\Services\TicketGenerator;
use SimpleXMLElement;

class ValidateController extends Controller
{
    /**
     * @var TicketLocker
     */
    protected $ticketLocker;
    /**
     * @var TicketRepository
     */
    protected $ticketRepository;

    /**
     * @var PGTicketRepository
     */
    protected $pgTicketRepository;

    /**
     * @var TicketGenerator
     */
    protected $ticketGenerator;

    /**
     * @var PGTCaller
     */
    protected $pgtCaller;

    /**
     * ValidateController constructor.
     * @param TicketLocker       $ticketLocker
     * @param TicketRepository   $ticketRepository
     * @param PGTicketRepository $pgTicketRepository
     * @param TicketGenerator    $ticketGenerator
     * @param PGTCaller          $pgtCaller
     */
    public function __construct(
        TicketLocker $ticketLocker,
        TicketRepository $ticketRepository,
        PGTicketRepository $pgTicketRepository,
        TicketGenerator $ticketGenerator,
        PGTCaller $pgtCaller
    ) {
        $this->ticketLocker       = $ticketLocker;
        $this->ticketRepository   = $ticketRepository;
        $this->pgTicketRepository = $pgTicketRepository;
        $this->ticketGenerator    = $ticketGenerator;
        $this->pgtCaller          = $pgtCaller;
    }

    public function v1ValidateAction(Request $request)
    {
        
        $service = $request->get('service', '');
        $ticket  = $request->get('ticket', '');
        if (empty($service) || empty($ticket)) {
            
            return new Response("no\n");
        }

        if (!$this->lockTicket($ticket)) {
            
            return new Response("no\n");
        }
        $record = $this->ticketRepository->getByTicket($ticket); 

        if (!$record || $record->service_url != $service) {
            $this->unlockTicket($ticket);
            
            return new Response("no\n");
        }
        // \DB::enableQueryLog();
        $this->ticketRepository->invalidTicket($record);
        
        // $userName = User::find($record->user_id)->getName();
        //Use Model Class in config/cas.php file , by stephen 2018/03/08
        $userModelClass = '\\'.ltrim(config('cas.user_table.model'), '\\');
        $userModel = new $userModelClass;
        $user = $userModel->find($record->user_id);

        if(!$user || !$user->getName()){
            return new Response("no\n");
        }
        
        $this->unlockTicket($ticket);

        $randomFunc = [$user, 'getRandomStr'];

        $randomStr = '';
        if(is_callable($randomFunc))
            $randomStr = call_user_func($randomFunc, $record->service_id);

        // file_put_contents(storage_path().'/logs/cms1.login.20180308.log', "yes\n". $user->getName()."\n".$randomStr."\r\n", FILE_APPEND);
        // return new Response("yes\n". $user->getName()."\n".$randomStr);
        return new Response("yes\n". $user->getName()."\n".$randomStr);
    }

    public function v2ServiceValidateAction(Request $request)
    {
        return $this->casValidate($request, false, false);
    }

    public function v3ServiceValidateAction(Request $request)
    {
        return $this->casValidate($request, true, false);
    }

    public function v2ProxyValidateAction(Request $request)
    {
        return $this->casValidate($request, false, true);
    }

    public function v3ProxyValidateAction(Request $request)
    {
        return $this->casValidate($request, true, true);
    }

    public function proxyAction(Request $request)
    {
        $pgt    = $request->get('pgt', '');
        $target = $request->get('targetService', '');
        $format = strtoupper($request->get('format', 'XML'));

        if (empty($pgt) || empty($target)) {
            return $this->proxyFailureResponse(
                CasException::INVALID_REQUEST,
                'param pgt and targetService can not be empty',
                $format
            );
        }

        $record = $this->pgTicketRepository->getByTicket($pgt);
        try {
            if (!$record) {
                throw new CasException(CasException::INVALID_TICKET, 'ticket is not valid');
            }
            $proxies = $record->proxies;
            array_unshift($proxies, $record->pgt_url);
            $ticket = $this->ticketRepository->applyTicket($record->user, $target, $proxies);
        } catch (CasException $e) {
            return $this->proxyFailureResponse($e->getCasErrorCode(), $e->getMessage(), $format);
        }

        return $this->proxySuccessResponse($ticket->ticket, $format);
    }

    /**
     * @param Request $request
     * @param bool    $returnAttr
     * @param bool    $allowProxy
     * @return Response
     */
    protected function casValidate(Request $request, $returnAttr, $allowProxy)
    {
        $service = $request->get('service', '');
        $ticket  = $request->get('ticket', '');
        $format  = strtoupper($request->get('format', 'XML'));
        if (empty($service) || empty($ticket)) {
            return $this->authFailureResponse(
                CasException::INVALID_REQUEST,
                'param service and ticket can not be empty',
                $format
            );
        }

        if (!$this->lockTicket($ticket)) {
            return $this->authFailureResponse(CasException::INTERNAL_ERROR, 'try to lock ticket failed', $format);
        }

        $record = $this->ticketRepository->getByTicket($ticket);
        try {
            if (!$record || (!$allowProxy && $record->isProxy())) {
                throw new CasException(CasException::INVALID_TICKET, 'ticket is not valid');
            }

            if ($record->service_url != $service) {
                throw new CasException(CasException::INVALID_SERVICE, 'service is not valid');
            }
        } catch (CasException $e) {
            //invalid ticket if error occur
            $record instanceof Ticket && $this->ticketRepository->invalidTicket($record);
            $this->unlockTicket($ticket);

            return $this->authFailureResponse($e->getCasErrorCode(), $e->getMessage(), $format);
        }

        $proxies = [];
        if ($record->isProxy()) {
            $proxies = $record->proxies;
        }

        $user = $record->user;
        $this->ticketRepository->invalidTicket($record);
        $this->unlockTicket($ticket);

        //handle pgt
        $iou    = null;
        $pgtUrl = $request->get('pgtUrl', '');
        if ($pgtUrl) {
            try {
                $pgTicket = $this->pgTicketRepository->applyTicket($user, $pgtUrl, $proxies);
                $iou      = $this->ticketGenerator->generateOne(config('cas.pg_ticket_iou_len', 64), 'PGTIOU-');
                if (!$this->pgtCaller->call($pgtUrl, $pgTicket->ticket, $iou)) {
                    $iou = null;
                }
            } catch (CasException $e) {
                $iou = null;
            }
        }

        $attr = $returnAttr ? $record->user->getCASAttributes() : [];

        return $this->authSuccessResponse($record->user->getName(), $format, $attr, $proxies, $iou);
    }

    /**
     * @param string      $username
     * @param string      $format
     * @param array       $attributes
     * @param array       $proxies
     * @param string|null $pgt
     * @return Response
     */
    protected function authSuccessResponse($username, $format, $attributes, $proxies = [], $pgt = null)
    {
        if (strtoupper($format) === 'JSON') {
            $resp = app(JsonAuthenticationSuccessResponse::class);
        } else {
            $resp = app(XmlAuthenticationSuccessResponse::class);
        }
        $resp->setUser($username);
        if (!empty($attributes)) {
            $resp->setAttributes($attributes);
        }
        if (!empty($proxies)) {
            $resp->setProxies($proxies);
        }

        if (is_string($pgt)) {
            $resp->setProxyGrantingTicket($pgt);
        }

        return $resp->toResponse();
    }

    /**
     * @param string $code
     * @param string $description
     * @param string $format
     * @return Response
     */
    protected function authFailureResponse($code, $description, $format)
    {
        if (strtoupper($format) === 'JSON') {
            $resp = app(JsonAuthenticationFailureResponse::class);
        } else {
            $resp = app(XmlAuthenticationFailureResponse::class);
        }
        $resp->setFailure($code, $description);

        return $resp->toResponse();
    }

    /**
     * @param string $ticket
     * @param string $format
     * @return Response
     */
    protected function proxySuccessResponse($ticket, $format)
    {
        if (strtoupper($format) === 'JSON') {
            $resp = app(JsonProxySuccessResponse::class);
        } else {
            $resp = app(XmlProxySuccessResponse::class);
        }
        $resp->setProxyTicket($ticket);

        return $resp->toResponse();
    }

    /**
     * @param string $code
     * @param string $description
     * @param string $format
     * @return Response
     */
    protected function proxyFailureResponse($code, $description, $format)
    {
        if (strtoupper($format) === 'JSON') {
            $resp = app(JsonProxyFailureResponse::class);
        } else {
            $resp = app(XmlProxyFailureResponse::class);
        }
        $resp->setFailure($code, $description);

        return $resp->toResponse();
    }

    /**
     * @param string $ticket
     * @return bool
     */
    protected function lockTicket($ticket)
    {
        return $this->ticketLocker->acquireLock($ticket, config('cas.lock_timeout'));
    }

    /**
     * @param string $ticket
     * @return bool
     */
    protected function unlockTicket($ticket)
    {
        return $this->ticketLocker->releaseLock($ticket);
    }
}
