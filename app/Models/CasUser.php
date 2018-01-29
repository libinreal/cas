<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Contracts\Models\UserModel;

class CasUser extends Authenticatable
{

	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'token',
        'enabled',
    ];

    protected $table = 'cas_users';
    protected $primaryKey = 'token';

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'token';
    }

    /**
     * Get multi users of services accoding the token
     * @return Relation
     */
    public function serviceUsers()
    {
    	return $this->hasMany('App\Models\CasServiceUser', 'token', 'token')->select('token, enabled, service_id, user_name');
    }

    /**
     * Get signle user of services accoding the service_id and user_name
     * @return Relation
     */
    public function serviceUserByKeys($serviceId, $userName)
    {
    	/*libin_debug($this->with('serviceUsers')
    		->where('service_id', $serviceId)
    		->where('user_name', $userName)->select('token, enabled, service_id, user_name')->first()->toSql());*/
    	return $this->with('serviceUsers')
    		->where('service_id', $serviceId)
    		->where('user_name', $userName)->select('token, enabled, service_id, user_name');
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return '';
    }
}
