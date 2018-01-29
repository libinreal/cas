<?php
namespace App\Auth\Cas;

use Illuminate\Auth\SessionGuard;

class CasSessionGuard extends SessionGuard
{
	/**
     * Get the currently authenticated user.
     *
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
        if (! is_null($this->user)) {
            return $this->user;
        }

        $token = $this->session->get($this->getName());

        
        // First we will try to load the user using the identifier in the session if
        // one exists. Otherwise we will check for a "remember me" cookie in this
        // request, and if one exists, attempt to retrieve the user using that.
        $user = null;

        if (! is_null($token)) {
            $user = $this->provider->retrieveByToken($token);
            
        }


        
        // If the user is null, but we decrypt a "recaller" cookie we can attempt to
        // pull the user data on that cookie which serves as a remember cookie on
        // the application. Once we have a user we can return it to the caller.
        $recaller = $this->getRecaller();

        if (is_null($user) && ! is_null($recaller)) {
            $user = $this->getUserByRecaller($recaller);
        }

        if ($user) {
            $this->updateSession($user->getAuthIdentifier());

            $this->fireLoginEvent($user, true);
        }
        
        //if empty($user->serviceUsers) $user->delete()
        //.....
       
        return $this->user = $user;
    }

    /**
     * Log a user into the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function login(AuthenticatableContract $user, $remember = true)
    {
        $this->updateSession($user->getAuthIdentifier());

        // If the user should be permanently "remembered" by the application we will
        // queue a permanent cookie that contains the encrypted copy of the user
        // identifier. We will then decrypt this later to retrieve the users.
        if ($remember) {
            $this->createRememberTokenIfDoesntExist($user);

            $this->queueRecallerCookie($user);
        }

        // If we have an event dispatcher instance set we will fire an event so that
        // any listeners will hook into the authentication events and run actions
        // based on the login and logout events fired from the guard instances.
        $this->fireLoginEvent($user, $remember);

        $this->setUser($user);
    }

    /**
     * Get the token for the currently authenticated user.
     *
     * @return string|null $serviceId . '|' . $userName
     */
    public function token()
    {
        if ($this->loggedOut) {
            return;
        }

        $token = $this->session->get($this->getName());

        if (is_null($token) && $this->user()) {
            $token = $this->user()->getAuthIdentifier();
        }

        return $token;
    }

    /**
     * Update the session with the given token.
     * @param  array  array($serviceId, $userName)
     * @return void
     */
    protected function updateSession($token)
    {
        $this->session->set($this->getName(), $token);

        $this->session->migrate(true);
    }

    /**
     * Queue the recaller cookie into the cookie jar.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function queueRecallerCookie(AuthenticatableContract $user)
    {
        $value = $user->getRememberToken();

        $this->getCookieJar()->queue($this->createRecaller($value));
    }

    /**
     * Determine if the recaller cookie is in a valid format.
     *
     * @param  mixed  $recaller
     * @return bool
     */
    protected function validRecaller($recaller)
    {
        if (! is_string($recaller) ) {
            return false;
        }

        return strlen($recaller) > 0;
    }

    /**
     * Pull a user from the repository by its recaller ID.
     *
     * @param  string  $recaller
     * @return mixed
     */
    protected function getUserByRecaller($recaller)
    {
        if ($this->validRecaller($recaller) && ! $this->tokenRetrievalAttempted) {
            $this->tokenRetrievalAttempted = true;

            $this->viaRemember = ! is_null($user = $this->provider->retrieveByToken($recaller));

            return $user;
        }
    }
}