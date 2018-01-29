<?php

namespace Illuminate\Auth;

use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use App\Events\Model\CasUserTokenChangeEvent;

class CasUserProvider extends EloquentUserProvider
{

	/**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        return $this->createModel()
        	->newQuery()
        	->with('serviceUsers')
        	->where($this->model->getRememberTokenName(), $identifier)
        	->get();
    }

	/**
     * Retrieve a user by their unique identifier.
     *
     * @param  int  $serviceId
     * @param  string  $userName
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByKeys($serviceId, $userName)
    {
        return $this->createModel()
        	->serviceUserByKeys($serviceId, $userName)
        	->get();
        // return $this->createModel()->newQuery()->find($identifier);
    }

    /**
     * Retrieve a user by their "remember me" token.
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($token)
    {

        return $this->createModel()
        	->newQuery()
        	->with('serviceUsers')
            ->where($this->model->getRememberTokenName(), $token)
            ->get();
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
        $beforeChangeToken = $user->getRememberToken();

        $change = $user->save();

     
        if($change)
        {
            $user->setRememberToken($token);
            //fire CasUserTokenChangeEvent
            // event(new CasUserTokenChangeEvent($beforeChangeToken, $token));
        }
     
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials)) {
            return;
        }

        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.
        $query = $this->createModel()->newQuery();

        foreach ($credentials as $key => $value) {
            if (! Str::contains($key, 'password')) {
                $query->where($key, $value);
            }
        }

        return $query->first();
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
        $plain = $credentials['password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }

}