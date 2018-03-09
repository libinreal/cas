<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Contracts\Models\UserModel;

class CasUser extends Authenticatable implements UserModel
{

	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'token',
        'enabled',
        'email',
        'real_name',
        'name',
        'service_id',
    ];

    protected $casts = [
        'enabled' => 'boolean'
    ];

    protected $hidden = [
        'token',
    ];

    protected $table = 'cas_users';

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
     * Get multi service users
     * @return Relation
     */
    public function serviceUsers()
    {
    	return $this->hasMany(CasServiceUser::class);
    }

    /**
     * Get multi services the user uses
     * @return Relation
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'cas_service_users', 'cas_user_id', 'service_id');
    }

    /**
     * Get random_str from `cas_service_users`
     * @return string
     */
    public function getRandomStr()
    {
        if($this->id && $this->service_id){

            $serviceUser = CasServiceUser::where('service_id', $this->service_id)
                        ->where('cas_user_id', $this->id)->first();
            /*if($serviceUser)
                file_put_contents(storage_path().'/logs/cms1.login.20180308.log', $serviceUser->toJson()."\r\n", FILE_APPEND);
            else
                file_put_contents(storage_path().'/logs/cms1.login.20180308.log', "service user not found\r\n", FILE_APPEND);*/
            return $serviceUser ? $serviceUser->random_str : '';
        }

        return '';
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

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function getEloquentModel()
    {
        return $this;
    }

    /**
     * @return array
     */
    public function getCASAttributes()
    {
        return [
            'email'         => $this->email,
            'real_name'     => $this->real_name,
            'oauth_profile' => json_encode($this->oauth->profile),
        ];
    }
}
